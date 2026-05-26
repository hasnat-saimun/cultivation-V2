@extends('admin-modern.layouts.app')

@section('title', 'Create Exam')

@section('content')
    <x-admin-modern.page-header
        title="Create Exam"
        subtitle="Modern parallel wrapper for exam creation using existing ERP submit flow"
        :breadcrumb="['Home', 'Academic', 'Exam List', 'Create Exam']"
    />

    <x-admin-modern.table-shell title="Add New Exam">
        <div class="am-btn-row" style="margin-bottom: 0.7rem;">
            <a href="{{ route('adminModernAcademicExamsIndex') }}" class="am-btn-outline">Exam List</a>
        </div>

        <form class="new-added-form" action="{{ route('confirmExam') }}" method="POST" enctype="multipart/form-data">
            @csrf

            <div class="am-grid am-grid-2" style="margin-bottom: 0.7rem;">
                <div>
                    <label for="examName" style="display:block; font-weight:600; margin-bottom:0.35rem;">Exam Name *</label>
                    <input id="examName" type="text" name="examName" placeholder="Enter exam name" style="width:100%; border:1px solid var(--am-border); border-radius:10px; padding:0.55rem 0.7rem; background:var(--am-surface); color:var(--am-text);" required>
                </div>

                <div>
                    <label for="baseMark" style="display:block; font-weight:600; margin-bottom:0.35rem;">Exam Base Mark *</label>
                    <input id="baseMark" type="text" name="baseMark" placeholder="Enter the value of base mark of the exam" style="width:100%; border:1px solid var(--am-border); border-radius:10px; padding:0.55rem 0.7rem; background:var(--am-surface); color:var(--am-text);" required>
                </div>
            </div>

            <div class="am-grid am-grid-2" style="margin-bottom: 0.7rem;">
                <div>
                    <label for="examDate" style="display:block; font-weight:600; margin-bottom:0.35rem;">Exam Startdate *</label>
                    <input id="examDate" type="date" name="examDate" placeholder="Enter exam start date" style="width:100%; border:1px solid var(--am-border); border-radius:10px; padding:0.55rem 0.7rem; background:var(--am-surface); color:var(--am-text);" required>
                </div>

                <div>
                    <label for="closeDate" style="display:block; font-weight:600; margin-bottom:0.35rem;">Exam Enddate *</label>
                    <input id="closeDate" type="date" name="closeDate" placeholder="Enter exam close date" style="width:100%; border:1px solid var(--am-border); border-radius:10px; padding:0.55rem 0.7rem; background:var(--am-surface); color:var(--am-text);" required>
                </div>
            </div>

            <div class="am-grid am-grid-2" style="margin-bottom: 0.7rem;">
                <div>
                    <label for="examClass" style="display:block; font-weight:600; margin-bottom:0.35rem;">Exam for Class *</label>
                    <select id="examClass" name="examClass" style="width:100%; border:1px solid var(--am-border); border-radius:10px; padding:0.55rem 0.7rem; background:var(--am-surface); color:var(--am-text);" required>
                        <option value="0">All Class</option>
                        @php
                            $itemData = \App\Models\classManage::orderBy('id', 'DESC')->get();
                        @endphp
                        @if(!empty($itemData))
                            @foreach($itemData as $item)
                                <option value="{{ $item->id }}">{{ $item->className }}</option>
                            @endforeach
                        @endif
                    </select>
                </div>

                <div>
                    <label for="passingSystem" style="display:block; font-weight:600; margin-bottom:0.35rem;">Passing System *</label>
                    <select id="passingSystem" name="passingSystem" style="width:100%; border:1px solid var(--am-border); border-radius:10px; padding:0.55rem 0.7rem; background:var(--am-surface); color:var(--am-text);">
                        <option value="1">Feature Wise</option>
                        <option value="2" selected>Total Marks</option>
                    </select>
                </div>
            </div>

            <div class="am-btn-row">
                <button type="submit" class="am-btn-primary" style="border:0; cursor:pointer;">Save</button>
                <button type="reset" class="am-btn-outline" style="cursor:pointer;">Reset</button>
            </div>
        </form>
    </x-admin-modern.table-shell>
@endsection
