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
    $selectedOptionalGroupId = $selectedClassRequiresGroup ? old('optionalGroupId', '0') : null;
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
                                        <select class="select2" name="examId" id="marks_exam_select" required>
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
                                        <select class="select2" name="classId" id="marks_class_select" required disabled>
                                            <option value="">Select exam and session first</option>
                                        </select>
                                    </div>

                                    <div class="col-12 form-group">
                                        <label>Section/Group</label>
                                        <select class="select2" name="groupId" id="marks_section_select">
                                            <option value="">Select (optional)</option>
                                        </select>
                                    </div>

                                    <div class="col-12 form-group {{ $selectedClassRequiresGroup ? '' : 'd-none' }}" id="optional_group_wrapper">
                                        <label>Department/Group (Optional)</label>
                                        <select class="select2" name="optionalGroupId" id="marks_optional_group_select" {{ $selectedClassRequiresGroup ? '' : 'disabled' }}>
                                            <option value="0" selected>All Departments/Groups</option>
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
                                        <button type="submit" id="marks_get_data_button" class="btn-fill-lg btn-gradient-yellow btn-hover-bluedark" disabled>Get Data</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const examSelect = document.getElementById('marks_exam_select');
        const sessionSelect = document.getElementById('marks_session_select');
        const classSelect = document.getElementById('marks_class_select');
        const sectionSelect = document.getElementById('marks_section_select');
        const optionalGroupSelect = document.getElementById('marks_optional_group_select');
        const optionalGroupWrapper = document.getElementById('optional_group_wrapper');
        const subjectSelect = document.getElementById('subject_select');
        const getDataButton = document.getElementById('marks_get_data_button');

        const classEndpoint = @json(route('api.marks.classes', [], false));
        const sectionEndpoint = @json(route('api.marks.sections', [], false));
        const groupEndpoint = @json(route('api.marks.groups', [], false));
        const subjectEndpoint = @json(route('api.marks.subjects', [], false));
        const csrfToken = @json(csrf_token());

        const oldValues = {
            examId: @json((string) old('examId', '')),
            sessionId: @json((string) old('sessionId', '')),
            classId: @json((string) old('classId', '')),
            sectionId: @json((string) old('groupId', '')),
            optionalGroupId: @json((string) old('optionalGroupId', '0')),
            subjectId: @json((string) old('subjectId', ''))
        };

        let classRequirementMap = {};
        let sectionRequired = false;
        let requestVersion = 0;

        if (!examSelect || !sessionSelect || !classSelect || !sectionSelect ||
            !optionalGroupSelect || !subjectSelect || !getDataButton) {
            return;
        }

        function refreshSelect2(element) {
            if (window.jQuery && window.jQuery.fn && window.jQuery.fn.select2) {
                window.jQuery(element).trigger('change.select2');
            }
        }

        function replaceOptions(element, placeholder, disabled = true) {
            element.innerHTML = '';
            const option = document.createElement('option');
            option.value = '';
            option.textContent = placeholder;
            element.appendChild(option);
            element.disabled = disabled;
            refreshSelect2(element);
        }

        function addOptions(element, rows, selectedValue = '') {
            rows.forEach(function (row) {
                const option = document.createElement('option');
                option.value = String(row.id);
                option.textContent = row.name || row.subjectName || '';
                element.appendChild(option);
            });

            if (selectedValue && Array.from(element.options).some(option => option.value === String(selectedValue))) {
                element.value = String(selectedValue);
            }

            refreshSelect2(element);
        }

        async function postJson(url, payload) {
            const response = await fetch(url, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify(payload)
            });

            if (!response.ok) {
                throw new Error('Request failed with status ' + response.status);
            }

            return response.json();
        }

        function classNeedsOptionalGroup() {
            return Boolean(classRequirementMap[String(classSelect.value || '')]);
        }

        function syncOptionalGroupVisibility() {
            const visible = classNeedsOptionalGroup();
            optionalGroupWrapper.classList.toggle('d-none', !visible);
            optionalGroupSelect.disabled = !visible;

            if (!visible) {
                optionalGroupSelect.value = '';
                refreshSelect2(optionalGroupSelect);
            }
        }

        function updateSubmitState() {
            const basicReady = Boolean(
                examSelect.value &&
                sessionSelect.value &&
                classSelect.value &&
                subjectSelect.value
            );
            const sectionReady = !sectionRequired || Boolean(sectionSelect.value);
            getDataButton.disabled = !(basicReady && sectionReady);
        }

        function resetAfterSession() {
            classRequirementMap = {};
            sectionRequired = false;
            replaceOptions(classSelect, 'Select exam and session first', true);
            replaceOptions(sectionSelect, 'Select class first', true);
            replaceOptions(optionalGroupSelect, 'All Departments/Groups', true);
            replaceOptions(subjectSelect, 'Select section first', true);
            syncOptionalGroupVisibility();
            updateSubmitState();
        }

        function resetAfterClass() {
            sectionRequired = false;
            replaceOptions(sectionSelect, 'Select class first', true);
            replaceOptions(optionalGroupSelect, 'All Departments/Groups', true);
            replaceOptions(subjectSelect, 'Select section first', true);
            syncOptionalGroupVisibility();
            updateSubmitState();
        }

        function resetAfterSection() {
            replaceOptions(optionalGroupSelect, 'All Departments/Groups', true);
            replaceOptions(subjectSelect, 'Select section first', true);
            syncOptionalGroupVisibility();
            updateSubmitState();
        }

        async function loadClasses(restoreOld = false) {
            const examId = examSelect.value || '';
            const sessionId = sessionSelect.value || '';
            const version = ++requestVersion;

            resetAfterSession();

            if (!examId || !sessionId) {
                return;
            }

            replaceOptions(classSelect, 'Loading classes...', true);

            try {
                const json = await postJson(classEndpoint, { examId, sessionId });
                if (version !== requestVersion) return;

                const rows = Array.isArray(json?.classes) ? json.classes : [];
                replaceOptions(classSelect, rows.length ? 'Select *' : 'No assigned class found', rows.length === 0);

                rows.forEach(row => {
                    classRequirementMap[String(row.id)] = Boolean(row.requiresOptionalGroup);
                });
                addOptions(classSelect, rows, restoreOld ? oldValues.classId : '');

                if (restoreOld && classSelect.value) {
                    await loadSections(true);
                }
            } catch (error) {
                if (version !== requestVersion) return;
                replaceOptions(classSelect, 'Unable to load classes', true);
            }

            updateSubmitState();
        }

        async function loadSections(restoreOld = false) {
            const classId = classSelect.value || '';
            const sessionId = sessionSelect.value || '';
            const version = ++requestVersion;

            resetAfterClass();
            syncOptionalGroupVisibility();

            if (!classId || !sessionId) return;

            replaceOptions(sectionSelect, 'Loading sections...', true);

            try {
                const json = await postJson(sectionEndpoint, { classId, sessionId });
                if (version !== requestVersion) return;

                const rows = Array.isArray(json?.sections) ? json.sections : [];
                sectionRequired = rows.length > 0;
                replaceOptions(
                    sectionSelect,
                    rows.length ? 'Select *' : 'No section assigned (class-wide)',
                    false
                );
                addOptions(sectionSelect, rows, restoreOld ? oldValues.sectionId : '');

                if (rows.length === 0) {
                    await loadGroups(restoreOld);
                } else if (restoreOld && sectionSelect.value) {
                    await loadGroups(true);
                }
            } catch (error) {
                if (version !== requestVersion) return;
                replaceOptions(sectionSelect, 'Unable to load sections', true);
            }

            updateSubmitState();
        }

        async function loadGroups(restoreOld = false) {
            const classId = classSelect.value || '';
            const sectionId = sectionSelect.value || '';
            const sessionId = sessionSelect.value || '';
            const version = ++requestVersion;

            resetAfterSection();
            syncOptionalGroupVisibility();

            if (!classId || !sessionId || (sectionRequired && !sectionId)) return;

            if (!classNeedsOptionalGroup()) {
                await loadSubjects(restoreOld);
                return;
            }

            replaceOptions(optionalGroupSelect, 'Loading groups...', true);

            try {
                const json = await postJson(groupEndpoint, { classId, sectionId, sessionId });
                if (version !== requestVersion) return;

                const rows = Array.isArray(json?.groups) ? json.groups : [];
                replaceOptions(optionalGroupSelect, 'All Departments/Groups', false);
                optionalGroupSelect.options[0].value = '0';
                addOptions(optionalGroupSelect, rows, restoreOld ? oldValues.optionalGroupId : '0');

                if (!optionalGroupSelect.value) {
                    optionalGroupSelect.value = '0';
                    refreshSelect2(optionalGroupSelect);
                }

                await loadSubjects(restoreOld);
            } catch (error) {
                if (version !== requestVersion) return;
                replaceOptions(optionalGroupSelect, 'Unable to load groups', true);
            }

            updateSubmitState();
        }

        async function loadSubjects(restoreOld = false) {
            const classId = classSelect.value || '';
            const sectionId = sectionSelect.value || '';
            const optionalGroupId = optionalGroupSelect.value || '';
            const sessionId = sessionSelect.value || '';
            const version = ++requestVersion;

            replaceOptions(subjectSelect, 'Select section first', true);

            if (!classId || !sessionId || (sectionRequired && !sectionId)) {
                updateSubmitState();
                return;
            }

            replaceOptions(subjectSelect, 'Loading subjects...', true);

            try {
                const json = await postJson(subjectEndpoint, {
                    classId,
                    sectionId,
                    optionalGroupId,
                    sessionId,
                    examId: examSelect.value || ''
                });
                if (version !== requestVersion) return;

                const rows = Array.isArray(json) ? json.map(row => ({
                    id: row.id,
                    name: row.subjectName || row.name || row.subject || row.title || ''
                })) : [];

                replaceOptions(subjectSelect, rows.length ? 'Select *' : 'No assigned subject found', rows.length === 0);
                addOptions(subjectSelect, rows, restoreOld ? oldValues.subjectId : '');
            } catch (error) {
                if (version !== requestVersion) return;
                replaceOptions(subjectSelect, 'Unable to load subjects', true);
            }

            updateSubmitState();
        }

        function bindChange(element, handler) {
            if (window.jQuery) {
                window.jQuery(element).off('.marksEntry').on('change.marksEntry', handler);
            } else {
                element.addEventListener('change', handler);
            }
        }

        bindChange(examSelect, function () { loadClasses(false); });
        bindChange(sessionSelect, function () { loadClasses(false); });
        bindChange(classSelect, function () { loadSections(false); });
        bindChange(sectionSelect, function () { loadGroups(false); });
        bindChange(optionalGroupSelect, function () { loadSubjects(false); });
        bindChange(subjectSelect, updateSubmitState);

        (async function initialize() {
            resetAfterSession();

            if (oldValues.examId) examSelect.value = oldValues.examId;
            if (oldValues.sessionId) sessionSelect.value = oldValues.sessionId;
            refreshSelect2(examSelect);
            refreshSelect2(sessionSelect);

            if (examSelect.value && sessionSelect.value) {
                await loadClasses(true);
            }

            updateSubmitState();
        })();
    });
</script>
@endsection