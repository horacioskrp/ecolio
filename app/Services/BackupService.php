<?php

namespace App\Services;

use App\Constants\Roles;
use App\Models\Backup;
use App\Models\BackupSetting;
use App\Models\FileStorageSetting;
use App\Models\User;
use App\Notifications\BackupFailedNotification;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;

/**
 * Génère et restaure les sauvegardes de la base.
 *
 * Conçu pour tenir la charge sur plusieurs années de données :
 *  - écriture en flux (jamais toute la base en mémoire) ;
 *  - compression gzip systématique (~85 % de taille en moins) ;
 *  - sur PostgreSQL, délégation à `pg_dump` (format custom, schéma inclus),
 *    avec repli automatique sur l'export SQL portable si l'outil est absent.
 */
class BackupService
{
    /** Tables transitoires exclues des sauvegardes. */
    private const EXCLUDED = [
        'migrations', 'cache', 'cache_locks', 'sessions',
        'jobs', 'job_batches', 'failed_jobs', 'password_reset_tokens',
    ];

    private const DIRECTORY = 'backups';

    /** Temps max (s) accordé aux outils externes (pg_dump / pg_restore). */
    private const PROCESS_TIMEOUT = 900;

    /**
     * Génère une sauvegarde pour chaque format demandé.
     *
     * @param  array<int,string>  $formats  sous-ensemble de ['json', 'sql']
     * @return Collection<int,Backup>
     */
    public function run(array $formats, ?string $userId = null, bool $scheduled = false): Collection
    {
        $formats = array_values(array_intersect($formats, ['json', 'sql'])) ?: ['json'];
        $results = collect();

        foreach ($formats as $format) {
            $results->push($this->generate($format, 'backup', $userId, $scheduled));
        }

        $this->applyRetention();

        return $results;
    }

    /**
     * Archive verrouillée d'une année scolaire (clôture) : snapshot complet
     * étiqueté, conservé à long terme et exclu de la rétention automatique.
     * Idempotent : au plus une archive verrouillée par année.
     */
    public function archiveAcademicYear(\App\Models\AcademicYear $year, ?string $userId = null, bool $scheduled = false): ?Backup
    {
        $already = Backup::where('academic_year_id', $year->id)
            ->where('locked', true)
            ->where('status', 'completed')
            ->exists();

        if ($already) {
            return null;
        }

        $prefix = 'archive_' . preg_replace('/[^A-Za-z0-9_-]+/', '-', (string) $year->year);

        // Format SQL : dump complet (schéma inclus sur PostgreSQL via pg_dump).
        return $this->generate('sql', $prefix, $userId, $scheduled, [
            'academic_year_id' => $year->id,
            'label'            => 'Année ' . $year->year,
            'locked'           => true,
        ]);
    }

    /**
     * Génère un fichier de sauvegarde (un format), l'enregistre sur le disque
     * et trace le résultat. La rétention n'est PAS appliquée ici.
     *
     * @param  array<string,mixed>  $extra  attributs additionnels (label, locked, academic_year_id…)
     */
    private function generate(string $format, string $prefix, ?string $userId, bool $scheduled, array $extra = []): Backup
    {
        $disk      = $this->disk();
        $timestamp = now()->format('Y-m-d_His');
        $filename  = "{$prefix}_{$timestamp}.{$format}";
        $path      = self::DIRECTORY . '/' . $filename;
        $tmp       = tempnam(sys_get_temp_dir(), 'bkp_');

        try {
            // Le builder écrit dans $tmp et renvoie l'extension réellement produite.
            $extension = $this->writeDump($format, $tmp);
            $filename  = "{$prefix}_{$timestamp}.{$extension}";
            $path      = self::DIRECTORY . '/' . $filename;
            $size      = filesize($tmp) ?: 0;
            $checksum  = hash_file('sha256', $tmp) ?: null;

            $stream = fopen($tmp, 'rb');
            Storage::disk($disk)->writeStream($path, $stream);
            if (is_resource($stream)) {
                fclose($stream);
            }

            return Backup::create(array_merge([
                'filename'   => $filename,
                'path'       => $path,
                'disk'       => $this->driverName(),
                'format'     => $format,
                'size'       => $size,
                'checksum'   => $checksum,
                'status'     => 'completed',
                'scheduled'  => $scheduled,
                'created_by' => $userId,
            ], $extra));
        } catch (\Throwable $e) {
            $backup = Backup::create(array_merge([
                'filename'   => $filename,
                'path'       => $path,
                'disk'       => $this->driverName(),
                'format'     => $format,
                'status'     => 'failed',
                'error'      => mb_substr($e->getMessage(), 0, 1000),
                'scheduled'  => $scheduled,
                'created_by' => $userId,
            ], $extra));

            $this->notifyFailure($backup);

            return $backup;
        } finally {
            if (is_string($tmp) && is_file($tmp)) {
                @unlink($tmp);
            }
        }
    }

    /**
     * Écrit la sauvegarde du format demandé dans $tmp.
     *
     * @return string  extension du fichier généré (json.gz | sql.gz | dump)
     */
    private function writeDump(string $format, string $tmp): string
    {
        if ($format === 'sql') {
            if (DB::getDriverName() === 'pgsql') {
                try {
                    $this->pgDump($tmp);

                    return 'dump';
                } catch (\Throwable $e) {
                    report($e); // pg_dump indisponible : on bascule sur l'export portable
                }
            }

            $this->writeSqlGz($tmp);

            return 'sql.gz';
        }

        $this->writeJsonGz($tmp);

        return 'json.gz';
    }

    /**
     * Restaure la base à partir d'un fichier de sauvegarde
     * (json[.gz], sql[.gz] ou dump PostgreSQL). Une sauvegarde de sécurité
     * (JSON) est générée au préalable.
     *
     * @return array{format:string, tables:int, rows?:int}
     */
    public function restore(UploadedFile $file): array
    {
        $name = strtolower($file->getClientOriginalName());
        $real = (string) $file->getRealPath();

        // Filet de sécurité : snapshot avant écrasement
        try {
            $this->run(['json']);
        } catch (\Throwable) {
            // On n'empêche pas la restauration si le snapshot échoue
        }

        // Dump natif PostgreSQL (format custom)
        if (str_ends_with($name, '.dump') || str_ends_with($name, '.pgdump')) {
            return $this->restorePgDump($real);
        }

        // Fichier compressé : on décompresse dans un fichier temporaire
        $source = $real;
        $tmp    = null;
        if (str_ends_with($name, '.gz')) {
            $tmp = tempnam(sys_get_temp_dir(), 'rst_');
            $this->gunzip($real, $tmp);
            $source = $tmp;
            $name   = substr($name, 0, -3); // retire « .gz »
        }

        $ext = pathinfo($name, PATHINFO_EXTENSION);
        if (! in_array($ext, ['json', 'sql'], true)) {
            if ($tmp) {
                @unlink($tmp);
            }
            throw new \InvalidArgumentException('Format non supporté (JSON, SQL ou dump PostgreSQL attendu).');
        }

        try {
            $contents = (string) file_get_contents($source);

            return $ext === 'sql' ? $this->restoreSql($contents) : $this->restoreJson($contents);
        } finally {
            if ($tmp && is_file($tmp)) {
                @unlink($tmp);
            }
        }
    }

    /** Restaure depuis un export JSON (vide puis réinsère chaque table). */
    private function restoreJson(string $contents): array
    {
        $data = json_decode($contents, true);

        if (! is_array($data) || ! isset($data['tables']) || ! is_array($data['tables'])) {
            throw new \RuntimeException('Fichier JSON de sauvegarde invalide.');
        }

        $rowsTotal = 0;

        try {
            DB::transaction(function () use ($data, &$rowsTotal): void {
                $this->deferForeignKeys();

                foreach ($data['tables'] as $table => $rows) {
                    if (! Schema::hasTable($table)) {
                        continue;
                    }
                    DB::table($table)->delete();

                    foreach (array_chunk($rows, 500) as $chunk) {
                        if (! empty($chunk)) {
                            DB::table($table)->insert($chunk);
                            $rowsTotal += count($chunk);
                        }
                    }
                }
            });
        } finally {
            $this->restoreForeignKeys();
        }

        return ['format' => 'json', 'tables' => count($data['tables']), 'rows' => $rowsTotal];
    }

    /** Restaure depuis un export SQL (vide les tables visées puis rejoue le script). */
    private function restoreSql(string $contents): array
    {
        preg_match_all('/INSERT\s+INTO\s+"([^"]+)"/i', $contents, $matches);
        $tables = array_values(array_unique($matches[1] ?? []));

        try {
            DB::transaction(function () use ($contents, $tables): void {
                $this->deferForeignKeys();

                foreach ($tables as $table) {
                    if (Schema::hasTable($table)) {
                        DB::table($table)->delete();
                    }
                }

                DB::unprepared($contents);
            });
        } finally {
            $this->restoreForeignKeys();
        }

        return ['format' => 'sql', 'tables' => count($tables)];
    }

    /** Restaure un dump natif PostgreSQL via `pg_restore` (drop + recreate). */
    private function restorePgDump(string $dumpPath): array
    {
        $c = $this->pgConnection();

        $process = new Process([
            'pg_restore',
            '--clean', '--if-exists', '--no-owner', '--no-privileges',
            '-h', $c['host'], '-p', (string) $c['port'],
            '-U', $c['username'], '-d', $c['database'],
            $dumpPath,
        ], null, ['PGPASSWORD' => $c['password']], null, self::PROCESS_TIMEOUT);

        $process->run();

        // pg_restore peut émettre des avertissements non bloquants (exit 1) ;
        // on échoue seulement sur une erreur franche (exit >= 2).
        if ($process->getExitCode() >= 2) {
            throw new \RuntimeException('pg_restore a échoué : ' . mb_substr($process->getErrorOutput(), 0, 500));
        }

        return ['format' => 'dump', 'tables' => 0];
    }

    /** Désactive / diffère les contraintes de clés étrangères le temps de la restauration. */
    private function deferForeignKeys(): void
    {
        try {
            match (DB::getDriverName()) {
                'sqlite' => DB::statement('PRAGMA defer_foreign_keys = ON'),
                'mysql'  => DB::statement('SET FOREIGN_KEY_CHECKS=0'),
                'pgsql'  => DB::statement("SET session_replication_role = 'replica'"),
                default  => null,
            };
        } catch (\Throwable) {
            // Best effort selon les privilèges du compte SGBD
        }
    }

    private function restoreForeignKeys(): void
    {
        try {
            match (DB::getDriverName()) {
                'mysql'  => DB::statement('SET FOREIGN_KEY_CHECKS=1'),
                'pgsql'  => DB::statement("SET session_replication_role = 'origin'"),
                default  => null,
            };
        } catch (\Throwable) {
            // Best effort
        }
    }

    /**
     * Vérifie l'intégrité d'une sauvegarde stockée en recalculant son empreinte.
     *
     * @return array{ok:bool, reason:string}
     */
    public function verify(Backup $backup): array
    {
        if ($backup->status !== 'completed') {
            return ['ok' => false, 'reason' => "La sauvegarde n'est pas complète."];
        }
        if (! Storage::disk($this->disk())->exists($backup->path)) {
            return ['ok' => false, 'reason' => 'Fichier introuvable sur le stockage.'];
        }
        if (! $backup->checksum) {
            return ['ok' => false, 'reason' => 'Aucune empreinte enregistrée (sauvegarde antérieure).'];
        }

        $ctx    = hash_init('sha256');
        $stream = Storage::disk($this->disk())->readStream($backup->path);
        while (! feof($stream)) {
            hash_update($ctx, (string) fread($stream, 262144));
        }
        fclose($stream);

        return hash_final($ctx) === $backup->checksum
            ? ['ok' => true, 'reason' => 'Fichier intègre (empreinte conforme).']
            : ['ok' => false, 'reason' => 'Empreinte différente : le fichier est corrompu.'];
    }

    /** Alerte les administrateurs par e-mail en cas d'échec de sauvegarde. */
    private function notifyFailure(Backup $backup): void
    {
        try {
            $admins = User::role(Roles::ADMINISTRATOR)->whereNotNull('email')->get();
            if ($admins->isNotEmpty()) {
                Notification::send($admins, new BackupFailedNotification($backup));
            }
        } catch (\Throwable) {
            // Messagerie non configurée : on n'empêche pas le flux de sauvegarde.
        }
    }

    /** Supprime un enregistrement de sauvegarde et son fichier. */
    public function delete(Backup $backup): void
    {
        if (Storage::disk($this->disk())->exists($backup->path)) {
            Storage::disk($this->disk())->delete($backup->path);
        }

        $backup->delete();
    }

    /**
     * Export JSON gzippé, écrit en flux (une ligne chargée à la fois).
     * Structure identique à l'ancien format : {generated_at, driver, tables:{...}}.
     */
    private function writeJsonGz(string $tmp): void
    {
        $gz = gzopen($tmp, 'wb6');
        if ($gz === false) {
            throw new \RuntimeException('Impossible d\'ouvrir le fichier de sauvegarde temporaire.');
        }

        $flags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;

        gzwrite($gz, '{"generated_at":' . json_encode(now()->toIso8601String())
            . ',"driver":' . json_encode(DB::getDriverName())
            . ',"tables":{');

        $firstTable = true;
        foreach ($this->tables() as $table) {
            gzwrite($gz, ($firstTable ? '' : ',') . json_encode($table) . ':[');
            $firstRow = true;
            foreach (DB::table($table)->cursor() as $row) {
                gzwrite($gz, ($firstRow ? '' : ',') . json_encode((array) $row, $flags));
                $firstRow = false;
            }
            gzwrite($gz, ']');
            $firstTable = false;
        }

        gzwrite($gz, '}}');
        gzclose($gz);
    }

    /** Export SQL gzippé (INSERT, données uniquement), écrit en flux. */
    private function writeSqlGz(string $tmp): void
    {
        $gz = gzopen($tmp, 'wb6');
        if ($gz === false) {
            throw new \RuntimeException('Impossible d\'ouvrir le fichier de sauvegarde temporaire.');
        }

        gzwrite($gz, "-- Sauvegarde Dalibi\n");
        gzwrite($gz, '-- Généré le ' . now()->toDateTimeString() . "\n");
        gzwrite($gz, '-- SGBD : ' . DB::getDriverName() . "\n\n");

        foreach ($this->tables() as $table) {
            $columns = null;
            foreach (DB::table($table)->cursor() as $row) {
                $arr = (array) $row;
                if ($columns === null) {
                    $columns = array_keys($arr);
                    $colList = implode(', ', array_map(fn ($c) => '"' . $c . '"', $columns));
                    gzwrite($gz, "-- Table : {$table}\n");
                }
                $values = array_map(fn ($v) => $this->quote($v), array_values($arr));
                gzwrite($gz, sprintf('INSERT INTO "%s" (%s) VALUES (%s);' . "\n", $table, $colList, implode(', ', $values)));
            }
            if ($columns !== null) {
                gzwrite($gz, "\n");
            }
        }

        gzclose($gz);
    }

    /** Dump natif PostgreSQL au format custom (compressé, schéma + données inclus). */
    private function pgDump(string $tmp): void
    {
        $c = $this->pgConnection();

        $process = new Process([
            'pg_dump',
            '-Fc', '-Z', '6', '--no-owner', '--no-privileges',
            '-h', $c['host'], '-p', (string) $c['port'],
            '-U', $c['username'], '-d', $c['database'],
            '-f', $tmp,
        ], null, ['PGPASSWORD' => $c['password']], null, self::PROCESS_TIMEOUT);

        $process->run();

        if (! $process->isSuccessful()) {
            throw new \RuntimeException('pg_dump indisponible ou en échec : ' . mb_substr($process->getErrorOutput(), 0, 300));
        }
    }

    /** Paramètres de connexion PostgreSQL de la connexion par défaut. */
    private function pgConnection(): array
    {
        $conn = config('database.connections.' . config('database.default'));

        return [
            'host'     => $conn['host'] ?? '127.0.0.1',
            'port'     => $conn['port'] ?? 5432,
            'database' => $conn['database'] ?? '',
            'username' => $conn['username'] ?? '',
            'password' => (string) ($conn['password'] ?? ''),
        ];
    }

    /** Décompresse un fichier .gz vers $dest, en flux. */
    private function gunzip(string $src, string $dest): void
    {
        $in  = gzopen($src, 'rb');
        $out = fopen($dest, 'wb');
        if ($in === false || $out === false) {
            throw new \RuntimeException('Impossible de décompresser le fichier de sauvegarde.');
        }

        while (! gzeof($in)) {
            fwrite($out, (string) gzread($in, 262144));
        }

        gzclose($in);
        fclose($out);
    }

    /** Formate une valeur pour une instruction SQL. */
    private function quote(mixed $value): string
    {
        if ($value === null) {
            return 'NULL';
        }
        if (is_bool($value)) {
            return $value ? 'TRUE' : 'FALSE';
        }
        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        return "'" . str_replace("'", "''", (string) $value) . "'";
    }

    /** Liste des tables à sauvegarder (préfixe de schéma retiré, tables transitoires exclues). */
    private function tables(): array
    {
        return collect(Schema::getTableListing())
            ->map(fn ($t) => str_contains($t, '.') ? substr($t, strrpos($t, '.') + 1) : $t)
            ->reject(fn ($t) => in_array($t, self::EXCLUDED, true))
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    /** Disque de destination : suit la configuration centralisée (local ou S3/R2 distant). */
    private function disk(): string
    {
        return 'media';
    }

    private function driverName(): string
    {
        return FileStorageSetting::get('driver', 'local') === 's3' ? 's3' : 'local';
    }

    /** Conserve uniquement les N dernières sauvegardes (par format). */
    private function applyRetention(): void
    {
        $retention = (int) BackupSetting::get('retention', 10);
        if ($retention <= 0) {
            return;
        }

        foreach (['json', 'sql'] as $format) {
            Backup::where('format', $format)
                ->where('status', 'completed')
                ->where('locked', false) // les archives d'année scolaire ne sont jamais purgées
                ->orderByDesc('created_at')
                ->skip($retention)
                ->take(PHP_INT_MAX)
                ->get()
                ->each(fn (Backup $b) => $this->delete($b));
        }
    }

    public static function lastBackupAt(): ?Carbon
    {
        return Backup::where('status', 'completed')->latest()->value('created_at');
    }
}
