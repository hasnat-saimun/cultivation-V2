@extends('cultivation.include')
@section('backTitle')
New Admission
@endsection
@section('backIndex')
@push('styles')
<style>
    .section-title{font-weight:600}
    .form-hint{font-size:.85rem;color:#6c757d}
    .preview-avatar{width:120px;height:120px;border-radius:50%;object-fit:cover;display:none}
    .input-group-text{display:flex;align-items:center;line-height:1;padding:.375rem .75rem}
    .input-group-text i{min-width:16px;text-align:center}
    /* Ensure icon addon matches control height and width */
    .input-group .form-control{height:42px}
    .input-group .input-group-text{height:42px;min-width:42px;justify-content:center}
    /* Force proper input-group layout against theme overrides */
    .new-added-form .input-group{display:flex!important;flex-wrap:nowrap!important;align-items:stretch!important;position:relative!important}
    .new-added-form .input-group-prepend{display:flex!important;order:0!important;margin-right:0!important;float:none!important;position:relative!important;left:0!important;right:auto!important;top:auto!important;transform:none!important;flex:0 0 auto!important}
    .new-added-form .input-group-prepend .input-group-text{border-right:0!important;border-top-right-radius:0!important;border-bottom-right-radius:0!important}
    .new-added-form .input-group>.form-control{order:1!important;flex:1 1 auto!important;border-left:0!important;border-top-left-radius:0!important;border-bottom-left-radius:0!important}
    .new-added-form .input-group-prepend,.new-added-form .input-group-text{right:auto!important;left:auto!important}
    .new-added-form .input-group-text i{position:static!important;left:auto!important;right:auto!important;top:auto!important;transform:none!important}
    .new-added-form .input-group .form-control{padding-left:.75rem!important;padding-right:.75rem!important}
    /* Make Select2 height consistent with inputs */
    .select2-container--default .select2-selection--single{height:42px!important;border-color:#ced4da}
    .select2-container--default .select2-selection--single .select2-selection__rendered{line-height:42px!important}
    .select2-container--default .select2-selection--single .select2-selection__arrow{height:42px!important}
    .btn-soft{background:#f1f3f5;border-color:#f1f3f5;color:#495057}
    .btn-soft:hover{background:#e9ecef;border-color:#e9ecef}
    .badge-note{background:#eef2f7;color:#495057}
    .card-header .btn{margin-right:.5rem}
</style>
@endpush
@php
    $serverData = \App\Models\ServerConfig::orderBy('id','DESC')->limit(1)->first();
    if(!empty($serverData)):
        $serverId           = $serverData->id;
        $insName            = $serverData->institueName;
        $location           = $serverData->address;
        $stdIdPrefix        = $serverData->studentIdPrefix;
        $teacherIdPrefix    = $serverData->teacherIdPrefix;
        $staffIdPrefix      = $serverData->staffIdPrefix;
    else:
        $serverId           = "";
        $insName            = "Sonar Bangla College";
        $location           = "Poyat, Burichong, Cumilla";
        $stdIdPrefix        = "SBCSTID";
        $teacherIdPrefix    = "SBCTID";
        $staffIdPrefix      = "SBCSTFID";
    endif;
    
    // Generate student ID here
    $lastRecord = \App\Models\newAdmission::latest('id')->first();
    $nextId = $lastRecord ? ($lastRecord->id + 1) : 1;
    $currentYear = date('Y');
    $sixDigitId = str_pad($nextId, 6, "0", STR_PAD_LEFT);
    $stdId = $currentYear . $sixDigitId;
@endphp
                <!-- Dashboard summery Start Here -->
                <div class="row gutters-20 mb-4">
                    <div class="item-title">
                        <h3>Add New Students</h3>
                    </div>
                    <!-- Admit Form Area Start Here -->
                        <div class="card height-auto">
                            <div class="card-header bg-light">
                                <a href="{{route('studentList')}}" class="btn btn-success">Student List</a>
                                <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#bulkUploadModal">
                                    Bulk Upload Students
                                </button>
                            </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-12">
                                    @if(session()->has('success'))
                                        <div class="alert alert-success w-100">
                                            {{ session()->get('success') }}
                                        </div>
                                    @endif
                                    @if(session()->has('error'))
                                        <div class="alert alert-danger w-100">
                                            {{ session()->get('error') }}
                                        </div>
                                    @endif
                                    @error('avatar')
                                        <div class="alert alert-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="heading-layout1">
                                <div class="dropdown">
                                    <a class="dropdown-toggle" href="#" role="button" data-toggle="dropdown"
                                        aria-expanded="false">...</a>

                                    <div class="dropdown-menu dropdown-menu-right">
                                        <a class="dropdown-item" href="#"><i
                                                class="fas fa-times text-orange-red"></i>Close</a>
                                        <a class="dropdown-item" href="#"><i
                                                class="fas fa-cogs text-dark-pastel-green"></i>Edit</a>
                                        <a class="dropdown-item" href="#"><i
                                                class="fas fa-redo-alt text-orange-peel"></i>Refresh</a>
                                    </div>
                                </div>
                            </div>
                            <form class="new-added-form" action="{{ route('confirmAdmit') }}" method="POST" enctype="multipart/form-data">
                                @csrf
                                    <div class="row mb-2">
                                        <h5 class="section-title">Personal Information</h5>
                                    </div>
                                <div class="row">
                                    <div class="col-xl-3 col-lg-6 col-12 form-group">
                                        <label>Admission ID</label>
                                        <div class="input-group">
                                            <div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-hashtag"></i></span></div>
                                            <input type="text" name="stdId" value="{{ $stdId }}" placeholder="Example:- {{ $currentYear }}000001" class="form-control" readonly>
                                        </div>
                                    </div>
                                    <div class="col-xl-3 col-lg-6 col-12 form-group">
                                        <label>Full Name *</label>
                                        <div class="input-group">
                                            <div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-user"></i></span></div>
                                            <input type="text" name="fullName" placeholder="Enter student first name" class="form-control" >
                                        </div>
                                    </div>
                                    <div class="col-xl-3 col-lg-6 col-12 form-group">
                                        <label>Sure Name</label>
                                        <div class="input-group">
                                            <div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-user"></i></span></div>
                                            <input type="text" name="sureName" placeholder="Enter student last name" class="form-control">
                                        </div>
                                    </div>
                                    <div class="col-xl-3 col-lg-6 col-12 form-group">
                                        <label>Father's Name *</label>
                                        <div class="input-group">
                                            <div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-user-tie"></i></span></div>
                                            <input type="text" name="fatherName" placeholder="Enter father's name" class="form-control" >
                                        </div>
                                    </div>
                                    <div class="col-xl-3 col-lg-6 col-12 form-group">
                                        <label>Mother's Name *</label>
                                        <div class="input-group">
                                            <div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-user"></i></span></div>
                                            <input type="text" name="motherName" placeholder="Enter mother's name" class="form-control" >
                                        </div>
                                    </div>
                                    <div class="col-xl-3 col-lg-6 col-12 form-group">
                                        <label>Gender *</label>
                                        <select class="select2" name="gender" >
                                            <option value="">Select *</option>
                                            <option value="1">Male</option>
                                            <option value="2">Female</option>
                                            <option value="3">Others</option>
                                        </select>
                                    </div>
                                    <div class="col-xl-3 col-lg-6 col-12 form-group">
                                        <label>Date Of Birth</label>
                                        <div class="input-group">
                                            <div class="input-group-prepend"><span class="input-group-text"><i class="far fa-calendar"></i></span></div>
                                            <input type="date" name="dob" placeholder="dd/mm/yyyy" class="form-control">
                                        </div>
                                    </div>
                                    <div class="col-xl-3 col-lg-6 col-12 form-group">
                                        <label>Blood Group *</label>
                                        <select class="select2" name="blGroup">
                                            <option value="">Select *</option>
                                            <option value="1">A+</option>
                                            <option value="2">A-</option>
                                            <option value="3">B+</option>
                                            <option value="4">B-</option>
                                            <option value="5">O+</option>
                                            <option value="6">O-</option>
                                            <option value="7">AB+</option>
                                            <option value="8">AB-</option>
                                        </select>
                                    </div>
                                    <div class="col-xl-3 col-lg-6 col-12 form-group">
                                        <label>Religion *</label>
                                        <select class="select2" name="religion" >
                                            <option value="">Select *</option>
                                            <option value="1">Islam</option>
                                            <option value="2">Hindu</option>
                                            <option value="3">Christian</option>
                                            <option value="4">Buddish</option>
                                            <option value="5">Others</option>
                                        </select>
                                    </div>
                                    <div class="col-xl-3 col-lg-6 col-12 form-group">
                                        <label>E-Mail</label>
                                        <div class="input-group">
                                            <div class="input-group-prepend"><span class="input-group-text"><i class="far fa-envelope"></i></span></div>
                                            <input type="email" name="mail" placeholder="Enter student email" class="form-control">
                                        </div>
                                    </div>
                                    <div class="col-xl-3 col-lg-6 col-12 form-group">
                                        <label>Phone</label>
                                        <div class="input-group">
                                            <div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-phone"></i></span></div>
                                            <input type="tel" name="phone" placeholder="Enter guardian mobile number" class="form-control" >
                                        </div>
                                    </div>
                                    <div class="col-xl-3 col-lg-6 col-12 form-group">
                                        <label>Address</label>
                                        <div class="input-group">
                                            <div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-map-marker-alt"></i></span></div>
                                            <input type="text" class="form-control" placeholder="Student full address" name="address">
                                        </div>
                                    </div>
                                    <div class="col-xl-3 col-lg-6 col-12 form-group ">
                                        <label class="text-dark-medium">Avatar (150px X 150px)</label>
                                        <input type="file" name="avatar" class="form-control-file" accept="image/*" id="stdAvatar">
                                        <div class="form-hint">Accepted: JPG/PNG/WEBP/AVIF up to 5MB.</div>
                                        <div class="mt-2"><img id="stdAvatarPreview" class="preview-avatar" alt="Preview"></div>
                                    </div>
                                </div>
                                <div class="row mt-5 mb-2">
                                    <h5 class="section-title">Academic Information</h5>
                                </div>
                                <div class="row">
                                    <div class="col-xl-3 col-lg-6 col-12 form-group">
                                        <label>Session *</label>
                                        <select class="select2" name="sessName" >
                                            <option value="">Select *</option>
                                            @php 
                                                $sessionDetails = \App\Models\sessionManage::all();
                                            @endphp
                                            @if(!empty($sessionDetails) && count($sessionDetails)>0)
                                            @foreach($sessionDetails as $sd)
                                                <option value="{{ $sd->id }}">{{ $sd->session}}</option>
                                                @endforeach
                                            @endif
                                        </select>
                                    </div>
                                    <div class="col-xl-3 col-lg-6 col-12 form-group">
                                        <label>Class *</label>
                                        <select class="select2" name="className" >
                                            <option value="">Select *</option>
                                            @php 
                                                $classDetails = \App\Models\classManage::all();
                                            @endphp
                                            @if(!empty($classDetails) && count($classDetails)>0)
                                            @foreach($classDetails as $cd)
                                                <option value="{{ $cd->id }}">{{ $cd->className}}</option>
                                                @endforeach
                                            @endif
                                        </select>
                                    </div>
                                    <div class="col-xl-3 col-lg-6 col-12 form-group">
                                        <label>Department *</label>
                                        <select class="select2" name="departmentName" >
                                            <option value="">Select *</option>
                                            @php 
                                                $departmentDetails = \App\Models\Department::all();
                                            @endphp
                                            @if(!empty($departmentDetails) && count($departmentDetails)>0)
                                            @foreach($departmentDetails as $sd)
                                                <option value="{{ $sd->id }}">{{ $sd->departmentName}}</option>
                                                @endforeach
                                            @endif
                                        </select>
                                    </div>
                                    <div class="col-xl-3 col-lg-6 col-12 form-group">
                                        <label>Section/Group *</label>
                                        <select class="select2" name="sectionName" >
                                            <option value="">Select *</option>
                                            @php 
                                                $sectionDatails = \App\Models\sectionManage::all();
                                            @endphp
                                            @if(!empty($sectionDatails) && count($sectionDatails)>0)
                                            @foreach($sectionDatails as $sec)
                                            <option value="{{$sec->id}}">{{$sec->section}}</option>
                                            @endforeach
                                            @endif
                                        </select>
                                    </div>
                                    <div class="col-xl-3 col-lg-6 col-12 form-group">
                                        <label>Roll</label>
                                        <div class="input-group">
                                            <div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-list-alt"></i></span></div>
                                            <input type="text" name="rollNumber" placeholder="Enter student class roll" class="form-control" >
                                        </div>
                                    </div>
                                </div>
                                <div class="row mb-2 mt-5">
                                    <h5 class="section-title">Guardian Information</h5>
                                </div>
                                <div class="row">
                                    <div class="col-xl-3 col-lg-6 col-12 form-group">
                                        <label for="gurdian">Guardian Name</label>
                                        <div class="input-group">
                                            <div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-user-shield"></i></span></div>
                                            <input type="text" class="form-control" placeholder="Enter guardian name" name="gurdian" id="gurdian" >
                                        </div>
                                    </div>
                                    <div class="col-xl-3 col-lg-6 col-12 form-group">
                                        <label for="gurdianPhone">Mobile Number</label>
                                        <div class="input-group">
                                            <div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-phone"></i></span></div>
                                            <input type="tel" class="form-control" placeholder="Enter phone number" name="gurdianPhone" id="gurdianPhone" >
                                        </div>
                                    </div>
                                    <div class="col-xl-3 col-lg-6 col-12 form-group">
                                        <label for="relationWithStd" >Relation *</label>
                                        <select class="select2" id="relationWithStd"  name="relationWithStd">
                                            <option value="">Select </option>
                                            <option value="1">Father</option>
                                            <option value="2">Mother</option>
                                            <option value="3">Brother</option>
                                            <option value="4">Sister</option>
                                            <option value="5">Uncle</option>
                                            <option value="6">Aunty</option>
                                            <option value="7">Other</option>
                                        </select>
                                    </div>
                                    
                                    <div class="col-12 form-group mg-t-8">
                                        <button type="submit" class="btn-fill-lg btn-gradient-yellow btn-hover-bluedark"><i class="fas fa-save"></i> Save</button>
                                        <button type="reset" class="btn-fill-lg bg-blue-dark btn-hover-bluedark"><i class="fas fa-redo-alt"></i> Reset</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <!-- Add this modal before -->
<!-- Bulk Upload Modal -->
<div class="modal fade" id="bulkUploadModal" tabindex="-1" role="dialog" aria-labelledby="bulkUploadModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="bulkUploadModalLabel">Bulk Upload Students</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="{{ route('bulkUploadStudents') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <div class="form-group">
                        <label>Upload CSV/Excel File</label>
                        <input type="file" name="student_file" class="form-control-file" accept=".csv,.xlsx,.xls" required>
                        <small class="form-text text-muted">
                            Upload CSV or Excel file with student data. 
                            <a href="{{ route('downloadStudentTemplate') }}" class="text-primary">Download Template</a>
                        </small>
                    </div>
                    <div class="alert alert-info">
                        <strong>File Format:</strong><br>
                        Columns: Full Name, Sure Name, Father Name, Mother Name, Gender, DOB, Blood Group, Religion, Email, Phone, Address, Session, Class, Department, Section, Roll, Guardian, Guardian Phone, Relation
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Upload Students</button>
                </div>
            </form>
        </div>
    </div>
</div>
        @push('scripts')
        <script>
            (function(){
                var input = document.getElementById('stdAvatar');
                var img = document.getElementById('stdAvatarPreview');
                if(input && img){
                    input.addEventListener('change', function(){
                        var f = this.files && this.files[0];
                        if(!f){ img.style.display='none'; return; }
                        var url = URL.createObjectURL(f);
                        img.src = url; img.style.display='inline-block';
                    });
                }
            })();
            setTimeout(function(){
                document.querySelectorAll('.alert').forEach(function(el){ el.classList.add('fade'); el.style.opacity = '0'; setTimeout(function(){ el.remove(); }, 500); });
            }, 3000);
        </script>
        @endpush
        @endsection