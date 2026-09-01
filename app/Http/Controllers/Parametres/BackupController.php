<?php

namespace App\Http\Controllers\Parametres;
use App\Http\Controllers\Controller;

use App\Constants\Roles;
use App\Jobs\RunBackupJob;
use App\Models\Backup;
use App\Models\BackupSetting;
use App\Models\FileStorageSetting;
use App\Services\BackupService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class BackupController extends Controller
{
    public function __construct(private readonly BackupService $service)
    {
    }

    private function authorizeAdmin(Request $request): void
    {
        // Autorisation déléguée aux permissions (middleware can:* sur les routes).
    }

    public function index(Request $request): Response
    {
        $this->authorizeAdmin($request);

        $backups = Backup::with('createdBy:id,firstname,lastname')
            ->latest()
            ->limit(100)
            ->get()
            ->map(fn (Backup $b) => [
                'id'         => $b->id,
                'filename'   => $b->filename,
                'format'     => $b->format,
                'disk'       => $b->disk,
                'size'       => $b->size,
                'status'     => $b->status,
                'error'      => $b->error,
                'scheduled'  => $b->scheduled,
                'created_by' => $b->createdBy?->name,
                'created_at' => $b->created_at?->format('d/m/Y H:i'),
            ]);

        return Inertia::render('Parametres/Backups', [
            'backups'        => $backups,
            'settings'       => BackupSetting::allSettings(),
            'storageDriver'  => FileStorageSetting::get('driver', 'local') === 's3' ? 's3' : 'local',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeAdmin($request);

        $validated = $request->validate([
            'formats'   => ['required', 'array', 'min:1'],
            'formats.*' => ['in:json,sql'],
        ], [
            'formats.required' => 'Choisissez au moins un format.',
        ]);

        RunBackupJob::dispatch($validated['formats'], $request->user()->id);

        return back()->with(
            'success',
            'Sauvegarde lancée. Elle apparaîtra dans la liste une fois terminée.'
        );
    }

    public function restore(Request $request): RedirectResponse
    {
        $this->authorizeAdmin($request);

        $request->validate([
            'file' => ['required', 'file', 'max:204800'], // 200 Mo
        ], [
            'file.required' => 'Sélectionnez un fichier de sauvegarde.',
            'file.max'      => 'Le fichier ne doit pas dépasser 200 Mo.',
        ]);

        $ext = strtolower($request->file('file')->getClientOriginalExtension());
        if (! in_array($ext, ['json', 'sql', 'gz', 'dump'], true)) {
            return back()->withErrors(['file' => 'Format non supporté : choisissez un fichier .json, .sql, .gz ou .dump.']);
        }

        try {
            $result = $this->service->restore($request->file('file'));
        } catch (\Throwable $e) {
            return back()->with('error', 'Échec de la restauration : ' . $e->getMessage());
        }

        $detail = isset($result['rows'])
            ? "{$result['tables']} tables, {$result['rows']} lignes"
            : "{$result['tables']} tables";

        return back()->with('success', "Restauration effectuée ({$detail}). Une sauvegarde de sécurité a été créée au préalable.");
    }

    public function download(Request $request, Backup $backup)
    {
        $this->authorizeAdmin($request);

        abort_unless($backup->status === 'completed', 404);
        abort_unless(Storage::disk('media')->exists($backup->path), 404);

        return Storage::disk('media')->download($backup->path, $backup->filename);
    }

    public function destroy(Request $request, Backup $backup): RedirectResponse
    {
        $this->authorizeAdmin($request);

        $this->service->delete($backup);

        return back()->with('success', 'Sauvegarde supprimée.');
    }

    public function updateSchedule(Request $request): RedirectResponse
    {
        $this->authorizeAdmin($request);

        $validated = $request->validate([
            'frequency'   => ['required', 'in:none,daily,weekly'],
            'time'        => ['required', 'date_format:H:i'],
            'day_of_week' => ['required', 'integer', 'between:1,7'],
            'formats'     => ['required', 'array', 'min:1'],
            'formats.*'   => ['in:json,sql'],
            'retention'   => ['required', 'integer', 'min:0', 'max:365'],
        ]);

        BackupSetting::set('frequency', $validated['frequency']);
        BackupSetting::set('time', $validated['time']);
        BackupSetting::set('day_of_week', (string) $validated['day_of_week']);
        BackupSetting::set('formats', implode(',', $validated['formats']));
        BackupSetting::set('retention', (string) $validated['retention']);

        return back()->with('success', 'Planification mise à jour.');
    }
}
