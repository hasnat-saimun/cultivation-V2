<?php

namespace Tests\Feature;

use Tests\TestCase;

class MarksBlankModalViewTest extends TestCase
{
    public function test_admin_marks_view_contains_blank_mark_modal_and_actions(): void
    {
        $view = file_get_contents(resource_path('views/result/get-marks.blade.php'));

        $this->assertIsString($view);
        $this->assertStringContainsString('blankMarksModal', $view);
        $this->assertStringContainsString('Blank marks detected', $view);
        $this->assertStringContainsString('Confirm Anyway', $view);
        $this->assertStringContainsString('Save as Draft', $view);
        $this->assertStringContainsString('Go Back', $view);
        $this->assertStringContainsString('submission_action', $view);
        $this->assertStringContainsString('confirm_blank_marks', $view);
    }

    public function test_teacher_workspace_contains_blank_mark_modal_and_actions(): void
    {
        $view = file_get_contents(resource_path('views/teacher/results/workspace.blade.php'));

        $this->assertIsString($view);
        $this->assertStringContainsString('teacherBlankMarksModal', $view);
        $this->assertStringContainsString('Blank marks detected', $view);
        $this->assertStringContainsString('Confirm Anyway', $view);
        $this->assertStringContainsString('Save as Draft', $view);
        $this->assertStringContainsString('Go Back', $view);
        $this->assertStringContainsString('teacher_submission_action', $view);
        $this->assertStringContainsString('teacher_confirm_blank_marks', $view);
        $this->assertStringContainsString('aria-hidden="true" hidden', $view);
        $this->assertStringContainsString('modalElement.hidden = false', $view);
        $this->assertStringContainsString('modalElement.hidden = true', $view);
        $this->assertStringNotContainsString('modalInstance.modal(', $view);
        $this->assertStringNotContainsString('data-dismiss="modal"', $view);
    }
}
