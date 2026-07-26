@extends('layouts.teacher')
@section('title','Edit Profile')
@section('content')
<div class="tp-section-head"><div><h1>Edit Profile</h1><p class="tp-card-label">Login email/mobile changes apply to future sign-ins.</p></div></div>
<section class="tp-card"><form class="tp-form" method="POST" action="{{ route('teacher.profile.update') }}" enctype="multipart/form-data">@csrf @method('PUT')
<div class="tp-field"><label class="tp-label" for="name">Display name <span class="tp-required" aria-hidden="true">*</span></label><input class="tp-control" id="name" name="adminName" required maxlength="100" value="{{ old('adminName',$teacher->adminName) }}">@error('adminName')<span class="tp-error" role="alert">{{ $message }}</span>@enderror</div>
<div class="tp-field"><label class="tp-label" for="email">Email <span class="tp-required" aria-hidden="true">*</span></label><input class="tp-control" id="email" type="email" name="adminMail" required value="{{ old('adminMail',$teacher->adminMail) }}">@error('adminMail')<span class="tp-error" role="alert">{{ $message }}</span>@enderror</div>
<div class="tp-field"><label class="tp-label" for="mobile">Mobile <span class="tp-required" aria-hidden="true">*</span></label><input class="tp-control" id="mobile" name="adminMobile" required value="{{ old('adminMobile',$teacher->adminMobile) }}">@error('adminMobile')<span class="tp-error" role="alert">{{ $message }}</span>@enderror</div>
<div class="tp-field"><label class="tp-label" for="avatar">Profile photo</label><input class="tp-control" id="avatar" type="file" name="avatar" accept="image/jpeg,image/png,image/webp"><span class="tp-help">JPEG, PNG or WebP; maximum 2 MB.</span>@error('avatar')<span class="tp-error" role="alert">{{ $message }}</span>@enderror</div>
<div class="tp-form-actions"><a class="tp-btn" href="{{ route('teacher.profile.show') }}">Cancel</a><button class="tp-btn tp-btn-primary" type="submit">Save profile</button></div></form></section>
@endsection
