@extends('layouts.teacher')
@section('title','Change Password')
@section('content')
<div class="tp-section-head"><div><h1>Change Password</h1><p class="tp-card-label">Use at least eight characters.</p></div></div>
<section class="tp-card"><form class="tp-form" method="POST" action="{{ route('teacher.password.update') }}">@csrf @method('PUT')
<div class="tp-field"><label class="tp-label" for="current-password">Current password <span class="tp-required" aria-hidden="true">*</span></label><input class="tp-control" id="current-password" type="password" name="current_password" required autocomplete="current-password">@error('current_password')<span class="tp-error" role="alert">{{ $message }}</span>@enderror</div>
<div class="tp-field"><label class="tp-label" for="password">New password <span class="tp-required" aria-hidden="true">*</span></label><input class="tp-control" id="password" type="password" name="password" required minlength="8" autocomplete="new-password"><span class="tp-help">Use at least eight characters.</span>@error('password')<span class="tp-error" role="alert">{{ $message }}</span>@enderror</div>
<div class="tp-field"><label class="tp-label" for="confirmation">Confirm new password <span class="tp-required" aria-hidden="true">*</span></label><input class="tp-control" id="confirmation" type="password" name="password_confirmation" required minlength="8" autocomplete="new-password"></div>
<div class="tp-form-actions"><a class="tp-btn" href="{{ route('teacher.profile.show') }}">Cancel</a><button class="tp-btn tp-btn-primary" type="submit">Change password</button></div></form></section>
@endsection
