@php
    $cultivationAdmin = \App\Models\CultivationAdmin::find(session('cultivationAdmin'));
    if(!empty($cultivationAdmin)):
        $adminName      =   $cultivationAdmin->adminName;
        $adminId        =   $cultivationAdmin->id;
        $adminEmail     =   $cultivationAdmin->adminMail;
        $userId         =   $cultivationAdmin->adminUser;
        $adminMobile    =   $cultivationAdmin->adminMobile;
        $adminType      =   $cultivationAdmin->adminType;
        $adminAvatar    =   $cultivationAdmin->avatar ?? '';
    else:
        $adminId        =   null;
        $adminName      =   "Abu Yousuf";
        $adminEmail     =   "cultivation@virtualitprofessional.com";
        $userId         =   "Spark Coder";
        $adminMobile    =   "01678909091";
        $adminType      =   "Admin";
        $adminAvatar    =   "";
    endif;
@endphp
@extends('cultivation.include')
@section('backTitle')
Admin Profile
@endsection
@section('backIndex')
@push('styles')
<style>
    .profile-header { background: linear-gradient(90deg,#f8f9fa,#eef2f7); border-radius:.5rem; }
    .profile-avatar { width: 72px; height: 72px; border-radius: 50%; object-fit: cover; box-shadow: 0 0 0 3px #fff; }
    .form-hint { font-size:.85rem; color:#6c757d; }
    .btn-soft { background:#f1f3f5; border-color:#f1f3f5; color:#495057; }
    .btn-soft:hover { background:#e9ecef; border-color:#e9ecef; }
    .section-title { font-weight:600; }
    .divider { height:1px; background:#e9ecef; margin: 1rem 0; }
</style>
@endpush
<!-- Dashboard summery Start Here -->
<div class="row gutters-20 mb-4">
    <div class="col-md-10 col-12 mx-auto">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-user"></i> Admin Profile
            </div>
            <div class="card-body cultivation">
                @if(session()->has('success'))
                    <div class="alert alert-success w-100">
                        {{ session()->get('success') }}
                    </div>
                @endif
                @if(session()->has('error'))
                    <div class="alert alert-warning w-100">
                        {{ session()->get('error') }}
                    </div>
                @endif
                @php $isDemo = strpos(config('app.url'), 'demoadmin.cultivationapp.com') !== false; @endphp
                <div class="profile-header p-3 p-md-4 mb-4">
                    <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center">
                        @php
                            $avatarPath = !empty($adminAvatar) ? asset('public/upload/image/admin/'.$adminAvatar) : asset('/public/back-office/').'/img/figure/admin.jpg';
                        @endphp
                        <img src="{{ $avatarPath }}" alt="{{ $adminName }}" class="profile-avatar">
                        <div class="flex-grow-1 ml-md-3 mt-3 mt-md-0">
                            <div class="d-flex align-items-center">
                                <h5 class="mb-0">{{ $adminName }}</h5>
                                <span class="badge badge-primary ml-2">{{ $adminType ?? 'Admin' }}</span>
                            </div>
                            <div class="text-muted small mt-1">
                                <i class="far fa-envelope"></i> {{ $adminEmail }}
                                <span class="mx-2">•</span>
                                <i class="fas fa-phone"></i> {{ $adminMobile ?: '—' }}
                            </div>
                        </div>
                        <a href="#photo" class="btn btn-soft mt-3 mt-md-0 ml-md-auto">Update Photo</a>
                    </div>
                </div>

                <section class="row">
                    <div class="col-md-6 col-12">
                        <div class="card-title section-title"><i class="far fa-id-card"></i> Details</div>
                        <form action="{{ route('saveAdminProfile') }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <input type="hidden" name="adminId" value="{{ $adminId }}">
                            <div class="mb-3">
                                <label for="adminName" class="form-label">Full Name</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="fas fa-user"></i></span>
                                    </div>
                                    <input type="text" name="adminName" class="form-control" id="adminName" value="{{ $adminName }}" placeholder="e.g. John Doe" required autocomplete="name">
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="adminEmail" class="form-label">Email</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="far fa-envelope"></i></span>
                                    </div>
                                    <input type="email" class="form-control" id="adminEmail" value="{{ $adminEmail }}" placeholder="email@example.com" readonly>
                                </div>
                                <div class="form-hint">Email is read-only. Contact support to change.</div>
                            </div>
                            <div class="mb-3">
                                <label for="adminMobile" class="form-label">Mobile</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="fas fa-phone"></i></span>
                                    </div>
                                    <input type="text" name="adminMobile" class="form-control" id="adminMobile" value="{{ $adminMobile }}" placeholder="e.g. 01XXXXXXXXX" required pattern="^[0-9+\-()\s]{6,20}$" autocomplete="tel">
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary">Save Changes</button>
                        </form>

                        <div id="photo" class="divider"></div>
                        <div class="card-title section-title"><i class="fas fa-image"></i> Profile Photo</div>
                        @error('avatar')
                            <div class="alert alert-danger">{{ $message }}</div>
                        @enderror
                        @if(!empty($adminAvatar))
                            <div class="mb-3">
                                <img class="img-thumbnail" style="max-width:160px;border-radius:50%;object-fit:cover;" src="{{ asset('public/upload/image/admin/'.$adminAvatar) }}" alt="{{ $adminName }}" />
                            </div>
                            <div class="mb-3">
                                <a href="{{ route('delAdminPhoto',['id'=>$adminId]) }}" class="btn btn-outline-danger">Remove Photo</a>
                            </div>
                        @endif
                        <form action="{{ route('updateAdminPhoto') }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <input type="hidden" name="adminId" value="{{ $adminId }}">
                            <div class="mb-3">
                                <label for="avatar" class="form-label">Upload New Photo</label>
                                <input type="file" name="avatar" id="avatar" class="form-control-file" accept="image/*">
                                <div class="form-hint">Recommended size 200x200. JPG/PNG/WEBP/AVIF up to 5MB.</div>
                            </div>
                            <button type="submit" class="btn btn-success">Update Photo</button>
                        </form>
                    </div>
                    <div class="col-md-6 col-12">
                        @if(!$isDemo)
                            <div class="card-title section-title"><i class="fas fa-key"></i> Change Password</div>
                            <form action="{{ route('changeAdminPassword') }}" method="POST" enctype="multipart/form-data">
                                @csrf
                                <input type="hidden" name="adminId" value="{{ $adminId }}">
                                <div class="mb-3">
                                    <label for="oldPassword" class="form-label">Old Password</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                        </div>
                                        <input type="password" autocomplete="current-password" name="oldPassword" class="form-control" id="oldPassword" placeholder="Current password" required>
                                        <div class="input-group-append">
                                            <button type="button" class="btn btn-soft" data-toggle="password" data-target="#oldPassword"><i class="far fa-eye"></i></button>
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="newPassword" class="form-label">New Password</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-key"></i></span>
                                        </div>
                                        <input type="password"  name="newPassword" class="form-control" id="newPassword" placeholder="At least 8 characters" minlength="8" required>
                                        <div class="input-group-append">
                                            <button type="button" class="btn btn-soft" data-toggle="password" data-target="#newPassword"><i class="far fa-eye"></i></button>
                                        </div>
                                    </div>
                                    <div class="form-hint">Use 8+ characters with a mix of letters and numbers.</div>
                                </div>
                                <div class="mb-3">
                                    <label for="confirmPassword" class="form-label">Confirm Password</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-key"></i></span>
                                        </div>
                                        <input type="password"  name="confirmPassword" class="form-control" id="confirmPassword" placeholder="Re-type new password" required>
                                        <div class="input-group-append">
                                            <button type="button" class="btn btn-soft" data-toggle="password" data-target="#confirmPassword"><i class="far fa-eye"></i></button>
                                        </div>
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-primary">Change Password</button>
                            </form>
                        @else
                            <div class="alert alert-warning mt-4">Password change is disabled in demo mode.</div>
                        @endif
                    </div>
                </section>
            </div>
        </div>
    </div>
</div>
<!-- Dashboard summery End Here -->
@push('scripts')
<script>
    document.querySelectorAll('[data-toggle="password"]').forEach(function(btn){
        btn.addEventListener('click', function(){
            var target = document.querySelector(this.getAttribute('data-target'));
            if(!target) return;
            if(target.type === 'password'){ target.type = 'text'; this.innerHTML = '<i class="far fa-eye-slash"></i>'; }
            else { target.type = 'password'; this.innerHTML = '<i class="far fa-eye"></i>'; }
        });
    });
    // Auto-dismiss alerts after a while if present
    setTimeout(function(){
        document.querySelectorAll('.alert').forEach(function(el){ el.classList.add('fade'); el.style.opacity = '0'; setTimeout(()=> el.remove(), 500); });
    }, 3000);
</script>
@endpush
@endsection