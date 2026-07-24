<?php

namespace Tests\Feature;

use App\Http\Controllers\MarksheetController;
use App\Models\ResultPublish;
use App\Services\ResultPublicationVisibilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Tests\Support\CreatesResultLifecycleScope;
use Tests\TestCase;

class ResultVisibilityTest extends TestCase
{
    use RefreshDatabase, CreatesResultLifecycleScope;

    public function test_absent_and_unpublished_are_hidden_while_exact_published_is_visible(): void
    {
        $data = $this->lifecycleScope();
        $visibility = app(ResultPublicationVisibilityService::class);
        $args = [$data['exam']->id, $data['session']->id, $data['class']->id, $data['section']->id];
        $this->assertFalse($visibility->isPublished(...$args));
        $publication = ResultPublish::create([
            'examId' => $data['exam']->id, 'sessionId' => $data['session']->id,
            'classId' => $data['class']->id, 'groupId' => $data['section']->id,
            'status' => 'published',
        ]);
        $this->assertTrue($visibility->isPublished(...$args));
        $publication->forceFill(['status' => 'unpublished'])->save();
        $this->assertFalse($visibility->isPublished(...$args));
    }

    public function test_legacy_class_wide_publication_remains_visible_but_normal_scope_does_not_leak_cross_section(): void
    {
        $data = $this->lifecycleScope();
        $visibility = app(ResultPublicationVisibilityService::class);
        $publication = new ResultPublish();
        $publication->forceFill([
            'examId' => $data['exam']->id, 'sessionId' => $data['session']->id,
            'classId' => $data['class']->id, 'groupId' => null,
            'status' => 'published', 'legacyImported' => true,
        ])->save();
        $this->assertTrue($visibility->isPublished(
            $data['exam']->id, $data['session']->id, $data['class']->id, $data['section']->id
        ));
        $publication->forceFill(['legacyImported' => false])->save();
        $this->assertFalse($visibility->isPublished(
            $data['exam']->id, $data['session']->id, $data['class']->id, $data['section']->id
        ));
        $this->assertTrue($visibility->isPublished(
            $data['exam']->id, $data['session']->id, $data['class']->id, null
        ));
    }

    public function test_authenticated_administrative_tabulation_remains_a_prepublication_preview(): void
    {
        [$data] = $this->confirmedLifecycleScope();
        $response = app(MarksheetController::class)->allMarksheet(Request::create('/marksheet/all', 'GET', [
            'examId' => $data['exam']->id,
            'sessionId' => $data['session']->id,
            'classId' => $data['class']->id,
            'sectionId' => $data['section']->id,
        ]));
        $this->assertInstanceOf(View::class, $response);
        $this->assertDatabaseCount('result_publishes', 0);
    }
}
