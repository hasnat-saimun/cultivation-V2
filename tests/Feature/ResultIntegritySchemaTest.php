<?php

namespace Tests\Feature;

use App\Models\MarksScopeState;
use App\Models\ResultLifecycleEvent;
use App\Models\ResultPublish;
use Illuminate\Database\QueryException;
use Illuminate\Database\Eloquent\MassAssignmentException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use LogicException;
use Tests\TestCase;

class ResultIntegritySchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_integrity_preflight_passes_read_only_on_clean_schema(): void
    {
        $before = [
            'marks' => DB::table('marksheets')->count(),
            'publications' => DB::table('result_publishes')->count(),
        ];

        $this->assertSame(0, Artisan::call('result-engine:integrity-preflight', ['--json' => true]));
        $this->assertStringContainsString('"status": "PASS"', Artisan::output());
        $this->assertSame($before['marks'], DB::table('marksheets')->count());
        $this->assertSame($before['publications'], DB::table('result_publishes')->count());
    }

    public function test_marks_identity_rejects_exact_and_class_wide_duplicates_but_allows_distinct_scopes(): void
    {
        $base = [
            'studentId' => '1', 'sessionId' => '1', 'classId' => '1', 'groupId' => '1',
            'examId' => '1', 'subjectId' => '1', 'created_at' => now(), 'updated_at' => now(),
        ];
        DB::table('marksheets')->insert($base);
        $this->expectDuplicate(fn () => DB::table('marksheets')->insert($base));

        DB::table('marksheets')->insert(array_replace($base, ['sessionId' => '2']));
        DB::table('marksheets')->insert(array_replace($base, ['groupId' => '2']));
        DB::table('marksheets')->insert(array_replace($base, ['groupId' => null]));
        $this->expectDuplicate(fn () => DB::table('marksheets')->insert(array_replace($base, ['groupId' => null])));

        $this->assertSame(
            ['class', 'section:1', 'section:2'],
            DB::table('marksheets')->pluck('normalizedGroupScope')->unique()->sort()->values()->all()
        );
    }

    public function test_publication_and_scope_state_uniqueness_use_normalized_group_scope(): void
    {
        $publication = [
            'examId' => '1', 'sessionId' => '1', 'classId' => '1', 'groupId' => null,
            'status' => 'published', 'revision' => 1, 'legacyImported' => false,
            'created_at' => now(), 'updated_at' => now(),
        ];
        DB::table('result_publishes')->insert($publication);
        $this->expectDuplicate(fn () => DB::table('result_publishes')->insert($publication));
        DB::table('result_publishes')->insert(array_replace($publication, ['groupId' => '1']));

        $scope = [
            'sessionId' => '1', 'classId' => '1', 'groupId' => null, 'examId' => '1',
            'subjectId' => '1', 'status' => 'draft', 'revision' => 1,
            'created_at' => now(), 'updated_at' => now(),
        ];
        DB::table('marks_scope_states')->insert($scope);
        $this->expectDuplicate(fn () => DB::table('marks_scope_states')->insert($scope));
    }

    public function test_schema_contract_and_model_defaults_are_available_without_lifecycle_behavior(): void
    {
        foreach (['normalizedGroupScope'] as $column) {
            $this->assertTrue(Schema::hasColumn('marksheets', $column));
            $this->assertTrue(Schema::hasColumn('result_publishes', $column));
            $this->assertTrue(Schema::hasColumn('marks_scope_states', $column));
        }
        $this->assertTrue(Schema::hasTable('result_lifecycle_events'));

        $publication = new ResultPublish();
        $this->assertSame(ResultPublish::STATUS_PUBLISHED, $publication->status);
        $this->assertSame(1, $publication->revision);
        $this->assertFalse($publication->legacyImported);
        $this->assertSame(MarksScopeState::STATUS_DRAFT, 'draft');
    }

    public function test_lifecycle_event_model_is_append_only_for_normal_model_operations(): void
    {
        $event = new ResultLifecycleEvent();
        $event->forceFill([
            'event_uuid' => (string) Str::uuid(),
            'action' => 'draft_marks_created',
            'entity_type' => 'marks_scope',
        ])->save();

        try {
            $event->update(['action' => 'draft_marks_updated']);
            $this->fail('Mass assignment must be blocked.');
        } catch (MassAssignmentException) {
            $this->assertSame('draft_marks_created', $event->fresh()->action);
        }

        $this->expectException(LogicException::class);
        $event->forceFill(['action' => 'draft_marks_updated'])->save();
    }

    public function test_lifecycle_sensitive_fields_are_not_mass_assignable(): void
    {
        $scope = new MarksScopeState([
            'sessionId' => '1', 'classId' => '1', 'examId' => '1', 'subjectId' => '1',
            'status' => 'confirmed', 'revision' => 99, 'confirmed_by' => 7,
        ]);
        $this->assertNull($scope->getAttribute('status'));
        $this->assertNull($scope->getAttribute('revision'));
        $this->assertNull($scope->getAttribute('confirmed_by'));

        $publication = new ResultPublish([
            'examId' => '1', 'sessionId' => '1', 'classId' => '1',
            'status' => 'unpublished', 'revision' => 99, 'legacyImported' => true,
        ]);
        $this->assertSame(ResultPublish::STATUS_PUBLISHED, $publication->status);
        $this->assertSame(1, $publication->revision);
        $this->assertFalse($publication->legacyImported);
    }

    private function expectDuplicate(callable $operation): void
    {
        try {
            $operation();
            $this->fail('Expected the database unique constraint to reject a duplicate.');
        } catch (QueryException $exception) {
            $this->assertStringContainsString('23000', (string) $exception->getCode());
        }
    }
}
