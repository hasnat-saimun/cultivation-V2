@extends('result.include')
@section('backTitle', 'Split/Migrate Subject Scope')
@section('backIndex')
@php
    $currentRemain = old('remain', $payload['remain'] ?? $sourceClassIds);
    $mode = old('destination_mode', isset($payload) && !($payload['create_destination'] ?? true) ? 'existing' : 'create');
    $destinationId = old('destination_id', $payload['destination_id'] ?? '');
    $classesById = $classList->keyBy('id');
@endphp
<div class="row gutters-20 mt-4 mb-4">
    <div class="col-12 col-xl-10 mx-auto">
        <div class="card height-auto">
            <div class="card-header bg-light d-flex justify-content-between align-items-center">
                <div><h3 class="mb-1">Split/Migrate Subject Scope</h3><small>{{ $source->subjectName }} (#{{ $source->id }})</small></div>
                <a href="{{ route('allSubject') }}" class="btn btn-secondary">Back to Subjects</a>
            </div>
            <div class="card-body">
                @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
                @if(session('migrationResult'))
                    <div class="card border-success mb-4">
                        <div class="card-header"><strong>Applied Migration Audit</strong></div>
                        <div class="card-body">
                            <div><strong>Operation:</strong> {{ session('migrationResult.operation_uuid') }}</div>
                            <div><strong>Destination subject:</strong> #{{ session('migrationResult.destination_id') }}</div>
                            <div><strong>Total references migrated:</strong> {{ collect(session('migrationResult.counts'))->sum() }}</div>
                        </div>
                    </div>
                @endif
                @if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

                <div class="alert alert-info">Dry-run is required before apply. The source subject is never deleted and raw marks are not modified.</div>
                <div class="border rounded p-3 mb-4 bg-light">
                    <small class="text-muted text-uppercase">Current Subject</small>
                    <h4 class="mb-3">{{ $source->subjectName }} <span class="text-muted">(#{{ $source->id }})</span></h4>
                    <small class="text-muted text-uppercase d-block mb-2">Current Scope</small>
                    <div class="d-flex flex-wrap" style="gap:.75rem;">
                        @foreach($classList as $class)
                            <label class="mb-0"><input type="checkbox" checked disabled> {{ $class->className }}</label>
                        @endforeach
                    </div>
                </div>
                <form id="splitPreviewForm" method="POST" action="{{ route('subject.scope.split.preview', $source->id) }}">
                    @csrf
                    <div class="form-group border rounded p-3">
                        <h5 class="mb-1">Classes that will REMAIN with this subject</h5>
                        <p class="text-muted">Unselected classes automatically migrate. Every current class is always in exactly one outcome.</p>
                        <div class="d-flex flex-wrap" style="gap:1rem;">
                        @foreach($classList as $class)
                            <div class="form-check mb-0">
                                <input class="form-check-input" type="checkbox" name="remain[]" value="{{ $class->id }}" id="remain{{ $class->id }}" {{ in_array($class->id, array_map('intval', $currentRemain), true) ? 'checked' : '' }}>
                                <label class="form-check-label" for="remain{{ $class->id }}">{{ $class->className }}</label>
                            </div>
                        @endforeach
                        </div>
                    </div>
                    <div class="row mb-4">
                        <div class="col-md-6 mb-2 mb-md-0">
                            <div class="alert alert-primary h-100 mb-0"><strong>Source subject will remain in:</strong><div id="liveRemainSummary" class="mt-1"></div></div>
                        </div>
                        <div class="col-md-6">
                            <div class="alert alert-warning h-100 mb-0"><strong>Destination subject will receive:</strong><div id="liveMigrateSummary" class="mt-1"></div></div>
                        </div>
                    </div>
                    @if($legacyClassList->isNotEmpty())
                        <div class="alert alert-secondary">
                            <h5>Unresolved / legacy scope</h5>
                            <p class="mb-2">These are not treated as academic classes and are never migrated automatically.</p>
                            @foreach($legacyClassList as $legacyClass)
                                <div class="form-group mb-2">
                                    <label for="legacyScope{{ $legacyClass->id }}"><strong>{{ $legacyClass->className }}</strong></label>
                                    <select class="form-control" id="legacyScope{{ $legacyClass->id }}" name="legacy_scope_resolution[{{ $legacyClass->id }}]">
                                        <option value="">Unresolved — decide before Apply</option>
                                        <option value="keep_source" {{ old('legacy_scope_resolution.'.$legacyClass->id, $payload['legacy_scope_resolutions'][$legacyClass->id] ?? '') === 'keep_source' ? 'selected' : '' }}>Keep with source</option>
                                    </select>
                                </div>
                            @endforeach
                        </div>
                    @endif
                    <hr>
                    <div class="form-group">
                        <label class="font-weight-bold d-block">Destination</label>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input destination-mode" type="radio" name="destination_mode" id="createDestination" value="create" {{ $mode === 'create' ? 'checked' : '' }}>
                            <label class="form-check-label" for="createDestination">Create compatible destination</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input destination-mode" type="radio" name="destination_mode" id="existingDestination" value="existing" {{ $mode === 'existing' ? 'checked' : '' }}>
                            <label class="form-check-label" for="existingDestination">Use existing compatible subject</label>
                        </div>
                    </div>
                    <div class="form-group" id="destinationSelectWrap">
                        <select name="destination_id" id="destinationSubject" class="form-control select2" data-placeholder="Search compatible subject">
                            <option value="">Select destination</option>
                            @foreach($destinations as $destination)
                                <option value="{{ $destination->id }}" {{ (string) $destinationId === (string) $destination->id ? 'selected' : '' }}>
                                    #{{ $destination->id }} — {{ $destination->subjectName }} ({{ $destination->assign_class ?: 'no class' }})
                                </option>
                            @endforeach
                        </select>
                        @if($destinations->isEmpty())<small class="text-muted">No same-name destination exists. Choose Create compatible destination.</small>@endif
                    </div>
                    <button class="btn btn-primary" type="submit">Run Dry-Run Preview</button>
                </form>

                @isset($preview)
                    <hr class="my-4">
                    <h4>Dry-Run Preview</h4>
                    <div class="border rounded p-3 mb-3">
                        <div class="row text-center align-items-center">
                            <div class="col-md-5"><small class="text-muted">CURRENT SUBJECT</small><h5>{{ $source->subjectName }} (#{{ $source->id }})</h5></div>
                            <div class="col-md-2 h3">&darr;</div>
                            <div class="col-md-5"><small class="text-muted">DESTINATION SUBJECT</small><h5>{{ $preview['destination']?->subjectName ?? $source->subjectName }} ({{ $preview['destination'] ? '#'.$preview['destination']->id : 'new' }})</h5></div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-md-6"><strong>Remain</strong><div>{{ collect($payload['remain'])->filter(fn($id) => $classesById->has($id))->map(fn($id) => $classesById[$id]->className)->implode(', ') ?: 'None' }}</div></div>
                            <div class="col-md-6"><strong>Migrate</strong><div>{{ collect($payload['migrate'])->map(fn($id) => $classesById[$id]->className ?? '#'.$id)->implode(', ') }}</div></div>
                        </div>
                    </div>
                    <div class="row mt-3">
                        @foreach([
                            'Affected marks' => $preview['mark_count'], 'Students' => $preview['student_count'],
                            'Exams' => $preview['exam_count'], 'Sessions' => $preview['session_count'],
                            'Teacher assignments' => $preview['teacher_assignment_count'],
                            'Curriculum mappings' => $preview['curriculum_mapping_count'],
                        ] as $label => $value)
                            <div class="col-6 col-md-4 mb-3"><div class="border rounded p-3 h-100"><small class="text-muted">{{ $label }}</small><div class="h4 mb-0">{{ $value }}</div></div></div>
                        @endforeach
                    </div>
                    @php
                        $teacherResolution = $preview['teacher_resolution'] ?? ['rows'=>[], 'auto_resolved'=>0, 'manual_unresolved'=>0];
                        $manualTeacherRows = collect($teacherResolution['rows'])->where('automatic', false);
                    @endphp
                    <div class="card mb-3">
                        <div class="card-header"><strong>Teacher subject references</strong></div>
                        <div class="card-body">
                            <div class="mb-2"><span class="badge badge-success">Auto-resolved: {{ $teacherResolution['auto_resolved'] }}</span> <span class="badge badge-warning">Manual: {{ $teacherResolution['manual_unresolved'] }}</span></div>
                            @foreach($teacherResolution['rows'] as $teacherRow)
                                <div class="border rounded p-2 mb-2">
                                    <strong>{{ $teacherRow['teacher_name'] }}</strong> — Subject #{{ $teacherRow['subject_id'] }}<br>
                                    <small>Known classes: {{ collect($teacherRow['known_class_ids'])->map(fn($id) => $allClassNames[$id] ?? '#'.$id)->implode(', ') ?: 'None' }}</small>
                                    @if($teacherRow['automatic'])
                                        <div class="text-success">Resolved through class-scoped assignments: {{ ucfirst($teacherRow['action']) }}</div>
                                    @else
                                        <div class="text-warning">Exact class ownership could not be proven. Choose a resolution in the Apply section.</div>
                                    @endif
                                </div>
                            @endforeach
                            @if(empty($teacherResolution['rows']))<span class="text-muted">No classless Teacher subject references.</span>@endif
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm">
                            <thead><tr><th>Reference</th><th>Affected rows</th></tr></thead>
                            <tbody>
                            @foreach($preview['counts'] as $reference => $count)
                                <tr><td>{{ $reference }}</td><td>{{ $count }}</td></tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                    @php $hasUnresolved = !empty($payload['legacy_unresolved']) || $manualTeacherRows->isNotEmpty(); @endphp
                    <div class="alert {{ $hasUnresolved ? 'alert-warning' : 'alert-success' }}"><strong>Conflict:</strong> {{ $hasUnresolved ? 'Manual resolution required before Apply.' : 'None.' }}</div>
                    <form method="POST" action="{{ route('subject.scope.split.apply', $source->id) }}" onsubmit="return confirm('Apply this subject scope migration transactionally?');">
                        @csrf
                        @foreach(collect($payload['remain'])->filter(fn($id) => $classesById->has($id)) as $id)<input type="hidden" name="remain[]" value="{{ $id }}">@endforeach
                        <input type="hidden" name="destination_mode" value="{{ $payload['create_destination'] ? 'create' : 'existing' }}">
                        @if($payload['destination_id'])<input type="hidden" name="destination_id" value="{{ $payload['destination_id'] }}">@endif
                        @foreach($legacyClassList as $legacyClass)
                            <div class="form-group">
                                <label for="applyLegacy{{ $legacyClass->id }}">Legacy scope: {{ $legacyClass->className }}</label>
                                <select class="form-control" id="applyLegacy{{ $legacyClass->id }}" name="legacy_scope_resolution[{{ $legacyClass->id }}]" required>
                                    <option value="">Select explicit handling</option>
                                    <option value="keep_source" {{ ($payload['legacy_scope_resolutions'][$legacyClass->id] ?? '') === 'keep_source' ? 'selected' : '' }}>Keep with source (never auto-migrate)</option>
                                </select>
                            </div>
                        @endforeach
                        @foreach($manualTeacherRows as $teacherRow)
                            <div class="form-group border rounded p-3">
                                <label for="teacherResolution{{ $teacherRow['row_id'] }}"><strong>{{ $teacherRow['teacher_name'] }}</strong> — classless subject assignment</label>
                                <div class="small text-muted mb-2">Known class assignments: {{ collect($teacherRow['known_class_ids'])->map(fn($id) => $allClassNames[$id] ?? '#'.$id)->implode(', ') ?: 'None' }}</div>
                                <select class="form-control" id="teacherResolution{{ $teacherRow['row_id'] }}" name="teacher_resolution[{{ $teacherRow['row_id'] }}]" required>
                                    <option value="">Select resolution</option>
                                    <option value="keep">Keep with source</option>
                                    <option value="move">Move to destination</option>
                                    <option value="both">Assign to both</option>
                                    <option value="scoped">Resolve through class-scoped assignments</option>
                                </select>
                            </div>
                        @endforeach
                        <div class="form-group">
                            <label for="confirmation">Type <strong>APPLY</strong> to confirm</label>
                            <input class="form-control" id="confirmation" name="confirmation" autocomplete="off" required pattern="APPLY">
                        </div>
                        <button class="btn btn-danger" type="submit">Apply Scope Migration</button>
                    </form>
                @endisset
            </div>
        </div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const classNames = @json($classList->pluck('className', 'id'));
    const remainBoxes = Array.from(document.querySelectorAll('#splitPreviewForm input[type="checkbox"][name="remain[]"]'));
    const liveRemain = document.getElementById('liveRemainSummary');
    const liveMigrate = document.getElementById('liveMigrateSummary');
    const dryRunButton = document.querySelector('#splitPreviewForm button[type="submit"]');
    const syncScopeSummary = () => {
        const remain = remainBoxes.filter(box => box.checked).map(box => classNames[box.value]);
        const migrate = remainBoxes.filter(box => !box.checked).map(box => classNames[box.value]);
        liveRemain.textContent = remain.length ? remain.join(', ') : 'Select at least one class';
        liveMigrate.textContent = migrate.length ? migrate.join(', ') : 'Uncheck at least one class';
        liveRemain.closest('.alert').classList.toggle('alert-danger', remain.length === 0);
        liveMigrate.closest('.alert').classList.toggle('alert-danger', migrate.length === 0);
        dryRunButton.disabled = remain.length === 0 || migrate.length === 0;
    };
    remainBoxes.forEach(box => box.addEventListener('change', syncScopeSummary));
    syncScopeSummary();
    const syncDestination = () => {
        const create = document.getElementById('createDestination').checked;
        document.getElementById('destinationSelectWrap').style.display = create ? 'none' : 'block';
    };
    document.querySelectorAll('.destination-mode').forEach(input => input.addEventListener('change', syncDestination));
    syncDestination();
    if (window.jQuery && jQuery.fn.select2) jQuery('#destinationSubject').select2({width:'100%', placeholder:'Search compatible subject'});
});
</script>
@endsection
