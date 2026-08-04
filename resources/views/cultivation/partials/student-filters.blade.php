@php($options = $filterOptions)
<form method="GET" action="{{ $action }}" class="row g-2 align-items-end student-filter-form">
    <div class="col-auto"><label class="form-label">Session</label><select name="sessionId" class="form-select student-dependent-filter"><option value="">All</option>@foreach($options['sessions'] as $item)<option value="{{ $item->id }}" @selected(($filters['sessionId'] ?? null) == $item->id)>{{ $item->session }}</option>@endforeach</select></div>
    <div class="col-auto"><label class="form-label">Class</label><select name="classId" class="form-select student-dependent-filter"><option value="">All</option>@foreach($options['classes'] as $item)<option value="{{ $item->id }}" @selected(($filters['classId'] ?? null) == $item->id)>{{ $item->className }}</option>@endforeach</select></div>
    <div class="col-auto"><label class="form-label">Section</label><select name="sectionId" class="form-select student-dependent-filter"><option value="">All</option>@foreach($options['sections'] as $item)<option value="{{ $item->id }}" @selected(($filters['sectionId'] ?? null) == $item->id)>{{ $item->section }}</option>@endforeach</select></div>
    <div class="col-auto"><label class="form-label">Department</label><select name="departmentId" class="form-select student-dependent-filter"><option value="">All</option>@foreach($options['departments'] as $item)<option value="{{ $item->id }}" @selected(($filters['departmentId'] ?? null) == $item->id)>{{ $item->departmentName }}</option>@endforeach</select></div>
    <div class="col-auto"><label class="form-label">Gender</label><select name="gender" class="form-select"><option value="">All</option>@foreach($options['genderOptions'] as $value => $label)<option value="{{ $value }}" @selected(($filters['gender'] ?? null) === (string) $value)>{{ $label }}</option>@endforeach</select></div>
    <div class="col-auto"><label class="form-label">Search</label><input name="search" type="search" class="form-control" value="{{ $filters['search'] ?? '' }}" placeholder="Name / Student ID / Phone"></div>
    <div class="col-auto"><button type="submit" class="btn btn-primary">Filter</button><a href="{{ $action }}" class="btn btn-light">Reset</a></div>
</form>
@once
@push('scripts')
<script>
document.addEventListener('change', function (event) {
    const select = event.target.closest('.student-dependent-filter');
    if (!select) return;
    const form = select.closest('.student-filter-form');
    const order = ['sessionId', 'classId', 'sectionId', 'departmentId'];
    const changed = order.indexOf(select.name);
    order.slice(changed + 1).forEach(name => {
        const dependent = form.querySelector('[name="' + name + '"]');
        if (dependent) dependent.value = '';
    });
    form.submit();
});
</script>
@endpush
@endonce
