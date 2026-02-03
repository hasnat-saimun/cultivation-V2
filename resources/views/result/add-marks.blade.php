@extends('result.include')
@section('backTitle')
Add New Marks
@endsection
@section('backIndex')
@php
    use App\Models\CultivationAdmin;
    use App\Models\classManage;
    use App\Models\Subject;

    $adminId = session('cultivationAdmin'); // or your custom session key
    $user = $adminId ? \App\Models\CultivationAdmin::find($adminId) : null;
    $isTeacherAdmin = $user && $user->userType == 1;
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
                                                $examList = \App\Models\Exam::orderBy('id','DESC')->get();
                                            @endphp
                                            @if(!empty($examList))
                                                @foreach($examList as $exm)
                                                <option value="{{ $exm->id }}">{{ $exm->examName }}</option>
                                                @endforeach
                                            @endif
                                        </select>
                                    </div><!-- Class Dropdown -->
                                    <div class="col-12 form-group">
                                        <label>Class *</label>
                                        <!-- Class Dropdown -->
                                        <select class="select2" name="classId" required>
                                            <option value="">Select *</option>
                                            @php
                                                if($isTeacherAdmin) {
                                                    $classIds = $user->access_class_array ?? [];
                                                    $classes = \App\Models\classManage::whereIn('id', $classIds)->get();
                                                } else {
                                                    $classes = \App\Models\classManage::orderBy('id','DESC')->get();
                                                }
                                            @endphp
                                            @foreach($classes as $cls)
                                                <option value="{{ $cls->id }}">{{ $cls->className }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="col-12 form-group">
                                        <label>Session *</label>
                                        <select class="select2" name="sessionId" required>
                                            <option value="">Select *</option>
                                            @php
                                                $sessions = \App\Models\sessionManage::orderBy('id','DESC')->get();
                                            @endphp
                                            @if(!empty($sessions))
                                                @foreach($sessions as $sess)
                                                <option value="{{ $sess->id }}">{{ $sess->session }}</option>
                                                @endforeach
                                            @endif
                                        </select>
                                    </div>
                                    <div class="col-12 form-group">
                                        <label>Section/Group *</label>
                                        <select class="select2" name="groupId" required>
                                            <option value="">Select *</option>
                                            @php
                                                $department = \App\Models\sectionManage::orderBy('id','DESC')->get();
                                            @endphp
                                            @if(!empty($department))
                                                @foreach($department as $dept)
                                                <option value="{{ $dept->id }}">{{ $dept->section }}</option>
                                                @endforeach
                                            @endif
                                        </select>
                                    </div>
                                    
                                    <!-- Subject Dropdown (dynamically loaded per class+section) -->
                                    <div class="col-12 form-group">
                                        <label>Subject *</label>
                                        <select class="select2" id="subject_select" name="subjectId" required>
                                            <option value="">Select class and section first</option>
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
        const classSelect = document.querySelector('select[name="classId"]');
        const sectionSelect = document.querySelector('select[name="groupId"]');
        const subjectSelect = document.getElementById('subject_select');

        // initialize subject loader

        async function loadSubjects(){
            const classId = classSelect.value; const sectionId = sectionSelect.value || '';
            if(!classId) { subjectSelect.innerHTML = '<option value="">Select class and section first</option>'; return; }
            try{
                const res = await fetch("{{ route('api.teacher.subjects') }}", {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: { 'Content-Type':'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    body: JSON.stringify({ classId: classId, sectionId: sectionId })
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

                // If select2 is active, trigger change so UI refreshes
                try{
                    if(window.jQuery && typeof $(subjectSelect).trigger === 'function'){
                        $(subjectSelect).trigger('change');
                    }
                }catch(e){ /* ignore */ }
            }catch(e){
                subjectSelect.innerHTML = '<option value="">Error loading subjects</option>';
            }
        }

        // Native event listeners
        if(classSelect) classSelect.addEventListener('change', loadSubjects);
        if(sectionSelect) sectionSelect.addEventListener('change', loadSubjects);

        // Bind jQuery/select2 events too (some pages initialize select2 which may prevent native events)
        try{
            if(window.jQuery){
                if(classSelect) $(classSelect).on('change', loadSubjects);
                if(sectionSelect) $(sectionSelect).on('change', loadSubjects);
            }
        }catch(e){ /* no-op */ }

        // If a class is already selected (e.g., page reload), load subjects on start
        if(classSelect && classSelect.value){
            loadSubjects();
        }
    });
</script>
@endsection