<?php

namespace Tests\Feature;

use App\Exceptions\ResultLifecycleException;
use App\Models\CultivationAdmin;
use App\Models\ResultLifecycleEvent;
use App\Services\ResultLifecycleEventService;
use App\Services\ResultMarksDraftService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use OverflowException;
use Tests\Support\CreatesResultLifecycleScope;
use Tests\TestCase;

class ResultLifecycleSecurityTest extends TestCase
{
    use RefreshDatabase, CreatesResultLifecycleScope;

    public function test_cash_admin_cannot_save_draft_and_client_actor_fields_are_ignored(): void
    {
        $data = $this->lifecycleScope();
        $cash = $this->lifecycleActor(CultivationAdmin::ROLE_CASH);

        try {
            app(ResultMarksDraftService::class)->save($this->lifecycleInput($data), $cash, '127.0.0.1', true);
            $this->fail('Cash Admin must not mutate marks.');
        } catch (ResultLifecycleException $exception) {
            $this->assertSame(403, $exception->httpStatus);
        }

        $actor = $this->lifecycleActor();
        app(ResultMarksDraftService::class)->save(
            $this->lifecycleInput($data) + [
                'actor_id' => 999999,
                'actor_role' => 'super_admin',
                'status' => 'confirmed',
                'revision' => 999,
            ],
            $actor,
            '127.0.0.1',
            true,
        );
        $event = ResultLifecycleEvent::firstOrFail();
        $this->assertSame($actor->id, $event->actor_id);
        $this->assertSame('general_admin', $event->actor_role);
    }

    public function test_student_outside_authorized_scope_rejects_entire_batch(): void
    {
        $authorized = $this->lifecycleScope();
        $other = $this->lifecycleScope();
        $actor = $this->lifecycleActor();
        $input = $this->lifecycleInput($authorized);
        $input['studentId'][] = $other['students']->first()->id;
        $input['cqMarks'][] = 80;
        $input['mcqMarks'][] = '';
        $input['practical'][] = '';

        try {
            app(ResultMarksDraftService::class)->save($input, $actor, null, true);
            $this->fail('Cross-scope student substitution must fail.');
        } catch (ResultLifecycleException $exception) {
            $this->assertSame(403, $exception->httpStatus);
        }
        $this->assertSame(0, DB::table('marksheets')->count());
        $this->assertSame(0, ResultLifecycleEvent::count());
    }

    public function test_oversized_audit_payload_is_rejected_without_partial_event(): void
    {
        $this->expectException(OverflowException::class);
        try {
            app(ResultLifecycleEventService::class)->append(
                'draft_marks_updated',
                ['sessionId' => 1, 'classId' => 1, 'groupId' => 1, 'examId' => 1, 'subjectId' => 1],
                $this->lifecycleActor(),
                null,
                null,
                null,
                [['evidence' => str_repeat('x', ResultLifecycleEventService::MAX_PAYLOAD_BYTES)]],
            );
        } finally {
            $this->assertSame(0, ResultLifecycleEvent::count());
        }
    }

    public function test_get_requests_cannot_reach_lifecycle_mutations(): void
    {
        foreach ([
            '/marks/add/draft',
            '/marks/add/confirm-subject',
            '/marks/add/reopen-subject',
            '/result/final-publish/publish',
            '/result/final-publish/unpublish',
        ] as $uri) {
            $this->get($uri)->assertStatus(405);
        }
    }
}
