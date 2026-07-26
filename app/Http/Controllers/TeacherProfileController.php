<?php

namespace App\Http\Controllers;

use App\Http\Requests\TeacherPasswordUpdateRequest;
use App\Http\Requests\TeacherProfileUpdateRequest;
use App\Models\CultivationAdmin;
use App\Services\TeacherDashboardService;
use App\Services\TeacherProfileService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class TeacherProfileController extends Controller
{
    public function __construct(private TeacherProfileService $profiles, private TeacherDashboardService $dashboard) {}
    public function show(): View { return $this->view('teacher.profile.show'); }
    public function edit(): View { return $this->view('teacher.profile.edit'); }
    public function passwordEdit(): View { return $this->view('teacher.profile.password'); }

    public function update(TeacherProfileUpdateRequest $request): RedirectResponse
    {
        $this->profiles->update($this->teacher(), $request->validated(), $request->file('avatar'));
        return redirect()->route('teacher.profile.show')->with('success', 'Profile updated successfully.');
    }
    public function passwordUpdate(TeacherPasswordUpdateRequest $request): RedirectResponse
    {
        $this->profiles->password($this->teacher(), $request->string('current_password')->toString(), $request->string('password')->toString());
        $request->session()->regenerate();
        return redirect()->route('teacher.profile.show')->with('success', 'Password changed successfully.');
    }
    private function view(string $name): View
    {
        $teacher=$this->teacher(); $teacher->load(['primaryClass','primarySection']);
        return view($name,['teacher'=>$teacher]+$this->dashboard->build($teacher));
    }
    private function teacher(): CultivationAdmin { /** @var CultivationAdmin $t */ $t=Auth::guard('teacher')->user(); return $t; }
}
