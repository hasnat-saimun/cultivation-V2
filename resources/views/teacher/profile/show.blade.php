@extends('layouts.teacher')
@section('title','Profile & Settings')
@section('content')
<div class="tp-section-head"><div><h1>Profile &amp; Settings</h1><p class="tp-card-label">Your teacher account</p></div>
<div><a href="{{ route('teacher.profile.edit') }}">Edit profile</a> · <a href="{{ route('teacher.password.edit') }}">Change password</a></div></div>
<div class="tp-panel-grid"><section class="tp-card">
<div class="tp-avatar" style="width:88px;height:88px;font-size:1.5rem">@if($avatarUrl)<img src="{{ $avatarUrl }}" alt="">@else{{ $avatarInitials }}@endif</div>
<h2>{{ $teacher->adminName }}</h2><p>Teacher/account ID: {{ $teacher->id }}</p><p>Username: {{ $teacher->adminUser }}</p>
<p>Email: {{ $teacher->adminMail }}</p><p>Mobile: {{ $teacher->adminMobile }}</p>
</section><section class="tp-card"><h2>Account information</h2><p>Account type: {{ $teacher->user_type_label }}</p>
<p>Primary class: {{ $teacher->primaryClass?->className ?: 'Not assigned' }}</p><p>Primary section: {{ $teacher->primarySection?->section ?: 'Not assigned' }}</p>
<p>Created: {{ $teacher->created_at?->format('d M Y') ?: '—' }}</p></section></div>
@endsection
