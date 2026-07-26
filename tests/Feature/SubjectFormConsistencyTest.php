<?php

namespace Tests\Feature;

use App\Models\classManage;
use App\Models\CultivationAdmin;
use App\Models\Subject;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SubjectFormConsistencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_page_contains_related_class_field(): void
    {
        $admin = $this->createAdmin();
        $class = $this->createClass('Class 8');

        $response = $this->withSession(['cultivationAdmin' => $admin->id])
            ->get(route('adminModernAcademicSubjectsCreate'));

        $response->assertOk();
        $response->assertSee('name="classId"', false);
        $response->assertSee('Assign Class *');
        $response->assertSee('value="'.$class->id.'"', false);
    }

    public function test_edit_page_contains_related_class_field(): void
    {
        $admin = $this->createAdmin();
        $class = $this->createClass('Class 9');
        $subject = $this->createSubject('History', 'Main', (string) $class->id);

        $response = $this->withSession(['cultivationAdmin' => $admin->id])
            ->get(route('adminModernAcademicSubjectsEdit', ['itemId' => $subject->id]));

        $response->assertOk();
        $response->assertSee('name="classId"', false);
        $response->assertSee('Assign Class *');
        $response->assertSee('value="'.$class->id.'"', false);
    }

    public function test_existing_related_class_is_preselected(): void
    {
        $admin = $this->createAdmin();
        $class = $this->createClass('Class 10');
        $subject = $this->createSubject('Geography', 'Main', (string) $class->id);

        $response = $this->withSession(['cultivationAdmin' => $admin->id])
            ->get(route('adminModernAcademicSubjectsEdit', ['itemId' => $subject->id]));

        $response->assertOk();
        $response->assertSee('option value="'.$class->id.'" selected', false);
    }

    public function test_changing_related_class_persists_and_reload_shows_updated_value(): void
    {
        $admin = $this->createAdmin();
        $oldClass = $this->createClass('Class 6');
        $newClass = $this->createClass('Class 7');
        $subject = $this->createSubject('Bangla', 'Main', (string) $oldClass->id, 70, 20, 10);

        $subjectCountBefore = Subject::count();

        $response = $this->withSession(['cultivationAdmin' => $admin->id])
            ->post(route('updateSubject'), [
                'itemId' => $subject->id,
                'subjectName' => $subject->subjectName,
                'subjectType' => $subject->subjectType,
                'classId' => $newClass->id,
                'cqValue' => $subject->CQ,
                'mcqValue' => $subject->MCQ,
                'practicalValue' => $subject->Practical,
            ]);

        $response->assertSessionHas('success');

        $subject->refresh();
        $this->assertSame((string) $newClass->id, (string) $subject->assign_class);
        $this->assertSame('Bangla', $subject->subjectName);
        $this->assertSame('Main', $subject->subjectType);
        $this->assertSame(70.0, (float) $subject->CQ);
        $this->assertSame(20.0, (float) $subject->MCQ);
        $this->assertSame(10.0, (float) $subject->Practical);
        $this->assertSame($subjectCountBefore, Subject::count());

        $reload = $this->withSession(['cultivationAdmin' => $admin->id])
            ->get(route('adminModernAcademicSubjectsEdit', ['itemId' => $subject->id]));

        $reload->assertOk();
        $reload->assertSee('option value="'.$newClass->id.'" selected', false);
        $reload->assertDontSee('option value="'.$oldClass->id.'" selected', false);
    }

    public function test_create_and_update_class_validation_is_consistent(): void
    {
        $admin = $this->createAdmin();
        $subject = $this->createSubject('Civics', 'Main', '0');

        $createResponse = $this->withSession(['cultivationAdmin' => $admin->id])
            ->from(route('adminModernAcademicSubjectsCreate'))
            ->post(route('confirmSubject'), [
                'subjectName' => 'New Subject',
                'subjectType' => 'Main',
                'classId' => 999999,
            ]);

        $createResponse->assertRedirect(route('adminModernAcademicSubjectsCreate'));
        $createResponse->assertSessionHasErrors('classId');

        $updateResponse = $this->withSession(['cultivationAdmin' => $admin->id])
            ->from(route('adminModernAcademicSubjectsEdit', ['itemId' => $subject->id]))
            ->post(route('updateSubject'), [
                'itemId' => $subject->id,
                'subjectName' => $subject->subjectName,
                'subjectType' => $subject->subjectType,
                'classId' => 999999,
            ]);

        $updateResponse->assertRedirect(route('adminModernAcademicSubjectsEdit', ['itemId' => $subject->id]));
        $updateResponse->assertSessionHasErrors('classId');
    }

    private function createAdmin(): CultivationAdmin
    {
        $admin = new CultivationAdmin();
        $admin->adminName = 'Subject Admin';
        $admin->adminUser = 'subject_admin_'.uniqid();
        $admin->userType = CultivationAdmin::ROLE_GENERAL;
        $admin->loginPassword = Hash::make('secret123');
        $admin->adminMobile = '017'.str_pad((string) random_int(0, 99999999), 8, '0', STR_PAD_LEFT);
        $admin->adminMail = uniqid('subject_admin_', true).'@example.test';
        $admin->save();

        return $admin;
    }

    private function createClass(string $name): classManage
    {
        $class = new classManage();
        $class->className = $name;
        $class->save();

        return $class;
    }

    private function createSubject(string $name, string $type, string $assignClass, ?float $cq = null, ?float $mcq = null, ?float $practical = null): Subject
    {
        $subject = new Subject();
        $subject->subjectName = $name;
        $subject->alias = strtolower(str_replace(' ', '_', $name));
        $subject->subjectType = $type;
        $subject->assign_class = $assignClass;
        $subject->CQ = $cq;
        $subject->MCQ = $mcq;
        $subject->Practical = $practical;
        $subject->save();

        return $subject;
    }
}