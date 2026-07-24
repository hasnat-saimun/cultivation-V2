<?php

namespace Tests\Feature;

use App\Exceptions\ResultLifecycleException;
use App\Models\Marksheet;
use App\Models\ResultLifecycleEvent;
use App\Services\ResultMarksDraftService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesResultLifecycleScope;
use Tests\TestCase;

class ResultLifecycleConcurrencyTest extends TestCase
{
    use RefreshDatabase, CreatesResultLifecycleScope;

    public function test_two_updates_with_same_revision_commit_exactly_once(): void
    {
        $data = $this->lifecycleScope();
        $actor = $this->lifecycleActor();
        $service = app(ResultMarksDraftService::class);
        $service->save($this->lifecycleInput($data, 70), $actor, null, true);
        $update = $this->lifecycleInput($data, 80) + ['scope_revision' => 2];
        $service->save($update, $actor);

        try {
            $service->save($update, $actor);
            $this->fail('The repeated stale update must not commit.');
        } catch (ResultLifecycleException $exception) {
            $this->assertSame('ScopeRevisionConflict', $exception->failure);
        }

        $this->assertSame(80.0, (float) Marksheet::firstOrFail()->subjectMarks);
        $this->assertSame(2, ResultLifecycleEvent::count());
    }
}
