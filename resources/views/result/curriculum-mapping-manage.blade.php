@extends('result.include')

@section('backTitle')
Curriculum Subject Mapping
@endsection

@section('backIndex')
<div class="row gutters-20 mb-4">
    <div class="card height-auto col-12 mx-auto">
        <div class="card-body">
            <h3 class="mb-3">Curriculum Subject Mapping</h3>

            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif
            @if(isset($errors) && $errors->any())
                <div class="alert alert-danger">
                    @foreach($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            @endif

            @php
                $mappedBySubject = $scopeMappings->keyBy(fn($row) => (int) $row->subject_id);
                $activeMappedIds = $scopeMappings
                    ->filter(fn($row) => (int) $row->is_active === 1)
                    ->pluck('subject_id')
                    ->map(fn($id) => (int) $id)
                    ->all();
                $mappedCount = $scopeMappings->count();
                $activeCount = $scopeMappings->filter(fn($row) => (int) $row->is_active === 1)->count();
                $selectedType = (string) ($selected['mappingType'] ?? 'main');
                $displaySubjects = $subjects->sort(function($left, $right) use ($mappedBySubject) {
                    $leftMap = $mappedBySubject->get((int) $left->id);
                    $rightMap = $mappedBySubject->get((int) $right->id);
                    $leftOrder = $leftMap ? (int) $leftMap->sort_order : PHP_INT_MAX;
                    $rightOrder = $rightMap ? (int) $rightMap->sort_order : PHP_INT_MAX;
                    if ($leftOrder !== $rightOrder) {
                        return $leftOrder <=> $rightOrder;
                    }

                    $leftName = strtolower((string) $left->subjectName);
                    $rightName = strtolower((string) $right->subjectName);
                    return $leftName <=> $rightName;
                })->values();
            @endphp

            <form method="GET" action="{{ route('resultCurriculumMappingManage') }}" class="mb-4">
                <div class="row g-2">
                    <div class="col-md-3">
                        <label class="form-label">Session</label>
                        <select class="form-control" name="sessionId" required>
                            <option value="">Select</option>
                            @foreach($sessions as $session)
                                <option value="{{ $session->id }}" @selected((int) ($selected['sessionId'] ?? 0) === (int) $session->id)>{{ $session->session }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Class</label>
                        <select class="form-control" name="classId" required>
                            <option value="">Select</option>
                            @foreach($classes as $class)
                                <option value="{{ $class->id }}" @selected((int) ($selected['classId'] ?? 0) === (int) $class->id)>{{ $class->className }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Section</label>
                        <select class="form-control" name="sectionId">
                            <option value="">All</option>
                            @foreach($sections as $section)
                                <option value="{{ $section->id }}" @selected((int) ($selected['sectionId'] ?? 0) === (int) $section->id)>{{ $section->section }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Department</label>
                        <select class="form-control" name="departmentId">
                            <option value="">Common</option>
                            @foreach($departments as $department)
                                <option value="{{ $department->id }}" @selected((int) ($selected['departmentId'] ?? 0) === (int) $department->id)>{{ $department->departmentName }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 mt-2">
                        <label class="form-label">Mapping Type</label>
                        <select class="form-control" name="mappingType">
                            <option value="main" @selected($selectedType === 'main')>Main</option>
                        </select>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary mt-3">Load Scope</button>
            </form>

            @if(!empty($selected['sessionId']) && !empty($selected['classId']))
                <div class="alert alert-info">
                    <div><strong>Session:</strong> {{ optional($sessions->firstWhere('id', (int) $selected['sessionId']))->session ?? $selected['sessionId'] }}</div>
                    <div><strong>Class:</strong> {{ optional($classes->firstWhere('id', (int) $selected['classId']))->className ?? $selected['classId'] }}</div>
                    <div><strong>Section:</strong> {{ empty($selected['sectionId']) ? 'All Sections' : (optional($sections->firstWhere('id', (int) $selected['sectionId']))->section ?? $selected['sectionId']) }}</div>
                    <div><strong>Department:</strong> {{ empty($selected['departmentId']) ? 'Common' : (optional($departments->firstWhere('id', (int) $selected['departmentId']))->departmentName ?? $selected['departmentId']) }}</div>
                    <div><strong>Mapping Type:</strong> {{ strtoupper($selectedType) }}</div>
                    <div><strong>Mapped subjects:</strong> {{ $mappedCount }}</div>
                    <div><strong>Active subjects:</strong> {{ $activeCount }}</div>
                </div>

                <form method="POST" action="{{ route('saveResultCurriculumMapping') }}" class="mb-4">
                    @csrf
                    <input type="hidden" name="sessionId" value="{{ (int) $selected['sessionId'] }}">
                    <input type="hidden" name="classId" value="{{ (int) $selected['classId'] }}">
                    <input type="hidden" name="sectionId" value="{{ $selected['sectionId'] }}">
                    <input type="hidden" name="departmentId" value="{{ $selected['departmentId'] }}">
                    <input type="hidden" name="mappingType" value="{{ $selectedType }}">

                    <label class="form-label">Main Subjects (curriculum-authoritative)</label>
                    <div class="row">
                        @foreach($displaySubjects as $subject)
                            @php
                                $subjectId = (int) $subject->id;
                                $mappedRow = $mappedBySubject->get($subjectId);
                                $isActive = $mappedRow && (int) $mappedRow->is_active === 1;
                                $sortOrder = $mappedRow ? (int) $mappedRow->sort_order : '';
                            @endphp
                            <div class="col-md-6 mb-2">
                                <div class="d-flex align-items-center justify-content-between border rounded p-2">
                                    <label class="d-flex align-items-center gap-2 mb-0">
                                        <input
                                            type="checkbox"
                                            name="subjectIds[]"
                                            value="{{ $subject->id }}"
                                            data-subject-id="{{ (int) $subject->id }}"
                                            data-mapped-active="{{ $isActive ? '1' : '0' }}"
                                            @checked($isActive)
                                        >
                                        <span>
                                            {{ $subject->subjectName }}
                                            @if($mappedRow)
                                                @if($isActive)
                                                    <small class="text-success">(Mapped, Active)</small>
                                                @else
                                                    <small class="text-warning">(Mapped, Inactive)</small>
                                                @endif
                                                <small class="text-muted">type: {{ strtoupper((string) $mappedRow->mapping_type) }}</small>
                                            @endif
                                            @if(strcasecmp((string) $subject->subjectType, 'Optional') === 0)
                                                <small class="text-muted">(Optional)</small>
                                            @endif
                                            @if((bool) ($subject->isReligious ?? false))
                                                <small class="text-muted">(Religious)</small>
                                            @endif
                                        </span>
                                    </label>
                                    <div class="ms-2" style="width: 110px;">
                                        <label class="form-label mb-0 small text-muted">Order</label>
                                        <input type="number" min="1" class="form-control form-control-sm" name="sortOrders[{{ (int) $subject->id }}]" value="{{ $sortOrder }}" placeholder="auto">
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <button type="submit" class="btn btn-success mt-3">Save Mapping</button>
                </form>

                <hr>

                <h5>Copy Mapping</h5>
                <form method="POST" action="{{ route('copyResultCurriculumMapping') }}">
                    @csrf
                    <div class="row g-2">
                        <div class="col-md-3">
                            <label class="form-label">Source Session</label>
                            <select class="form-control curriculum-copy-select" id="copy_source_session" name="sourceSessionId" required>
                                <option value="">Select</option>
                                @foreach($sessions as $session)
                                    <option value="{{ $session->id }}" @selected((int) ($selected['sessionId'] ?? 0) === (int) $session->id)>{{ $session->session }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Source Class</label>
                            <select class="form-control curriculum-copy-select" id="copy_source_class" name="sourceClassId" required disabled>
                                <option value="">Select session first</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Source Section</label>
                            <select class="form-control curriculum-copy-select" id="copy_source_section" name="sourceSectionId" disabled>
                                <option value="">All</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Source Department</label>
                            <select class="form-control curriculum-copy-select" id="copy_source_department" name="sourceDepartmentId" disabled>
                                <option value="">All</option>
                            </select>
                        </div>
                    </div>
                    <div class="row g-2 mt-1">
                        <div class="col-md-3">
                            <label class="form-label">Target Session</label>
                            <select class="form-control curriculum-copy-select" id="copy_target_session" name="targetSessionId" required>
                                <option value="">Select</option>
                                @foreach($sessions as $session)
                                    <option value="{{ $session->id }}" @selected((int) ($selected['sessionId'] ?? 0) === (int) $session->id)>{{ $session->session }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Target Class</label>
                            <select class="form-control curriculum-copy-select" id="copy_target_class" name="targetClassId" required disabled>
                                <option value="">Select session first</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Target Section</label>
                            <select class="form-control curriculum-copy-select" id="copy_target_section" name="targetSectionId" disabled>
                                <option value="">All</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Target Department</label>
                            <select class="form-control curriculum-copy-select" id="copy_target_department" name="targetDepartmentId" disabled>
                                <option value="">All</option>
                            </select>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-outline-primary mt-3">Copy Mapping</button>
                </form>
            @endif
        </div>
    </div>
</div>
@if(!empty($selected['sessionId']) || !empty($selected['classId']))
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const csrfToken = @json(csrf_token());
            const classEndpoint = @json(route('api.resultCurriculumMapping.classes', [], false));
            const sectionEndpoint = @json(route('api.resultCurriculumMapping.sections', [], false));
            const departmentEndpoint = @json(route('api.resultCurriculumMapping.departments', [], false));
            const ALL_SECTION_VALUE = '';

            function refreshSelect(select) {
                if (window.jQuery && window.jQuery.fn && window.jQuery.fn.select2) {
                    window.jQuery(select).trigger('change.select2');
                }
            }

            function replaceOptions(select, placeholder, disabled = true) {
                select.innerHTML = '';
                const option = document.createElement('option');
                option.value = '';
                option.textContent = placeholder;
                select.appendChild(option);
                select.disabled = disabled;
                refreshSelect(select);
            }

            function populateOptions(select, rows, selectedValue = '') {
                rows.forEach(function (row) {
                    const option = document.createElement('option');
                    option.value = String(row.id);
                    option.textContent = row.name || '';
                    if (row.requires_department !== undefined) {
                        option.dataset.requiresDepartment = row.requires_department ? '1' : '0';
                    }
                    select.appendChild(option);
                });

                if (selectedValue && Array.from(select.options).some(function (option) {
                    return option.value === String(selectedValue);
                })) {
                    select.value = String(selectedValue);
                }

                refreshSelect(select);
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

            function bindScope(prefix) {
                const sessionSelect = document.getElementById(prefix + '_session');
                const classSelect = document.getElementById(prefix + '_class');
                const sectionSelect = document.getElementById(prefix + '_section');
                const departmentSelect = document.getElementById(prefix + '_department');

                if (!sessionSelect || !classSelect || !sectionSelect || !departmentSelect) {
                    return;
                }

                const defaults = {
                    sessionId: sessionSelect.value || '',
                    classId: classSelect.value || '',
                    sectionId: sectionSelect.value || '',
                    departmentId: departmentSelect.value || '',
                };

                let requestVersion = 0;
                let sectionLoading = false;

                function classSupportsDepartment() {
                    const selectedOption = classSelect.options[classSelect.selectedIndex];
                    return Boolean(selectedOption && selectedOption.dataset && selectedOption.dataset.requiresDepartment === '1');
                }

                function hasValidSectionScope(sectionValue) {
                    return sectionValue === ALL_SECTION_VALUE || /^[1-9]\d*$/.test(String(sectionValue));
                }

                function setDepartmentDisabled(placeholder) {
                    replaceOptions(departmentSelect, placeholder, true);
                }

                async function syncDepartmentState(restoreSelection = false) {
                    const sessionId = sessionSelect.value || '';
                    const classId = classSelect.value || '';
                    const sectionValue = sectionSelect.value ?? '';

                    if (!sessionId || !classId) {
                        setDepartmentDisabled('All');
                        return;
                    }

                    if (sectionLoading) {
                        setDepartmentDisabled('Loading departments...');
                        return;
                    }

                    if (!classSupportsDepartment()) {
                        setDepartmentDisabled('All');
                        return;
                    }

                    if (!hasValidSectionScope(sectionValue)) {
                        setDepartmentDisabled('All');
                        return;
                    }

                    await loadDepartments(restoreSelection);
                }

                function resetClasses() {
                    replaceOptions(classSelect, 'Select session first', true);
                    replaceOptions(sectionSelect, 'All', true);
                    setDepartmentDisabled('All');
                }

                function resetSections() {
                    replaceOptions(sectionSelect, 'All', true);
                    setDepartmentDisabled('All');
                }

                function resetDepartments() {
                    setDepartmentDisabled('All');
                }

                async function loadClasses(restoreSelection = false) {
                    const sessionId = sessionSelect.value || '';
                    const version = ++requestVersion;
                    resetClasses();

                    if (!sessionId) {
                        return;
                    }

                    replaceOptions(classSelect, 'Loading classes...', true);

                    try {
                        const json = await postJson(classEndpoint, { sessionId });
                        if (version !== requestVersion) return;

                        const rows = Array.isArray(json?.classes) ? json.classes : [];
                        replaceOptions(classSelect, rows.length ? 'Select' : 'No class found', rows.length === 0);
                        populateOptions(classSelect, rows, restoreSelection ? defaults.classId : '');

                        if (classSelect.value) {
                            await loadSections(restoreSelection);
                        }
                    } catch (error) {
                        if (version !== requestVersion) return;
                        replaceOptions(classSelect, 'Unable to load classes', true);
                    }
                }

                async function loadSections(restoreSelection = false) {
                    const sessionId = sessionSelect.value || '';
                    const classId = classSelect.value || '';
                    const version = ++requestVersion;
                    sectionLoading = true;
                    resetSections();

                    if (!sessionId || !classId) {
                        sectionLoading = false;
                        return;
                    }

                    replaceOptions(sectionSelect, 'Loading sections...', true);

                    try {
                        const json = await postJson(sectionEndpoint, { sessionId, classId });
                        if (version !== requestVersion) return;

                        const rows = Array.isArray(json?.sections) ? json.sections : [];
                        replaceOptions(sectionSelect, 'All', false);
                        populateOptions(sectionSelect, rows, restoreSelection ? defaults.sectionId : '');
                        sectionLoading = false;
                        await syncDepartmentState(restoreSelection);
                    } catch (error) {
                        if (version !== requestVersion) return;
                        sectionLoading = false;
                        replaceOptions(sectionSelect, 'Unable to load sections', true);
                    }
                }

                async function loadDepartments(restoreSelection = false) {
                    const sessionId = sessionSelect.value || '';
                    const classId = classSelect.value || '';
                    const sectionId = sectionSelect.value || '';
                    const version = ++requestVersion;
                    resetDepartments();

                    if (!sessionId || !classId || !classSupportsDepartment() || !hasValidSectionScope(sectionId)) {
                        return;
                    }

                    replaceOptions(departmentSelect, 'Loading departments...', true);

                    try {
                        const json = await postJson(departmentEndpoint, { sessionId, classId, sectionId });
                        if (version !== requestVersion) return;

                        const rows = Array.isArray(json?.departments) ? json.departments : [];
                        replaceOptions(departmentSelect, 'All', false);
                        populateOptions(departmentSelect, rows, restoreSelection ? defaults.departmentId : '');
                    } catch (error) {
                        if (version !== requestVersion) return;
                        replaceOptions(departmentSelect, 'Unable to load departments', true);
                    }
                }

                function bindChange(select, handler) {
                    if (window.jQuery) {
                        window.jQuery(select).off('.curriculumCopy').on('change.curriculumCopy', handler);
                        return;
                    }

                    select.addEventListener('change', handler);
                }

                bindChange(sessionSelect, function () {
                    loadClasses(false);
                });

                bindChange(classSelect, function () {
                    resetSections();
                    loadSections(false);
                });

                bindChange(sectionSelect, function () {
                    syncDepartmentState(false);
                });

                if (sessionSelect.value) {
                    loadClasses(true);
                } else {
                    resetClasses();
                }
            }

            bindScope('copy_source');
            bindScope('copy_target');
        });
    </script>
@endif
@endsection
