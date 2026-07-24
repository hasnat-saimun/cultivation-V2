<?php

namespace Tests\Feature;

use App\Exceptions\ResultPublicationException;
use App\Models\CultivationAdmin;
use App\Models\Marksheet;
use App\Models\MarksScopeState;
use App\Models\ResultLifecycleEvent;
use App\Models\ResultPublish;
use App\Services\ResultPublishService;
use App\Services\ResultUnpublishService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesResultLifecycleScope;
use Tests\TestCase;

class ResultUnpublishTest extends TestCase
{
    use RefreshDatabase, CreatesResultLifecycleScope;

    public function test_general_admin_unpublish_retains_row_marks_confirmations_and_history(): void
    {
        foreach ([CultivationAdmin::ROLE_GENERAL] as $role) {
            [$data, , $input] = $this->confirmedLifecycleScope();
            $actor = $this->lifecycleActor($role);
            app(ResultPublishService::class)->publish($input, $actor);
            $mark = Marksheet::first()->getAttributes();
            $confirmation = MarksScopeState::first()->getAttributes();
            $result = app(ResultUnpublishService::class)->unpublish(
                $input + ['publication_revision' => 1, 'reason' => 'Correction required'],
                $actor,
            );

            $this->assertSame('unpublished', $result['publications'][0]['status']);
            $this->assertSame(2, $result['publications'][0]['revision']);
            $this->assertDatabaseCount('result_publishes', 1);
            $this->assertSame($mark, Marksheet::first()->getAttributes());
            $this->assertSame($confirmation, MarksScopeState::first()->getAttributes());
            $this->assertDatabaseHas('result_lifecycle_events', ['action' => 'result_published']);
            $this->assertDatabaseHas('result_lifecycle_events', ['action' => 'result_unpublished']);
        }
    }

    public function test_super_admin_can_unpublish(): void
    {
        [$data, , $input] = $this->confirmedLifecycleScope();
        $actor = $this->lifecycleActor(4);
        app(ResultPublishService::class)->publish($input, $actor);
        $result = app(ResultUnpublishService::class)->unpublish(
            $input + ['publication_revision' => 1, 'reason' => 'Correction required'],
            $actor,
        );
        $this->assertSame('unpublished', $result['publications'][0]['status']);
    }

    public function test_teacher_cash_blank_and_overlong_reason_are_denied(): void
    {
        [$data, $actor, $input] = $this->confirmedLifecycleScope();
        app(ResultPublishService::class)->publish($input, $actor);
        foreach ([CultivationAdmin::ROLE_TEACHER, CultivationAdmin::ROLE_CASH] as $role) {
            try {
                app(ResultUnpublishService::class)->unpublish(
                    $input + ['publication_revision' => 1, 'reason' => 'Fix'],
                    $this->lifecycleActor($role),
                );
                $this->fail('Role must not unpublish.');
            } catch (ResultPublicationException $exception) {
                $this->assertSame(403, $exception->httpStatus);
            }
        }
        foreach (['', str_repeat('x', 501)] as $reason) {
            try {
                app(ResultUnpublishService::class)->unpublish(
                    $input + ['publication_revision' => 1, 'reason' => $reason],
                    $actor,
                );
                $this->fail('Invalid reason must fail.');
            } catch (ResultPublicationException $exception) {
                $this->assertSame(422, $exception->httpStatus);
            }
        }
    }

    public function test_repeat_is_idempotent_does_not_overwrite_reason_and_stale_revision_conflicts(): void
    {
        [$data, $actor, $input] = $this->confirmedLifecycleScope();
        app(ResultPublishService::class)->publish($input, $actor);
        app(ResultUnpublishService::class)->unpublish(
            $input + ['publication_revision' => 1, 'reason' => 'Original reason'],
            $actor,
        );
        $eventCount = ResultLifecycleEvent::count();
        $repeat = app(ResultUnpublishService::class)->unpublish(
            $input + ['publication_revision' => 2, 'reason' => 'Different reason'],
            $actor,
        );
        $this->assertTrue($repeat['idempotent']);
        $this->assertSame('Original reason', ResultPublish::first()->unpublish_reason);
        $this->assertSame($eventCount, ResultLifecycleEvent::count());

        $this->expectException(ResultPublicationException::class);
        app(ResultUnpublishService::class)->unpublish(
            $input + ['publication_revision' => 1, 'reason' => 'Stale'],
            $actor,
        );
    }

    public function test_legacy_imported_publication_unpublishes_truthfully_without_fabricated_publish_event(): void
    {
        $data = $this->lifecycleScope();
        $actor = $this->lifecycleActor();
        $publication = new ResultPublish();
        $publication->forceFill([
            'examId' => $data['exam']->id,
            'sessionId' => $data['session']->id,
            'classId' => $data['class']->id,
            'groupId' => null,
            'status' => 'published',
            'revision' => 1,
            'legacyImported' => true,
        ])->save();
        $result = app(ResultUnpublishService::class)->unpublish([
            'examId' => $data['exam']->id,
            'sessionId' => $data['session']->id,
            'classId' => $data['class']->id,
            'groupId' => null,
            'exact_scope' => true,
            'publication_revision' => 1,
            'reason' => 'Legacy result correction',
        ], $actor);
        $this->assertSame('unpublished', $result['publications'][0]['status']);
        $this->assertSame(0, ResultLifecycleEvent::where('action', 'result_published')->count());
        $this->assertSame(1, ResultLifecycleEvent::where('action', 'result_unpublished')->count());
        $this->assertTrue((bool) $publication->fresh()->legacyImported);
    }
}
