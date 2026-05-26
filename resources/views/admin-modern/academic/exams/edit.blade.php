@extends('admin-modern.layouts.app')

@section('title', 'Edit Exam')

@section('content')
    <x-admin-modern.page-header
        title="Edit Exam"
        subtitle="Modern parallel wrapper for exam update using existing ERP submit flow"
        :breadcrumb="['Home', 'Academic', 'Exam List', 'Edit Exam']"
    />

    <x-admin-modern.table-shell title="Update Exam">
        <div class="am-btn-row" style="margin-bottom: 0.7rem;">
            <a href="{{ route('adminModernAcademicExamsIndex') }}" class="am-btn-outline">Exam List</a>
        </div>

        @if(isset($item))
            <form class="new-added-form" action="{{ route('updateExam') }}" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="itemId" value="{{ $item->id }}">
                @csrf

                <div class="am-grid am-grid-2" style="margin-bottom: 0.7rem;">
                    <div>
                        <label for="examName" style="display:block; font-weight:600; margin-bottom:0.35rem;">Exam Name *</label>
                        <input id="examName" type="text" name="examName" value="{{ $item->examName }}" placeholder="Enter exam name" style="width:100%; border:1px solid var(--am-border); border-radius:10px; padding:0.55rem 0.7rem; background:var(--am-surface); color:var(--am-text);" required>
                    </div>

                    <div>
                        <label for="baseMark" style="display:block; font-weight:600; margin-bottom:0.35rem;">Exam Base Mark *</label>
                        <input id="baseMark" type="text" name="baseMark" value="{{ $item->baseMark }}" placeholder="Enter the value of base mark of the exam" style="width:100%; border:1px solid var(--am-border); border-radius:10px; padding:0.55rem 0.7rem; background:var(--am-surface); color:var(--am-text);" required>
                    </div>
                </div>

                <div class="am-grid am-grid-2" style="margin-bottom: 0.7rem;">
                    <div>
                        <label for="examDate" style="display:block; font-weight:600; margin-bottom:0.35rem;">Exam Startdate *</label>
                        <input id="examDate" type="date" name="examDate" value="{{ $item->examDate }}" placeholder="Enter exam start date" style="width:100%; border:1px solid var(--am-border); border-radius:10px; padding:0.55rem 0.7rem; background:var(--am-surface); color:var(--am-text);" required>
                    </div>

                    <div>
                        <label for="closeDate" style="display:block; font-weight:600; margin-bottom:0.35rem;">Exam Enddate *</label>
                        <input id="closeDate" type="date" name="closeDate" value="{{ $item->closeDate }}" placeholder="Enter exam close date" style="width:100%; border:1px solid var(--am-border); border-radius:10px; padding:0.55rem 0.7rem; background:var(--am-surface); color:var(--am-text);" required>
                    </div>
                </div>

                <div class="am-grid am-grid-2" style="margin-bottom: 0.7rem;">
                    <div>
                        <label for="passingSystem" style="display:block; font-weight:600; margin-bottom:0.35rem;">Passing System *</label>
                        <select id="passingSystem" name="passingSystem" style="width:100%; border:1px solid var(--am-border); border-radius:10px; padding:0.55rem 0.7rem; background:var(--am-surface); color:var(--am-text);">
                            <option value="1" {{ $item->passingSystem == 1 ? 'selected' : '' }}>Feature Wise</option>
                            <option value="2" {{ $item->passingSystem == 2 ? 'selected' : '' }}>Total Marks</option>
                        </select>
                    </div>

                    <div>
                        <label for="examClass" style="display:block; font-weight:600; margin-bottom:0.35rem;">Exam for Class *</label>
                        <select id="examClass" name="examClass" style="width:100%; border:1px solid var(--am-border); border-radius:10px; padding:0.55rem 0.7rem; background:var(--am-surface); color:var(--am-text);" required>
                            <option value="0">All Class</option>
                            @php
                                $itemData = \App\Models\Classes::orderBy('id', 'DESC')->get();
                            @endphp
                            @if(!empty($itemData))
                                @foreach($itemData as $item)
                                    <option value="{{ $item->id }}">{{ $item->className }}</option>
                                @endforeach
                            @endif
                        </select>
                    </div>
                </div>

                <div class="am-btn-row">
                    <button type="submit" class="am-btn-primary" style="border:0; cursor:pointer;">Save</button>
                    <button type="reset" class="am-btn-outline" style="cursor:pointer;">Reset</button>
                </div>
            </form>
        @else
            <div class="am-flash is-info" role="status">
                <span>Opps! Sorry, No data found for update</span>
            </div>
        @endif
    </x-admin-modern.table-shell>
@endsection
