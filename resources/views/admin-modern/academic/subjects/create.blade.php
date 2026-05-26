@extends('admin-modern.layouts.app')

@section('title', 'Create Subject')

@section('content')
    <x-admin-modern.page-header
        title="Create Subject"
        subtitle="Modern parallel wrapper for subject creation using existing ERP submit flow"
        :breadcrumb="['Home', 'Academic', 'Subject List', 'Create Subject']"
    />

    <x-admin-modern.table-shell title="Add New Subject">
        <div class="am-btn-row" style="margin-bottom: 0.7rem;">
            <a href="{{ route('adminModernAcademicSubjectsIndex') }}" class="am-btn-outline">Subject List</a>
        </div>

        <form class="new-added-form" action="{{ route('confirmSubject') }}" method="POST" enctype="multipart/form-data">
            @csrf

            <div class="am-grid am-grid-2" style="margin-bottom: 0.7rem;">
                <div>
                    <label for="subjectName" style="display:block; font-weight:600; margin-bottom:0.35rem;">Subject Name *</label>
                    <input id="subjectName" type="text" name="subjectName" placeholder="Enter subject name" style="width:100%; border:1px solid var(--am-border); border-radius:10px; padding:0.55rem 0.7rem; background:var(--am-surface); color:var(--am-text);" required>
                </div>

                <div>
                    <label for="subjectType" style="display:block; font-weight:600; margin-bottom:0.35rem;">Subject Type *</label>
                    <select id="subjectType" name="subjectType" style="width:100%; border:1px solid var(--am-border); border-radius:10px; padding:0.55rem 0.7rem; background:var(--am-surface); color:var(--am-text);">
                        <option value="Main">Main</option>
                        <option value="Optional">Optional</option>
                    </select>
                </div>
            </div>

            <div class="am-grid am-grid-2" style="margin-bottom: 0.7rem;">
                <div>
                    <label for="classId" style="display:block; font-weight:600; margin-bottom:0.35rem;">Assign Class *</label>
                    <select id="classId" name="classId" style="width:100%; border:1px solid var(--am-border); border-radius:10px; padding:0.55rem 0.7rem; background:var(--am-surface); color:var(--am-text);">
                        <option value="">Select Class</option>
                        <option value="0">All</option>
                        @if($classList->count() > 0)
                            @foreach($classList as $class)
                                <option value="{{ $class->id }}">{{ $class->className }}</option>
                            @endforeach
                        @endif
                    </select>
                </div>

                <div>
                    <label style="display:block; font-weight:600; margin-bottom:0.35rem;">Available Feature *</label>
                    <div style="display:flex; gap:1rem; flex-wrap:wrap; margin-bottom:0.5rem;">
                        <label style="display:flex; align-items:center; gap:0.4rem;">
                            <input onclick="cqMarks(this)" name="cqValue" type="checkbox" id="CQ" value="CQ">
                            <span>CQ</span>
                        </label>
                        <label style="display:flex; align-items:center; gap:0.4rem;">
                            <input onclick="mcqMarks(this)" name="mcqValue" type="checkbox" id="MCQ" value="MCQ">
                            <span>MCQ</span>
                        </label>
                        <label style="display:flex; align-items:center; gap:0.4rem;">
                            <input onclick="practicalMarks(this)" name="practicalValue" type="checkbox" id="Practical" value="Practical">
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
                    <input type="checkbox" id="isReligious" name="isReligious" value="1">
                    <span>Mark as Religious Subject</span>
                </label>
            </div>

            <div id="defaultRelSixWrap" style="display:none; margin-bottom: 0.7rem;">
                <label style="display:flex; align-items:center; gap:0.45rem;">
                    <input type="checkbox" id="defaultReligiousForAllClass" name="defaultReligiousForAllClass" value="1">
                    <span>Set as default Religious subject for All Class</span>
                </label>
            </div>

            <div id="defaultRelAllWrap" style="display:none; margin-bottom: 0.7rem;">
                <label style="display:block; font-weight:600; margin-bottom:0.35rem;">Set as default Religious subject for classes</label>
                <div class="am-grid am-grid-2">
                    @if($classList->count() > 0)
                        @foreach($classList as $class)
                            <label style="display:flex; align-items:center; gap:0.45rem;">
                                <input type="checkbox" id="defaultRelClass{{ $class->id }}" name="defaultReligiousClasses[]" value="{{ $class->id }}">
                                <span>{{ $class->className }}</span>
                            </label>
                        @endforeach
                    @endif
                </div>
            </div>

            <div class="am-btn-row">
                <button type="submit" class="am-btn-primary" style="border:0; cursor:pointer;">Save</button>
                <button type="reset" class="am-btn-outline" style="cursor:pointer;">Reset</button>
            </div>
        </form>
    </x-admin-modern.table-shell>

    <script>
        function validateTotalMarks() {
            let cq = document.querySelector("input[name='cqValue'][type='text']");
            let mcq = document.querySelector("input[name='mcqValue'][type='text']");
            let practical = document.querySelector("input[name='practicalValue'][type='text']");

            let cqVal = cq ? parseInt(cq.value) || 0 : 0;
            let mcqVal = mcq ? parseInt(mcq.value) || 0 : 0;
            let practicalVal = practical ? parseInt(practical.value) || 0 : 0;

            let total = cqVal + mcqVal + practicalVal;

            if (total > 100) {
                alert("Total marks for CQ, MCQ, and Practical cannot exceed 100.");
                return false;
            }
            return true;
        }

        document.addEventListener("DOMContentLoaded", function() {
            let form = document.querySelector("form.new-added-form");
            if (form) {
                form.onsubmit = function() {
                    return validateTotalMarks();
                }
            }
            const isRel = document.getElementById('isReligious');
            const wrapSix = document.getElementById('defaultRelSixWrap');
            const wrapAll = document.getElementById('defaultRelAllWrap');
            const allToggle = document.getElementById('defaultReligiousForAllClass');
            if (isRel) {
                const toggle = () => {
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
                    boxes.forEach(b => { b.checked = allToggle.checked; });
                });
            }
        });

        function mcqMarks(checkbox) {
            if (checkbox.checked) {
                document.getElementById("mcqFiled").innerHTML = "<label for='mcqMarksValue' style='display:block; font-weight:600; margin-bottom:0.35rem;'>MCQ Marks</label><input type='text' name='mcqValue' style='width:100%; border:1px solid var(--am-border); border-radius:10px; padding:0.55rem 0.7rem; background:var(--am-surface); color:var(--am-text);' placeholder='Enter the mcq total marks'>";
            } else {
                document.getElementById("mcqFiled").innerHTML = "";
            }
        }

        function cqMarks(checkbox) {
            if (checkbox.checked) {
                document.getElementById("cqFiled").innerHTML = "<label for='cqMarksValue' style='display:block; font-weight:600; margin-bottom:0.35rem;'>CQ Marks</label><input type='text' name='cqValue' style='width:100%; border:1px solid var(--am-border); border-radius:10px; padding:0.55rem 0.7rem; background:var(--am-surface); color:var(--am-text);' placeholder='Enter the cq total marks'>";
            } else {
                document.getElementById("cqFiled").innerHTML = "";
            }
        }

        function practicalMarks(checkbox) {
            if (checkbox.checked) {
                document.getElementById("practicalFiled").innerHTML = "<label for='practicalMarksValue' style='display:block; font-weight:600; margin-bottom:0.35rem;'>Practical Marks</label><input type='text' name='practicalValue' style='width:100%; border:1px solid var(--am-border); border-radius:10px; padding:0.55rem 0.7rem; background:var(--am-surface); color:var(--am-text);' placeholder='Enter the practical total marks'>";
            } else {
                document.getElementById("practicalFiled").innerHTML = "";
            }
        }
    </script>
@endsection
