<?php

namespace App\Services;

use App\Models\CultivationAdmin;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class TeacherProfileService
{
    public function update(CultivationAdmin $teacher, array $data, ?UploadedFile $avatar = null): array
    {
        $changed = [];
        foreach (['adminName', 'adminMail', 'adminMobile'] as $field) {
            if ((string) $teacher->{$field} !== (string) $data[$field]) {
                $teacher->{$field} = $data[$field]; $changed[] = $field;
            }
        }
        $newName = null;
        if ($avatar) {
            $newName = 'teacher-'.$teacher->id.'-'.Str::uuid().'.'.$avatar->extension();
            $avatar->move(public_path('upload/image/admin'), $newName);
            $oldName = (string) $teacher->avatar;
            $teacher->avatar = $newName; $changed[] = 'avatar';
        }
        try { $teacher->save(); }
        catch (\Throwable $e) {
            if ($newName) @unlink(public_path('upload/image/admin/'.$newName));
            throw $e;
        }
        if ($newName && isset($oldName) && str_starts_with($oldName, 'teacher-'.$teacher->id.'-')
            && basename($oldName) === $oldName && is_file(public_path('upload/image/admin/'.$oldName))) {
            @unlink(public_path('upload/image/admin/'.$oldName));
        }
        Log::info('Teacher profile updated', ['teacher_id'=>$teacher->id, 'action'=>'profile_updated', 'changed_fields'=>$changed]);
        return $changed;
    }

    public function password(CultivationAdmin $teacher, string $current, string $password): void
    {
        if (!Hash::check($current, (string) $teacher->loginPassword)) {
            Log::warning('Teacher password change rejected', ['teacher_id'=>$teacher->id, 'action'=>'password_change', 'category'=>'incorrect_current_password']);
            throw ValidationException::withMessages(['current_password'=>'The current password is incorrect.']);
        }
        $teacher->loginPassword = Hash::make($password);
        $teacher->save();
        Log::info('Teacher password changed', ['teacher_id'=>$teacher->id, 'action'=>'password_changed', 'changed_fields'=>['loginPassword']]);
    }
}
