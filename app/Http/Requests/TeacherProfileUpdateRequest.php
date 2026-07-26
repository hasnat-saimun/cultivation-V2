<?php

namespace App\Http\Requests;

use App\Models\CultivationAdmin;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TeacherProfileUpdateRequest extends FormRequest
{
    public function authorize(): bool { return $this->user('teacher')?->isTeacher() === true; }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'adminName' => trim((string) $this->input('adminName')),
            'adminMail' => mb_strtolower(trim((string) $this->input('adminMail'))),
            'adminMobile' => preg_replace('/[\s-]+/', '', trim((string) $this->input('adminMobile'))),
        ]);
    }

    public function rules(): array
    {
        $id = $this->user('teacher')->id;
        return [
            'adminName' => ['required', 'string', 'max:100'],
            'adminMail' => ['required', 'email:rfc', 'max:255', Rule::unique('cultivation_admins', 'adminMail')->ignore($id)],
            'adminMobile' => ['required', 'regex:/^\+?[0-9]{10,15}$/', Rule::unique('cultivation_admins', 'adminMobile')->ignore($id)],
            'avatar' => ['nullable', 'image', 'mimes:jpeg,png,webp', 'max:2048', 'dimensions:min_width=80,min_height=80,max_width=3000,max_height=3000'],
        ];
    }
}
