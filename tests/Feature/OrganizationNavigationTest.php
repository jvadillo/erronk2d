<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class OrganizationNavigationTest extends TestCase
{
    use RefreshDatabase;

    public static function sections(): array
    {
        return [
            'courses' => ['courses', 'Cursos académicos'],
            'cycles' => ['cycles', 'Ciclos'],
            'classrooms' => ['classrooms', 'Grupos'],
            'teachers' => ['teachers', 'Profesor'],
            'students' => ['students', 'Estudiante'],
            'modules' => ['modules', 'Módulos'],
            'rubrics' => ['rubrics', 'Biblioteca de rúbricas'],
            'registrations' => ['registrations', 'Solicitudes'],
        ];
    }

    #[DataProvider('sections')]
    public function test_administrator_can_open_each_section_directly(string $section, string $title): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get('/setup/'.$section)->assertInertia(fn (Assert $page) => $page
            ->component('Setup')->where('section', $section)->where('title', $title)
            ->has('setupNavigation', 8)
            ->where('setupNavigation.0.section', 'courses')
            ->where('setupNavigation.1.section', 'cycles')
            ->where('setupNavigation.2.section', 'modules')
            ->where('setupNavigation.3.section', 'classrooms')
            ->where('setupNavigation.4.label', 'Profesores')
            ->where('setupNavigation.5.label', 'Estudiantes')
            ->where('setupNavigation.6.label', 'Rúbricas')
            ->where('setupNavigation.7.label', 'Solicitudes'));
    }

    #[DataProvider('sections')]
    public function test_student_cannot_bypass_organization_restriction_through_section_url(string $section, string $title): void
    {
        $student = User::factory()->create();

        $this->actingAs($student)->get('/setup/'.$section)->assertForbidden();
    }

    public function test_old_organization_url_redirects_to_courses_and_unknown_section_is_not_found(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get('/setup')->assertRedirect('/setup/courses');
        $this->get('/setup/missing')->assertNotFound();
    }

    public function test_teacher_can_consult_sections_but_cannot_open_registration_requests(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $this->actingAs($teacher);

        foreach (['classrooms', 'students', 'rubrics'] as $section) {
            $this->get('/setup/'.$section)->assertInertia(fn (Assert $page) => $page
                ->where('section', $section)->has('setupNavigation', 3)
                ->where('setupNavigation.2.section', 'rubrics')->has('registrations', 0));
        }
        foreach (['courses', 'cycles', 'modules', 'teachers', 'registrations'] as $section) {
            $this->get('/setup/'.$section)->assertForbidden();
        }
    }

    public function test_guest_sections_require_login_and_students_have_no_organization_navigation(): void
    {
        $this->get('/setup/students')->assertRedirect('/login');

        $student = User::factory()->create();
        $this->actingAs($student)->get('/')->assertInertia(fn (Assert $page) => $page->has('setupNavigation', 0));
    }

    public function test_form_validation_returns_to_the_current_section(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->from('/setup/courses')->post('/setup/year', ['name' => '', 'periods' => []])
            ->assertRedirect('/setup/courses')->assertSessionHasErrors(['name']);

        $this->assertDatabaseCount('academic_years', 0);
    }
}
