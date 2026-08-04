@extends('result.include')
@section('backTitle', 'Split/Migrate Subject Scope')
@section('backIndex')
@php
    $currentRemain = old('remain', $payload['remain'] ?? []);
    $currentMigrate = old('migrate', $payload['migrate'] ?? []);
    $mode = old('destination_mode', ($payload['create_destination'] ?? false) ? 'create' : 'existing');
    $destinationId = old('destination_id', $payload['destination_id'] ?? '');
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
                <form method="POST" action="{{ route('subject.scope.split.preview', $source->id) }}">
                    @csrf
                    <div class="row">
                        <div class="col-md-6">
                            <h5>Classes remaining with source</h5>
                            @foreach($classList as $class)
                                <div class="form-check mb-2">
                                    <input class="form-check-input scope-choice" type="checkbox" name="remain[]" value="{{ $class->id }}" id="remain{{ $class->id }}" {{ in_array($class->id, array_map('intval', $currentRemain), true) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="remain{{ $class->id }}">{{ $class->className }}</label>
                                </div>
                            @endforeach
                        </div>
                        <div class="col-md-6">
                            <h5>Classes migrating to destination</h5>
                            @foreach($classList as $class)
                                <div class="form-check mb-2">
                                    <input class="form-check-input scope-choice" type="checkbox" name="migrate[]" value="{{ $class->id }}" id="migrate{{ $class->id }}" {{ in_array($class->id, array_map('intval', $currentMigrate), true) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="migrate{{ $class->id }}">{{ $class->className }}</label>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    <hr>
                    <div class="form-group">
                        <label class="font-weight-bold d-block">Destination</label>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input destination-mode" type="radio" name="destination_mode" id="existingDestination" value="existing" {{ $mode === 'existing' ? 'checked' : '' }}>
                            <label class="form-check-label" for="existingDestination">Select compatible subject</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input destination-mode" type="radio" name="destination_mode" id="createDestination" value="create" {{ $mode === 'create' ? 'checked' : '' }}>
                            <label class="form-check-label" for="createDestination">Create compatible destination</label>
                        </div>
                    </div>
                    <div class="form-group" id="destinationSelectWrap">
                        <select name="destination_id" class="form-control">
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
                    <div class="alert alert-success">No compatibility, overlap, collision, archive, or classless-reference blockers were found.</div>
                    <form method="POST" action="{{ route('subject.scope.split.apply', $source->id) }}" onsubmit="return confirm('Apply this subject scope migration transactionally?');">
                        @csrf
                        @foreach($payload['remain'] as $id)<input type="hidden" name="remain[]" value="{{ $id }}">@endforeach
                        @foreach($payload['migrate'] as $id)<input type="hidden" name="migrate[]" value="{{ $id }}">@endforeach
                        <input type="hidden" name="destination_mode" value="{{ $payload['create_destination'] ? 'create' : 'existing' }}">
                        @if($payload['destination_id'])<input type="hidden" name="destination_id" value="{{ $payload['destination_id'] }}">@endif
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
    document.querySelectorAll('.scope-choice').forEach(function (choice) {
        choice.addEventListener('change', function () {
            const otherName = this.name === 'remain[]' ? 'migrate[]' : 'remain[]';
            const other = document.querySelector('input[name="' + otherName + '"][value="' + this.value + '"]');
            if (this.checked && other) other.checked = false;
        });
    });
    const syncDestination = () => {
        const create = document.getElementById('createDestination').checked;
        document.getElementById('destinationSelectWrap').style.display = create ? 'none' : 'block';
    };
    document.querySelectorAll('.destination-mode').forEach(input => input.addEventListener('change', syncDestination));
    syncDestination();
});
</script>
@endsection
