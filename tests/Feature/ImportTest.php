<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;
use Tests\TestCase;

class ImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_csv_preview_does_not_write_and_commit_imports_all_students(): void
    {
        $classroom = AcademicYear::create(['name' => 'Curso'])->classrooms()->create(['name' => 'Clase']);
        $admin = User::factory()->create(['role' => 'admin']);
        $csv = "name;email\nAne;ane@example.test\nIker;iker@example.test\n";
        $this->actingAs($admin)->postJson('/imports', ['kind' => 'student', 'classroom_id' => $classroom->id, 'commit' => false, 'file' => UploadedFile::fake()->createWithContent('students.csv', $csv)])->assertOk()->assertJsonPath('count', 2)->assertJsonPath('committed', false);
        $this->assertDatabaseCount('users', 1);

        $this->postJson('/imports', ['kind' => 'student', 'classroom_id' => $classroom->id, 'commit' => true, 'file' => UploadedFile::fake()->createWithContent('students.csv', $csv)])->assertOk()->assertJsonPath('committed', true);

        $this->assertDatabaseHas('users', ['name' => 'Ane', 'email' => 'ane@example.test', 'role' => 'student', 'classroom_id' => $classroom->id]);
        $this->assertDatabaseHas('users', ['name' => 'Iker', 'email' => 'iker@example.test', 'role' => 'student', 'classroom_id' => $classroom->id]);
        $this->assertDatabaseHas('audit_events', ['action' => 'import.student']);
    }

    public function test_student_import_requires_class_in_preview_and_commit(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        foreach ([false, true] as $commit) {
            $this->actingAs($admin)->postJson('/imports', ['kind' => 'student', 'commit' => $commit, 'file' => UploadedFile::fake()->createWithContent('students.csv', "name,email\nAne,ane@example.test\n")])->assertUnprocessable()->assertJsonValidationErrors('classroom_id');
        }
        $this->assertDatabaseCount('users', 1);
    }

    public function test_invalid_row_rejects_entire_import_and_reports_its_line(): void
    {
        $classroom = AcademicYear::create(['name' => 'Curso'])->classrooms()->create(['name' => 'Clase']);
        $admin = User::factory()->create(['role' => 'admin']);
        $file = UploadedFile::fake()->createWithContent('students.csv', "name,email\nAne,ane@example.test\nIker,not-an-email\n");
        $this->actingAs($admin)->postJson('/imports', ['kind' => 'student', 'classroom_id' => $classroom->id, 'commit' => true, 'file' => $file])->assertOk()->assertJsonPath('committed', false)->assertJsonPath('errors.0.row', 3);
        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('audit_events', 0);
    }

    public function test_excel_import_reads_students_and_preserves_names(): void
    {
        $classroom = AcademicYear::create(['name' => 'Curso'])->classrooms()->create(['name' => 'Clase']);
        $admin = User::factory()->create(['role' => 'admin']);
        $path = tempnam(sys_get_temp_dir(), 'erronk2d-xlsx-');
        $writer = new Writer;
        $writer->openToFile($path);
        $writer->addRow(Row::fromValues(['name', 'email']));
        $writer->addRow(Row::fromValues(['Ane Etxeberria', 'ane@example.test']));
        $writer->close();
        try {
            $this->actingAs($admin)->postJson('/imports', ['kind' => 'student', 'classroom_id' => $classroom->id, 'commit' => true, 'file' => new UploadedFile($path, 'students.xlsx', null, null, true)])
                ->assertOk()->assertJsonPath('committed', true);
            $this->assertDatabaseHas('users', ['name' => 'Ane Etxeberria', 'email' => 'ane@example.test']);
        } finally {
            unlink($path);
        }
    }

    public function test_invalid_excel_structure_returns_validation_error_without_writes(): void
    {
        $classroom = AcademicYear::create(['name' => 'Curso'])->classrooms()->create(['name' => 'Clase']);
        $admin = User::factory()->create(['role' => 'admin']);
        $path = tempnam(sys_get_temp_dir(), 'erronk2d-invalid-');
        $zip = new \ZipArchive;
        $zip->open($path, \ZipArchive::OVERWRITE);
        $zip->addFromString('wrong.txt', 'This is not a workbook');
        $zip->close();
        try {
            $this->actingAs($admin)->postJson('/imports', ['kind' => 'student', 'classroom_id' => $classroom->id, 'commit' => true, 'file' => new UploadedFile($path, 'students.xlsx', null, null, true)])
                ->assertUnprocessable()->assertJsonValidationErrors('file');
            $this->assertDatabaseCount('users', 1);
        } finally {
            unlink($path);
        }
    }

    public function test_email_duplicate_with_different_case_is_reported_before_commit(): void
    {
        $classroom = AcademicYear::create(['name' => 'Curso'])->classrooms()->create(['name' => 'Clase']);
        $admin = User::factory()->create(['role' => 'admin', 'email' => 'existing@example.test']);
        $file = UploadedFile::fake()->createWithContent('students.csv', "name,email\nDuplicado,EXISTING@example.test\n");
        $this->actingAs($admin)->postJson('/imports', ['kind' => 'student', 'classroom_id' => $classroom->id, 'commit' => true, 'file' => $file])
            ->assertOk()->assertJsonPath('committed', false)->assertJsonPath('errors.0.row', 2);
        $this->assertDatabaseCount('users', 1);
    }
}
