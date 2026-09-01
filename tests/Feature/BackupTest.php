<?php

namespace Tests\Feature;

use App\Constants\Roles;
use App\Models\Backup;
use App\Models\BackupSetting;
use App\Models\User;
use App\Services\BackupService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BackupTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        Storage::fake('media');
    }

    private function admin(): User
    {
        $u = User::factory()->create();
        $u->assignRole(Roles::ADMINISTRATOR);

        return $u;
    }

    public function test_admin_can_generate_backup_in_both_formats(): void
    {
        $this->actingAs($this->admin())
            ->post(route('backups.store'), ['formats' => ['json', 'sql']])
            ->assertRedirect();

        $this->assertEquals(2, Backup::where('status', 'completed')->count());

        foreach (Backup::all() as $b) {
            Storage::disk('media')->assertExists($b->path);
        }
    }

    public function test_backup_requires_a_format(): void
    {
        $this->actingAs($this->admin())
            ->post(route('backups.store'), ['formats' => []])
            ->assertSessionHasErrors('formats');
    }

    public function test_non_admin_cannot_access_backups(): void
    {
        $teacher = User::factory()->create();
        $teacher->assignRole(Roles::TEACHER);

        $this->actingAs($teacher)->get(route('backups.index'))->assertForbidden();
        $this->actingAs($teacher)->post(route('backups.store'), ['formats' => ['json']])->assertForbidden();
    }

    public function test_admin_can_save_schedule(): void
    {
        $this->actingAs($this->admin())
            ->post(route('backups.schedule'), [
                'frequency'   => 'weekly',
                'time'        => '04:00',
                'day_of_week' => 2,
                'formats'     => ['json', 'sql'],
                'retention'   => 5,
            ])
            ->assertRedirect();

        $this->assertEquals('weekly', BackupSetting::get('frequency'));
        $this->assertEquals('04:00', BackupSetting::get('time'));
        $this->assertEquals('5', BackupSetting::get('retention'));
    }

    public function test_admin_can_delete_backup(): void
    {
        $this->actingAs($this->admin())->post(route('backups.store'), ['formats' => ['json']]);
        $backup = Backup::first();

        $this->actingAs($this->admin())
            ->delete(route('backups.destroy', $backup))
            ->assertRedirect();

        $this->assertDatabaseMissing('backups', ['id' => $backup->id]);
        Storage::disk('media')->assertMissing($backup->path);
    }

    public function test_restore_from_json_repopulates_the_database(): void
    {
        // Données initiales
        $u = User::factory()->create(['firstname' => 'Ama', 'lastname' => 'Koffi']);

        // Snapshot JSON du contenu actuel
        $json = json_encode([
            'tables' => [
                'users' => User::all()->map(fn ($x) => $x->getAttributes())->all(),
            ],
        ]);

        // On supprime la donnée…
        $u->forceDelete();
        $this->assertDatabaseMissing('users', ['id' => $u->id]);

        // … puis on restaure via le service
        Storage::fake('media');
        app(BackupService::class)->restore(
            UploadedFile::fake()->createWithContent('dump.json', $json)
        );

        $this->assertDatabaseHas('users', ['id' => $u->id, 'firstname' => 'Ama']);
    }

    public function test_admin_can_upload_and_restore_via_endpoint(): void
    {
        $admin = $this->admin();
        $json  = json_encode(['tables' => ['users' => User::all()->map(fn ($x) => $x->getAttributes())->all()]]);

        $this->actingAs($admin)
            ->post(route('backups.restore'), [
                'file' => UploadedFile::fake()->createWithContent('dump.json', $json),
            ])
            ->assertRedirect()
            ->assertSessionHas('success');
    }

    public function test_restore_rejects_unsupported_format(): void
    {
        $this->actingAs($this->admin())
            ->post(route('backups.restore'), [
                'file' => UploadedFile::fake()->create('archive.zip', 10),
            ])
            ->assertSessionHasErrors('file');
    }

    public function test_non_admin_cannot_restore(): void
    {
        $teacher = User::factory()->create();
        $teacher->assignRole(Roles::TEACHER);

        $this->actingAs($teacher)
            ->post(route('backups.restore'), ['file' => UploadedFile::fake()->createWithContent('d.json', '{"tables":{}}')])
            ->assertForbidden();
    }

    public function test_generated_files_are_gzip_compressed(): void
    {
        $this->actingAs($this->admin())->post(route('backups.store'), ['formats' => ['json']]);

        $backup = Backup::where('format', 'json')->firstOrFail();

        // Le nom reflète la compression et le contenu est bien du gzip valide.
        $this->assertStringEndsWith('.json.gz', $backup->filename);

        $raw = Storage::disk('media')->get($backup->path);
        $this->assertSame("\x1f\x8b", substr($raw, 0, 2), 'Le fichier doit être compressé (en-tête gzip).');

        $decoded = json_decode((string) gzdecode($raw), true);
        $this->assertIsArray($decoded);
        $this->assertArrayHasKey('tables', $decoded);
    }

    public function test_restore_from_gzipped_backup_roundtrip(): void
    {
        $u = User::factory()->create(['firstname' => 'Kossi', 'lastname' => 'Mensah']);

        $this->actingAs($this->admin())->post(route('backups.store'), ['formats' => ['json']]);
        $backup = Backup::where('format', 'json')->firstOrFail();
        $gz     = Storage::disk('media')->get($backup->path);

        $u->forceDelete();
        $this->assertDatabaseMissing('users', ['id' => $u->id]);

        app(BackupService::class)->restore(
            UploadedFile::fake()->createWithContent('backup_restore.json.gz', $gz)
        );

        $this->assertDatabaseHas('users', ['id' => $u->id, 'firstname' => 'Kossi']);
    }

    public function test_retention_keeps_only_latest_backups(): void
    {
        BackupSetting::set('retention', '3');

        for ($i = 0; $i < 5; $i++) {
            $this->actingAs($this->admin())->post(route('backups.store'), ['formats' => ['json']]);
        }

        // Au plus 3 sauvegardes JSON conservées
        $this->assertLessThanOrEqual(3, Backup::where('format', 'json')->count());
    }
}
