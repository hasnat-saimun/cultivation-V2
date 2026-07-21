@extends('result.include')
@section('backTitle')
Add New Marks
@endsection
@section('backIndex')
@php
    use App\Models\Exam;
    use App\Models\sectionManage;
    use App\Models\Department;

    $adminId = session('cultivationAdmin'); // or your custom session key
    $user = $adminId ? \App\Models\CultivationAdmin::find($adminId) : null;
    $isTeacherAdmin = $user && $user->userType == 1;

    $selectedClassId = old('classId');
    $selectedClassRequiresGroup = !empty($selectedClassId)
        ? !empty($classGroupRequirementMap[(string)$selectedClassId])
        : false;
    $selectedOptionalGroupId = $selectedClassRequiresGroup ? old('optionalGroupId') : null;
    $selectedGender = old('gender', 'all');
@endphp
                <!-- Dashboard summery Start Here -->
                <div class="row gutters-20 mb-4">
                    <!-- Admit Form Area Start Here -->
                    <div class="card height-auto">
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
                                </div>
                            </div>
                            <div class="heading-layout1">
                                <div class="item-title">
                                    <h3>Add New Marks</h3>
                                </div>
                            </div>
                            <form class="new-added-form" action="{{ route('getMarks') }}" method="POST">
                                @csrf
                                <div class="row">
                                    <div class="col-12 form-group">
                                        <label>Exam *</label>
                                        <select class="select2" name="examId" required>
                                            <option value="">Select *</option>
                                            @php
                                                $examList = Exam::orderBy('id','DESC')->get();
                                            @endphp
                                            @if(!empty($examList))
                                                @foreach($examList as $exm)
                                                <option value="{{ $exm->id }}" {{ old('examId') == $exm->id ? 'selected' : '' }}>{{ $exm->examName }}</option>
                                                @endforeach
                                            @endif
                                        </select>
                                    </div><!-- Class Dropdown -->
                                    <div class="col-12 form-group">
                                        <label>Class *</label>
                                        <select class="select2" name="classId" id="marks_class_select" required>
                                            <option value="">Select *</option>
                                            @foreach($classes as $cls)
                                                <option value="{{ $cls->id }}" {{ old('classId') == $cls->id ? 'selected' : '' }}>{{ $cls->className }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <input type="hidden" name="sessionId" value="">
                                    <div class="col-12 form-group">
                                        <label>Section/Group</label>
                                        <select class="select2" name="groupId" id="marks_section_select">
                                            <option value="">Select (optional)</option>
                                            @php
                                                $department = sectionManage::orderBy('id','DESC')->get();
                                            @endphp
                                            @if(!empty($department))
                                                @foreach($department as $dept)
                                                <option value="{{ $dept->id }}" {{ old('groupId') == $dept->id ? 'selected' : '' }}>{{ $dept->section }}</option>
                                                @endforeach
                                            @endif
                                        </select>
                                    </div>

                                    <div class="col-12 form-group {{ $selectedClassRequiresGroup ? '' : 'd-none' }}" id="optional_group_wrapper">
                                        <label>Group (Optional)</label>
                                        <select class="select2" name="optionalGroupId" id="marks_optional_group_select" {{ $selectedClassRequiresGroup ? '' : 'disabled' }}>
                                            <option value="">Select (optional)</option>
                                            @php
                                                $optionalGroups = Department::orderBy('id','ASC')->get();
                                            @endphp
                                            @if(!empty($optionalGroups))
                                                @foreach($optionalGroups as $grp)
                                                <option value="{{ $grp->id }}" {{ (string)$selectedOptionalGroupId === (string)$grp->id ? 'selected' : '' }}>{{ $grp->departmentName }}</option>
                                                @endforeach
                                            @endif
                                        </select>
                                    </div>

                                    <div class="col-12 form-group">
                                        <label>Gender</label>
                                        <select class="select2" name="gender" id="marks_gender_select">
                                            <option value="all" {{ $selectedGender === 'all' ? 'selected' : '' }}>All</option>
                                            <option value="1" {{ $selectedGender === '1' ? 'selected' : '' }}>Male</option>
                                            <option value="2" {{ $selectedGender === '2' ? 'selected' : '' }}>Female</option>
                                            <option value="3" {{ $selectedGender === '3' ? 'selected' : '' }}>Others</option>
                                        </select>
                                    </div>
                                    
                                    <!-- Subject Dropdown (dynamically loaded per class+section) -->
                                    <div class="col-12 form-group">
                                        <label>Subject *</label>
                                        <select class="select2" id="subject_select" name="subjectId" required>
                                            <option value="">No assigned subject found for the selected criteria.</option>
                                        </select>
                                    </div>
                                    <div class="col-12 form-group mg-t-8">
                                        <button type="submit" class="btn-fill-lg btn-gradient-yellow btn-hover-bluedark">Get Data</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
<script>
    document.addEventListener('DOMContentLoaded', function(){
        const classSelect = document.getElementById('marks_class_select');
        const sectionSelect = document.getElementById('marks_section_select');
        const optionalGroupSelect = document.getElementById('marks_optional_group_select');
        const optionalGroupWrapper = document.getElementById('optional_group_wrapper');
        const subjectSelect = document.getElementById('subject_select');
        const classGroupRequirementMap = @json($classGroupRequirementMap ?? []);

        function classNeedsOptionalGroup(classId){
            return !!classGroupRequirementMap[String(classId)];
        }

        function syncOptionalGroupVisibility(){
            if(!classSelect || !optionalGroupSelect || !optionalGroupWrapper){
                return;
            }

            const shouldShow = classNeedsOptionalGroup(classSelect.value || '');
            optionalGroupWrapper.classList.toggle('d-none', !shouldShow);
            optionalGroupSelect.disabled = !shouldShow;

            if(!shouldShow){
                optionalGroupSelect.value = '';
                try{
                    if(window.jQuery){
                        window.jQuery(optionalGroupSelect).val('').trigger('change.select2');
                    }
                }catch(e){ /* ignore */ }
            }
        }

        async function loadSubjects(){
            const classId = classSelect.value; const sectionId = sectionSelect.value || ''; const optionalGroupId = optionalGroupSelect.value || '';
            if(!classId) { subjectSelect.innerHTML = '<option value="">No assigned subject found for the selected criteria.</option>'; return; }
            try{
                const res = await fetch("{{ route('api.marks.subjects') }}", {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: { 'Content-Type':'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    body: JSON.stringify({ classId: classId, sectionId: sectionId, optionalGroupId: optionalGroupId })
                });
                if(!res.ok) throw new Error('Request failed');
                const json = await res.json();
                subjectSelect.innerHTML = '<option value="">Select *</option>';
                if(Array.isArray(json)){
                    json.forEach(s => {
                        const opt = document.createElement('option'); opt.value = s.id; opt.textContent = s.subjectName || s.name || s.subject || s.title || s;
                        subjectSelect.appendChild(opt);
                    });
                } else if(json && Array.isArray(json.subjectIds)){
                    // fallback: if server returned subjectIds only, create options with ids as both value and label
                    json.subjectIds.forEach(id => {
                        const opt = document.createElement('option'); opt.value = id; opt.textContent = 'Subject '+id; subjectSelect.appendChild(opt);
                    });
                }

                if (subjectSelect.options.length <= 1) {
                    subjectSelect.innerHTML = '<option value="">No assigned subject found for the selected criteria.</option>';
                }

                // If select2 is active, trigger change so UI refreshes
                try{
                    if(window.jQuery && typeof $(subjectSelect).trigger === 'function'){
                        $(subjectSelect).trigger('change');
                    }
                }catch(e){ /* ignore */ }
            }catch(e){
                subjectSelect.innerHTML = '<option value="">No assigned subject found for the selected criteria.</option>';
            }
        }

        function onSelectionChanged(){
            syncOptionalGroupVisibility();
            loadSubjects();
        }

        if(classSelect) classSelect.addEventListener('change', onSelectionChanged);
        if(sectionSelect) sectionSelect.addEventListener('change', loadSubjects);
        if(optionalGroupSelect) optionalGroupSelect.addEventListener('change', loadSubjects);

        syncOptionalGroupVisibility();

        // If a class is already selected (e.g., page reload), load subjects on start
        if(classSelect && classSelect.value){
            loadSubjects();
        }
    });
</script>
@endsection