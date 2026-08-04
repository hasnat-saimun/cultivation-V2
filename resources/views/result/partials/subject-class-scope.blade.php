@php
    $selected = collect(old('classIds', $selectedClassIds ?? []))->map(fn ($id) => (int) $id)->all();
    $allSelected = (bool) old('allClasses', $allClasses ?? false);
@endphp
<div class="col-12 form-group">
    <label class="d-block">Class Scope *</label>
    <div class="form-check mb-3">
        <input class="form-check-input" type="checkbox" name="allClasses" id="allClasses" value="1" {{ $allSelected ? 'checked' : '' }}>
        <label class="form-check-label font-weight-bold" for="allClasses">All Classes</label>
    </div>
    <div class="row" id="subjectClassChoices">
        @foreach($classList as $class)
            <div class="col-sm-6 col-lg-4 mb-2">
                <div class="form-check">
                    <input class="form-check-input subject-class-choice" type="checkbox" name="classIds[]"
                           id="subjectClass{{ $class->id }}" value="{{ $class->id }}"
                           {{ in_array((int) $class->id, $selected, true) ? 'checked' : '' }}>
                    <label class="form-check-label" for="subjectClass{{ $class->id }}">{{ $class->className }}</label>
                </div>
            </div>
        @endforeach
    </div>
    @error('classIds')<div class="text-danger mt-2">{{ $message }}</div>@enderror
    @error('classIds.*')<div class="text-danger mt-2">{{ $message }}</div>@enderror
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const all = document.getElementById('allClasses');
    const choices = Array.from(document.querySelectorAll('.subject-class-choice'));
    const sync = () => {
        choices.forEach(choice => {
            choice.disabled = all.checked;
            if (all.checked) choice.checked = false;
        });
        document.getElementById('subjectClassChoices').style.opacity = all.checked ? '.5' : '1';
    };
    all.addEventListener('change', sync);
    sync();
});
</script>
