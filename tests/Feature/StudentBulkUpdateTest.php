<?php

namespace Tests\Feature;

use App\Models\CultivationAdmin;
use App\Models\newAdmission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class StudentBulkUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_single_student_bulk_update_persists_changes(): void
    {
        $admin = $this->createAdmin();
        $student = $this->createStudent();

        $response = $this->withSession(['cultivationAdmin' => $admin->id])->post(route('studentBulkUpdateStore'), [
            'students' => [[
                'id' => $student->id,
                'phone' => '01710000001',
                'gurdianMobile' => '01810000001',
                'address' => 'Updated Address One',
            ]],
        ]);

        $response->assertRedirect(route('studentBulkUpdate'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('new_admissions', [
            'id' => $student->id,
            'phone' => '01710000001',
            'gurdianMobile' => '01810000001',
            'address' => 'Updated Address One',
        ]);
    }

    public function test_multiple_student_bulk_update_persists_all_changes(): void
    {
        $admin = $this->createAdmin();
        $studentA = $this->createStudent(['stdId' => 2001]);
        $studentB = $this->createStudent(['stdId' => 2002]);

        $response = $this->withSession(['cultivationAdmin' => $admin->id])->post(route('studentBulkUpdateStore'), [
            'students' => [
                [
                    'id' => $studentA->id,
                    'phone' => '01710000002',
                    'gurdianMobile' => '01810000002',
                    'address' => 'Updated Address A',
                ],
                [
                    'id' => $studentB->id,
                    'phone' => '01710000003',
                    'gurdianMobile' => '01810000003',
                    'address' => 'Updated Address B',
                ],
            ],
        ]);

        $response->assertRedirect(route('studentBulkUpdate'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('new_admissions', [
            'id' => $studentA->id,
            'phone' => '01710000002',
            'gurdianMobile' => '01810000002',
            'address' => 'Updated Address A',
        ]);
        $this->assertDatabaseHas('new_admissions', [
            'id' => $studentB->id,
            'phone' => '01710000003',
            'gurdianMobile' => '01810000003',
            'address' => 'Updated Address B',
        ]);
    }

    public function test_unchanged_payload_does_not_report_success(): void
    {
        $admin = $this->createAdmin();
        $student = $this->createStudent([
            'phone' => '01712345678',
            'gurdianMobile' => '01812345678',
            'address' => 'No Change Address',
        ]);

        $response = $this->withSession(['cultivationAdmin' => $admin->id])->post(route('studentBulkUpdateStore'), [
            'students' => [[
                'id' => $student->id,
                'phone' => '01712345678',
                'gurdianMobile' => '01812345678',
                'address' => 'No Change Address',
            ]],
        ]);

        $response->assertRedirect(route('studentBulkUpdate'));
        $response->assertSessionHas('error');
        $response->assertSessionMissing('success');
    }

    public function test_validation_failure_rejects_invalid_payload(): void
    {
        $admin = $this->createAdmin();

        $response = $this->withSession(['cultivationAdmin' => $admin->id])->from(route('studentBulkUpdate'))->post(route('studentBulkUpdateStore'), [
            'students' => [[
                'phone' => '01710000001',
            ]],
        ]);

        $response->assertRedirect(route('studentBulkUpdate'));
        $response->assertSessionHasErrors('students.0.id');
    }

    public function test_invalid_student_id_is_rejected(): void
    {
        $admin = $this->createAdmin();

        $response = $this->withSession(['cultivationAdmin' => $admin->id])->from(route('studentBulkUpdate'))->post(route('studentBulkUpdateStore'), [
            'students' => [[
                'id' => 999999,
                'phone' => '01710000009',
            ]],
        ]);

        $response->assertRedirect(route('studentBulkUpdate'));
        $response->assertSessionHasErrors('students.0.id');
    }

    public function test_unauthorized_access_is_redirected_to_login(): void
    {
        $student = $this->createStudent();

        $response = $this->post(route('studentBulkUpdateStore'), [
            'students' => [[
                'id' => $student->id,
                'phone' => '01710000011',
            ]],
        ]);

        $response->assertRedirect(route('adminLogin'));
    }

    public function test_default_connection_is_used_for_update_path(): void
    {
        $admin = $this->createAdmin();
        $student = $this->createStudent();

        $defaultConnection = DB::getDefaultConnection();
        $modelConnection = $student->getConnectionName() ?: $defaultConnection;

        $response = $this->withSession(['cultivationAdmin' => $admin->id])->post(route('studentBulkUpdateStore'), [
            'students' => [[
                'id' => $student->id,
                'phone' => '01710000021',
            ]],
        ]);

        $response->assertSessionHas('success');
        $this->assertSame($defaultConnection, $modelConnection);
        $this->assertSame('01710000021', (string) DB::connection($defaultConnection)->table('new_admissions')->where('id', $student->id)->value('phone'));
    }

    public function test_success_is_returned_only_when_at_least_one_row_is_persisted(): void
    {
        $admin = $this->createAdmin();
        $studentA = $this->createStudent(['phone' => '01720000001']);
        $studentB = $this->createStudent(['phone' => '01720000002']);

        $response = $this->withSession(['cultivationAdmin' => $admin->id])->post(route('studentBulkUpdateStore'), [
            'students' => [
                ['id' => $studentA->id, 'phone' => '01720000001'],
                ['id' => $studentB->id, 'phone' => '01729999999'],
            ],
        ]);

        $response->assertRedirect(route('studentBulkUpdate'));
        $response->assertSessionHas('success');
        $response->assertSessionMissing('error');

        $this->assertSame('01720000001', (string) newAdmission::query()->findOrFail($studentA->id)->phone);
        $this->assertSame('01729999999', (string) newAdmission::query()->findOrFail($studentB->id)->phone);
    }

    public function test_updated_values_survive_page_reload(): void
    {
        $admin = $this->createAdmin();
        $student = $this->createStudent(['fullName' => 'Reload', 'sureName' => 'Check']);

        $this->withSession(['cultivationAdmin' => $admin->id])->post(route('studentBulkUpdateStore'), [
            'students' => [[
                'id' => $student->id,
                'phone' => '01710000031',
                'gurdianMobile' => '01810000031',
                'address' => 'Persist After Reload',
            ]],
        ])->assertSessionHas('success');

        $reload = $this->withSession(['cultivationAdmin' => $admin->id])->get(route('studentBulkUpdate'));
        $reload->assertOk();
        $reload->assertSee('01710000031');
        $reload->assertSee('01810000031');
        $reload->assertSee('Persist After Reload');
    }

    public function test_transaction_rolls_back_all_rows_when_one_row_throws(): void
    {
        $this->withoutExceptionHandling();

        $admin = $this->createAdmin();
        $studentA = $this->createStudent(['phone' => '01730000001']);
        $studentB = $this->createStudent(['phone' => '01730000002']);

        newAdmission::saving(function (newAdmission $model) use ($studentB): void {
            if ((int) $model->id === (int) $studentB->id) {
                throw new \RuntimeException('forced rollback');
            }
        });

        try {
            $this->withSession(['cultivationAdmin' => $admin->id])->post(route('studentBulkUpdateStore'), [
                'students' => [
                    ['id' => $studentA->id, 'phone' => '01739999991'],
                    ['id' => $studentB->id, 'phone' => '01739999992'],
                ],
            ]);
            $this->fail('Expected transaction failure to bubble up.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('forced rollback', $exception->getMessage());
        } finally {
            newAdmission::flushEventListeners();
        }

        $this->assertSame('01730000001', (string) newAdmission::query()->findOrFail($studentA->id)->phone);
        $this->assertSame('01730000002', (string) newAdmission::query()->findOrFail($studentB->id)->phone);
    }

    private function createAdmin(): CultivationAdmin
    {
        $admin = new CultivationAdmin();
        $admin->adminName = 'Bulk Admin';
        $admin->adminUser = 'bulk_admin_'.uniqid();
        $admin->userType = CultivationAdmin::ROLE_GENERAL;
        $admin->loginPassword = Hash::make('secret123');
        $admin->adminMobile = '017'.str_pad((string) random_int(0, 99999999), 8, '0', STR_PAD_LEFT);
        $admin->adminMail = uniqid('bulk_admin_', true).'@example.test';
        $admin->save();

        return $admin;
    }

    private function createStudent(array $overrides = []): newAdmission
    {
        return newAdmission::create(array_merge([
            'stdId' => random_int(1000, 9999),
            'fullName' => 'Student',
            'sureName' => 'One',
            'phone' => '01700000000',
            'gurdianMobile' => '01800000000',
            'address' => 'Original Address',
        ], $overrides));
    }
}
