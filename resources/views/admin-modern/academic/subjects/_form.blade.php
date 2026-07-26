@php
    $subjectItem = $item ?? null;
    $selectedSubjectType = old('subjectType', (string) ($subjectItem->subjectType ?? 'Main'));
    $selectedClassId = old('classId', isset($subjectItem) ? (string) ($subjectItem->assign_class ?? '') : '');
    $cqValue = old('cqValue', isset($subjectItem) ? $subjectItem->CQ : '');
    $mcqValue = old('mcqValue', isset($subjectItem) ? $subjectItem->MCQ : '');
    $practicalValue = old('practicalValue', isset($subjectItem) ? $subjectItem->Practical : '');
    $isReligiousChecked = old('isReligious', (int) ($subjectItem->isReligious ?? 0)) ? true : false;
    $selectedDefaultClassIds = collect(old('defaultReligiousClasses', $defaultClassIds ?? []))
        ->map(fn ($id) => (int) $id)
        ->filter(fn ($id) => $id > 0)
        ->values()
        ->all();
    $allClassIds = ($classList ?? collect([]))->pluck('id')->map(fn ($id) => (int) $id)->all();
    $isDefaultAll = old('defaultReligiousForAllClass') !== null
        ? (bool) old('defaultReligiousForAllClass')
        : (!empty($allClassIds) && empty(array_diff($allClassIds, $selectedDefaultClassIds)));
@endphp

<form class="new-added-form" action="{{ $actionRoute }}" method="POST" enctype="multipart/form-data">
    @csrf
    @if(isset($subjectItem))
        <input type="hidden" name="itemId" value="{{ $subjectItem->id }}">
    @endif

    <div class="am-grid am-grid-2" style="margin-bottom: 0.7rem;">
        <div>
            <label for="subjectName" style="display:block; font-weight:600; margin-bottom:0.35rem;">Subject Name *</label>
            <input id="subjectName" type="text" name="subjectName" value="{{ old('subjectName', $subjectItem->subjectName ?? '') }}" placeholder="Enter subject name" style="width:100%; border:1px solid var(--am-border); border-radius:10px; padding:0.55rem 0.7rem; background:var(--am-surface); color:var(--am-text);" required>
        </div>

        <div>
            <label for="subjectType" style="display:block; font-weight:600; margin-bottom:0.35rem;">Subject Type *</label>
            <select id="subjectType" name="subjectType" style="width:100%; border:1px solid var(--am-border); border-radius:10px; padding:0.55rem 0.7rem; background:var(--am-surface); color:var(--am-text);">
                <option value="Main" {{ $selectedSubjectType === 'Main' ? 'selected' : '' }}>Main</option>
                <option value="Optional" {{ $selectedSubjectType === 'Optional' ? 'selected' : '' }}>Optional</option>
            </select>
        </div>
    </div>

    <div class="am-grid am-grid-2" style="margin-bottom: 0.7rem;">
        <div>
            <label for="classId" style="display:block; font-weight:600; margin-bottom:0.35rem;">Assign Class *</label>
            <select id="classId" name="classId" style="width:100%; border:1px solid var(--am-border); border-radius:10px; padding:0.55rem 0.7rem; background:var(--am-surface); color:var(--am-text);">
                <option value="">Select Class</option>
                <option value="0" {{ $selectedClassId === '0' ? 'selected' : '' }}>All</option>
                @if(($classList ?? collect([]))->count() > 0)
                    @foreach($classList as $class)
                        <option value="{{ $class->id }}" {{ $selectedClassId === (string) $class->id ? 'selected' : '' }}>{{ $class->className }}</option>
                    @endforeach
                @endif
            </select>
        </div>

        <div>
            <label style="display:block; font-weight:600; margin-bottom:0.35rem;">Available Feature *</label>
            <div style="display:flex; gap:1rem; flex-wrap:wrap; margin-bottom:0.5rem;">
                <label style="display:flex; align-items:center; gap:0.4rem;">
                    <input onclick="cqMarks(this)" name="availableFeature[]" type="checkbox" id="CQ" value="CQ" {{ $cqValue !== null && $cqValue !== '' ? 'checked' : '' }}>
                    <span>CQ</span>
                </label>
                <label style="display:flex; align-items:center; gap:0.4rem;">
                    <input onclick="mcqMarks(this)" name="availableFeature[]" type="checkbox" id="MCQ" value="MCQ" {{ $mcqValue !== null && $mcqValue !== '' ? 'checked' : '' }}>
                    <span>MCQ</span>
                </label>
                <label style="display:flex; align-items:center; gap:0.4rem;">
                    <input onclick="practicalMarks(this)" name="availableFeature[]" type="checkbox" id="Practical" value="Practical" {{ $practicalValue !== null && $practicalValue !== '' ? 'checked' : '' }}>
                    <span>Practical</span>
                </label>
            </div>
            <div class="am-grid am-grid-3">
                <div id="cqFiled"></div>
                <div id="mcqFiled"></div>
                <div id="practicalFiled"></div>
            </div>
        </div>
    </div>

    <div style="margin-bottom: 0.7rem;">
        <label style="display:flex; align-items:center; gap:0.45rem;">
            <input type="checkbox" id="isReligious" name="isReligious" value="1" {{ $isReligiousChecked ? 'checked' : '' }}>
            <span>Mark as Religious Subject</span>
        </label>
    </div>

    <div id="defaultRelSixWrap" style="display:none; margin-bottom: 0.7rem;">
        <label style="display:flex; align-items:center; gap:0.45rem;">
            <input type="checkbox" id="defaultReligiousForAllClass" name="defaultReligiousForAllClass" value="1" {{ $isDefaultAll ? 'checked' : '' }}>
            <span>Set as default Religious subject for All Class</span>
        </label>
    </div>

    <div id="defaultRelAllWrap" style="display:none; margin-bottom: 0.7rem;">
        <label style="display:block; font-weight:600; margin-bottom:0.35rem;">Set as default Religious subject for classes</label>
        <div class="am-grid am-grid-2">
            @if(($classList ?? collect([]))->count() > 0)
                @foreach($classList as $class)
                    @php $checked = in_array((int) $class->id, $selectedDefaultClassIds, true); @endphp
                    <label style="display:flex; align-items:center; gap:0.45rem;">
                        <input type="checkbox" id="defaultRelClass{{ $class->id }}" name="defaultReligiousClasses[]" value="{{ $class->id }}" {{ $checked ? 'checked' : '' }}>
                        <span>{{ $class->className }}</span>
                    </label>
                @endforeach
            @endif
        </div>
    </div>

    <div class="am-btn-row">
        <button type="submit" class="am-btn-primary" style="border:0; cursor:pointer;">{{ $submitLabel ?? 'Save' }}</button>
        <button type="reset" class="am-btn-outline" style="cursor:pointer;">Reset</button>
    </div>
</form>

<script>
    function validateTotalMarks() {
        const cq = document.querySelector("input[name='cqValue'][type='text']");
        const mcq = document.querySelector("input[name='mcqValue'][type='text']");
        const practical = document.querySelector("input[name='practicalValue'][type='text']");

        const cqVal = cq ? parseInt(cq.value, 10) || 0 : 0;
        const mcqVal = mcq ? parseInt(mcq.value, 10) || 0 : 0;
        const practicalVal = practical ? parseInt(practical.value, 10) || 0 : 0;

        if (cqVal + mcqVal + practicalVal > 100) {
            alert('Total marks for CQ, MCQ, and Practical cannot exceed 100.');
            return false;
        }

        return true;
    }

    document.addEventListener('DOMContentLoaded', function() {
        const form = document.querySelector('form.new-added-form');
        if (form) {
            form.onsubmit = function() {
                return validateTotalMarks();
            };
        }

        const isRel = document.getElementById('isReligious');
        const wrapSix = document.getElementById('defaultRelSixWrap');
        const wrapAll = document.getElementById('defaultRelAllWrap');
        const allToggle = document.getElementById('defaultReligiousForAllClass');

        if (isRel) {
            const toggle = function() {
                const show = isRel.checked ? 'block' : 'none';
                if (wrapSix) wrapSix.style.display = show;
                if (wrapAll) wrapAll.style.display = show;
            };

            isRel.addEventListener('change', toggle);
            toggle();
        }

        if (allToggle) {
            allToggle.addEventListener('change', function() {
                const boxes = document.querySelectorAll("input[name='defaultReligiousClasses[]']");
                boxes.forEach(function(box) {
                    box.checked = allToggle.checked;
                });
            });
        }

        const cq = document.getElementById('CQ');
        const mcq = document.getElementById('MCQ');
        const practical = document.getElementById('Practical');

        if (cq && cq.checked) cqMarks(cq);
        if (mcq && mcq.checked) mcqMarks(mcq);
        if (practical && practical.checked) practicalMarks(practical);
    });

    function mcqMarks(checkbox) {
        const value = @json((string) $mcqValue);
        if (checkbox.checked) {
            document.getElementById('mcqFiled').innerHTML = "<label for='mcqMarksValue' style='display:block; font-weight:600; margin-bottom:0.35rem;'>MCQ Marks</label><input type='text' name='mcqValue' style='width:100%; border:1px solid var(--am-border); border-radius:10px; padding:0.55rem 0.7rem; background:var(--am-surface); color:var(--am-text);' placeholder='Enter the mcq total marks' value='" + value + "'>";
        } else {
            document.getElementById('mcqFiled').innerHTML = '';
        }
    }

    function cqMarks(checkbox) {
        const value = @json((string) $cqValue);
        if (checkbox.checked) {
            document.getElementById('cqFiled').innerHTML = "<label for='cqMarksValue' style='display:block; font-weight:600; margin-bottom:0.35rem;'>CQ Marks</label><input type='text' name='cqValue' style='width:100%; border:1px solid var(--am-border); border-radius:10px; padding:0.55rem 0.7rem; background:var(--am-surface); color:var(--am-text);' placeholder='Enter the cq total marks' value='" + value + "'>";
        } else {
            document.getElementById('cqFiled').innerHTML = '';
        }
    }

    function practicalMarks(checkbox) {
        const value = @json((string) $practicalValue);
        if (checkbox.checked) {
            document.getElementById('practicalFiled').innerHTML = "<label for='practicalMarksValue' style='display:block; font-weight:600; margin-bottom:0.35rem;'>Practical Marks</label><input type='text' name='practicalValue' style='width:100%; border:1px solid var(--am-border); border-radius:10px; padding:0.55rem 0.7rem; background:var(--am-surface); color:var(--am-text);' placeholder='Enter the practical total marks' value='" + value + "'>";
        } else {
            document.getElementById('practicalFiled').innerHTML = '';
        }
    }
</script>