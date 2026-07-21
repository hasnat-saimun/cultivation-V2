@extends('result.include')
@section('backTitle')
Add New Marks
@endsection
@section('backIndex')
@php
    use App\Models\Exam;

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
                                        <label>Session *</label>
                                        <select class="select2" name="sessionId" id="marks_session_select" required>
                                            <option value="">Select *</option>
                                            @foreach(($sessions ?? []) as $session)
                                                <option value="{{ $session->id }}" {{ (string) old('sessionId') === (string) $session->id ? 'selected' : '' }}>{{ $session->session }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="col-12 form-group">
                                        <label>Class *</label>
                                        <select class="select2" name="classId" id="marks_class_select" required {{ old('sessionId') ? '' : 'disabled' }}>
                                            <option value="">Select *</option>
                                            @foreach($classes as $cls)
                                                <option value="{{ $cls->id }}" {{ old('classId') == $cls->id ? 'selected' : '' }}>{{ $cls->className }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="col-12 form-group">
                                        <label>Section/Group</label>
                                        <select class="select2" name="groupId" id="marks_section_select">
                                            <option value="">Select (optional)</option>
                                        </select>
                                    </div>

                                    <div class="col-12 form-group {{ $selectedClassRequiresGroup ? '' : 'd-none' }}" id="optional_group_wrapper">
                                        <label>Group (Optional)</label>
                                        <select class="select2" name="optionalGroupId" id="marks_optional_group_select" {{ $selectedClassRequiresGroup ? '' : 'disabled' }}>
                                            <option value="">Select (optional)</option>
                                        </select>
                                    </div>

                                    <!-- Subject Dropdown (dynamically loaded per class+section) -->
                                    <div class="col-12 form-group">
                                        <label>Subject *</label>
                                        <select class="select2" id="subject_select" name="subjectId" required disabled>
                                            <option value="">No assigned subject found for the selected criteria.</option>
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
        const sessionSelect = document.getElementById('marks_session_select');
        const classSelect = document.getElementById('marks_class_select');
        const sectionSelect = document.getElementById('marks_section_select');
        const optionalGroupSelect = document.getElementById('marks_optional_group_select');
        const optionalGroupWrapper = document.getElementById('optional_group_wrapper');
        const subjectSelect = document.getElementById('subject_select');
        const classGroupRequirementMap = @json($classGroupRequirementMap ?? []);
        const sectionEndpoint = @json(route('api.marks.sections', [], false));
        const groupEndpoint = @json(route('api.marks.groups', [], false));
        const subjectEndpoint = @json(route('api.marks.subjects', [], false));
        const csrfToken = @json(csrf_token());
        const oldValues = {
            sessionId: @json((string) old('sessionId', '')),
            classId: @json((string) old('classId', '')),
            sectionId: @json((string) old('groupId', '')),
            optionalGroupId: @json((string) old('optionalGroupId', '')),
            subjectId: @json((string) old('subjectId', ''))
        };

        if(!sessionSelect || !classSelect || !sectionSelect || !optionalGroupSelect || !subjectSelect){
            return;
        }

        function triggerSelect2(el){
            try{
                if(window.jQuery){
                    window.jQuery(el).trigger('change.select2');
                }
            }catch(e){ /* ignore */ }
        }

        function setLoading(selectEl, message){
            selectEl.disabled = true;
            selectEl.innerHTML = '<option value="">' + message + '</option>';
            triggerSelect2(selectEl);
        }

        function resetSelect(selectEl, placeholder, disabled){
            selectEl.disabled = !!disabled;
            selectEl.innerHTML = '<option value="">' + placeholder + '</option>';
            triggerSelect2(selectEl);
        }

        function setSubjectLoadingState(message){
            setLoading(subjectSelect, message);
        }

        function setSubjectEmptyState(disabled = true, placeholder = 'No assigned subject found for the selected criteria.'){
            resetSelect(subjectSelect, placeholder, disabled);
        }

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
                triggerSelect2(optionalGroupSelect);
            }
        }

        function syncSessionGate(){
            const hasSession = !!(sessionSelect.value || '');

            classSelect.disabled = !hasSession;

            if(!hasSession){
                classSelect.value = '';
                triggerSelect2(classSelect);
                hardResetDependentFields();
            }
        }

        async function postJson(url, payload){
            const res = await fetch(url, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type':'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify(payload)
            });

            if(!res.ok){
                throw new Error('Request failed');
            }

            return await res.json();
        }

        async function loadSections(){
            const classId = classSelect.value || '';
            const sessionId = sessionSelect.value || '';

            if(!classId || !sessionId){
                resetSelect(sectionSelect, 'Select (optional)', true);
                return;
            }

            setLoading(sectionSelect, 'Loading sections...');

            try{
                const json = await postJson(sectionEndpoint, { classId, sessionId });
                resetSelect(sectionSelect, 'Select (optional)', false);
                const rows = Array.isArray(json?.sections) ? json.sections : [];
                rows.forEach(item => {
                    const opt = document.createElement('option');
                    opt.value = item.id;
                    opt.textContent = item.name;
                    sectionSelect.appendChild(opt);
                });
                if(oldValues.sectionId){
                    sectionSelect.value = oldValues.sectionId;
                    oldValues.sectionId = '';
                }
                triggerSelect2(sectionSelect);
            }catch(e){
                resetSelect(sectionSelect, 'Unable to load sections', true);
            }
        }

        async function loadGroups(){
            const classId = classSelect.value || '';
            const sectionId = sectionSelect.value || '';
            const sessionId = sessionSelect.value || '';

            if(!classId || !sessionId || !classNeedsOptionalGroup(classId)){
                resetSelect(optionalGroupSelect, 'Select (optional)', true);
                return;
            }

            setLoading(optionalGroupSelect, 'Loading groups...');

            try{
                const json = await postJson(groupEndpoint, { classId, sectionId, sessionId });
                resetSelect(optionalGroupSelect, 'Select (optional)', false);
                const rows = Array.isArray(json?.groups) ? json.groups : [];
                rows.forEach(item => {
                    const opt = document.createElement('option');
                    opt.value = item.id;
                    opt.textContent = item.name;
                    optionalGroupSelect.appendChild(opt);
                });
                if(oldValues.optionalGroupId){
                    optionalGroupSelect.value = oldValues.optionalGroupId;
                    oldValues.optionalGroupId = '';
                }
                triggerSelect2(optionalGroupSelect);
            }catch(e){
                resetSelect(optionalGroupSelect, 'Unable to load groups', true);
            }
        }

        async function loadSubjects(){
            const classId = classSelect.value || '';
            const sectionId = sectionSelect.value || '';
            const optionalGroupId = optionalGroupSelect.value || '';
            const sessionId = sessionSelect.value || '';

            if(!classId || !sessionId) {
                setSubjectEmptyState(true, 'Select session and class first');
                return;
            }

            if(classNeedsOptionalGroup(classId) && !optionalGroupId){
                setSubjectEmptyState(true, 'Select group first');
                return;
            }

            setSubjectLoadingState('Loading subjects...');

            try{
                const json = await postJson(subjectEndpoint, { classId, sectionId, optionalGroupId, sessionId });
                subjectSelect.disabled = false;
                subjectSelect.innerHTML = '<option value="">Select *</option>';
                if(Array.isArray(json)){
                    json.forEach(s => {
                        const opt = document.createElement('option');
                        opt.value = s.id;
                        opt.textContent = s.subjectName || s.name || s.subject || s.title || s;
                        subjectSelect.appendChild(opt);
                    });
                }

                if (subjectSelect.options.length <= 1) {
                    setSubjectEmptyState(false);
                    return;
                }

                if(oldValues.subjectId){
                    subjectSelect.value = oldValues.subjectId;
                    oldValues.subjectId = '';
                }

                triggerSelect2(subjectSelect);
            }catch(e){
                setSubjectEmptyState(false);
            }
        }

        function hardResetDependentFields(){
            resetSelect(sectionSelect, 'Select (optional)', true);
            resetSelect(optionalGroupSelect, 'Select (optional)', true);
            setSubjectEmptyState(true);
        }

        async function onClassOrSessionChanged(){
            hardResetDependentFields();
            syncOptionalGroupVisibility();
            await loadSections();
            await loadGroups();
            await loadSubjects();
        }

        async function onSectionChanged(){
            resetSelect(optionalGroupSelect, 'Select (optional)', true);
            syncOptionalGroupVisibility();
            await loadGroups();
            await loadSubjects();
        }

        document.addEventListener('change', function(event){
            if(event.target && (event.target.id === 'marks_session_select' || event.target.id === 'marks_class_select')){
                if(event.target.id === 'marks_session_select'){
                    syncSessionGate();
                }
                onClassOrSessionChanged();
            }
            if(event.target && event.target.id === 'marks_section_select'){
                onSectionChanged();
            }
            if(event.target && event.target.id === 'marks_optional_group_select'){
                loadSubjects();
            }
        });

        (async function init(){
            syncSessionGate();
            syncOptionalGroupVisibility();
            hardResetDependentFields();

            if(oldValues.sessionId){
                sessionSelect.value = oldValues.sessionId;
            }
            if(oldValues.classId){
                classSelect.value = oldValues.classId;
            }
            triggerSelect2(sessionSelect);
            syncSessionGate();
            triggerSelect2(classSelect);

            if(sessionSelect.value && classSelect.value){
                await onClassOrSessionChanged();
            }
        })();
    });
</script>
@endsection