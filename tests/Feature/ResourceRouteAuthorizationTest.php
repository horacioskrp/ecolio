<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Subject;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Non-régression transverse : une permission de LECTURE ne doit jamais suffire
 * à créer, modifier ou supprimer sur les routes `Route::resource`.
 *
 * Ces routes n'étaient gardées que par un seul `can:view_*`, appliqué à tous les
 * verbes ; la protection ne tenait qu'aux FormRequests/policies écrites au cas par
 * cas, laissant plusieurs `destroy` ouverts.
 */
class ResourceRouteAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    /** Utilisateur porteur des seules permissions demandées. */
    private function userWith(string ...$permissions): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo($permissions);

        return $user;
    }

    public function test_view_classes_alone_cannot_delete_a_classroom(): void
    {
        $classroom = Classroom::create(['name' => '6ème A', 'code' => '6A', 'capacity' => 40, 'active' => true]);

        $this->actingAs($this->userWith('view_classes'))
            ->delete(route('classrooms.destroy', $classroom))
            ->assertForbidden();

        $this->assertDatabaseHas('classes', ['id' => $classroom->id]);
    }

    public function test_view_subjects_alone_cannot_create_a_subject(): void
    {
        $this->actingAs($this->userWith('view_subjects'))
            ->post(route('subjects.store'), ['name' => 'Physique', 'code' => 'PHY'])
            ->assertForbidden();

        $this->assertDatabaseMissing('subjects', ['code' => 'PHY']);
    }

    public function test_view_academic_years_alone_cannot_delete_a_year(): void
    {
        $year = AcademicYear::create([
            'year' => '2025-2026', 'start_date' => '2025-09-01',
            'end_date' => '2026-07-31', 'active' => true,
        ]);

        $this->actingAs($this->userWith('view_academic_years'))
            ->delete(route('academic-years.destroy', $year))
            ->assertForbidden();

        $this->assertDatabaseHas('academic_years', ['id' => $year->id]);
    }

    public function test_view_users_alone_cannot_delete_a_user(): void
    {
        $target = User::factory()->create();

        $this->actingAs($this->userWith('view_users'))
            ->delete(route('users.destroy', $target))
            ->assertForbidden();

        $this->assertDatabaseHas('users', ['id' => $target->id]);
    }

    public function test_view_absence_permissions_alone_cannot_create_a_request(): void
    {
        $this->actingAs($this->userWith('view_absence_permissions'))
            ->post(route('absence-permissions.store'), [])
            ->assertForbidden();
    }

    public function test_matching_permission_grants_access(): void
    {
        $subject = Subject::create(['name' => 'Chimie', 'code' => 'CHI']);

        $this->actingAs($this->userWith('view_subjects', 'delete_subjects'))
            ->delete(route('subjects.destroy', $subject))
            ->assertRedirect();

        $this->assertDatabaseMissing('subjects', ['id' => $subject->id]);
    }
}
