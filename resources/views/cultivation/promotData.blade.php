@extends('cultivation.include')
@section('backTitle')
Get Promotional Student Data
@endsection
@php
    $classData = \App\Models\classManage::find($classId);
    $sectionData = \App\Models\sectionManage::find($groupId);
    $sessionData = \App\Models\sessionManage::find($sessionId);
    if($classData):
        $className = $classData->className;
    else:
        $className = "-";
    endif;
    if($sectionData):
        $sectionName = $sectionData->section;
    else:
        $sectionName = "-";
    endif;
    if($sessionData):
        $session_name = $sessionData->session;
    else:
        $session_name = "-";
    endif;
@endphp
@section('backIndex')
    @if($studentList->count()>0)
        <form method="POST" class="card-body form form-group" action="{{ route('confirmPromotData') }}">
                @if($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                <div class="row">
                    <div class="col-12"><h1>Manage the promotion of student from the list</h1></div>
                    @if(config('result_engine.promotion_enabled'))
                    <div class="col-12 form-group">
                        <label>Controlling Exam *</label>
                        <select class="select2" name="examId" required>
                            <option value="">Select *</option>
                            @foreach(($promotionExamList ?? collect()) as $exam)
                                <option value="{{ $exam->id }}" {{ (string)old('examId') === (string)$exam->id ? 'selected' : '' }}>{{ $exam->examName }}</option>
                            @endforeach
                        </select>
                        <small class="form-text text-muted">Only this published exam controls eligibility and the centralized archive.</small>
                    </div>
                    @endif
                    <div class="col-6 col-md-4 mb-2"><b>Group/Section:</b>  {{ isset($type) && $type==='classwise' ? 'All Sections' : $sectionName }}</div>
                    <div class="col-6 col-md-4 mb-2"><b>Current Class:</b>  {{ $className }}</div>
                    <div class="col-6 col-md-4 mb-2"><b>Session:</b> {{ $session_name }}</div>
                    <div class="col-12 form-group">
                        <label>Promoted Session *</label>
                        <select class="select2" name="promotSession" required>
                            <option value="">Select *</option>
                            @php
                                $sessions = \App\Models\sessionManage::orderBy('id','DESC')->get();
                            @endphp
                            @if(!empty($sessions))
                                @foreach($sessions as $sess)
                                <option value="{{ $sess->id }}">{{ $sess->session }}</option>
                                @endforeach
                            @endif
                        </select>
                    </div>
                    <div class="col-12 form-group">
                        <label>Promoted Class *</label>
                        <select class="select2" name="promotId" required>
                            <option value="">Select *</option>
                            @php
                                $classes = \App\Models\classManage::orderBy('id','DESC')->get();
                            @endphp
                            @if(!empty($classes))
                                @foreach($classes as $cls)
                                <option value="{{ $cls->id }}">{{ $cls->className }}</option>
                                @endforeach
                            @endif
                        </select>
                    </div>
                    <div class="col-12 form-group">
                        <label>Promoted Section *</label>
                        <select class="select2" name="promotSection" required>
                            <option value="">Select *</option>
                            @php
                                $sections = \App\Models\sectionManage::orderBy('id','DESC')->get();
                            @endphp
                            @if(!empty($sections))
                                @foreach($sections as $sec)
                                <option value="{{ $sec->id }}">{{ $sec->section }}</option>
                                @endforeach
                            @endif
                        </select>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-12">Promot All <input type="checkbox" name="select-all" id="select-all" /></div>
                </div>
                @csrf
                <input type="hidden" name="submit_token" value="{{ $submitToken ?? '' }}">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Eligible</th>
                            <th>Student ID</th>
                            <th> Roll</th>
                            <th>New Roll</th>
                            <th>Student Name</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if($studentList->count()>0)
                        <input type="hidden" name="sessionId" value="{{ $sessionId }}">
                        <input type="hidden" name="classId" value="{{ $classId }}">
                        <input type="hidden" name="groupId" value="{{ $groupId }}">
                        <input type="hidden" name="type" value="{{ $type ?? 'sectionwise' }}">
                        @foreach($studentList as $std)
                        <tr>
                            <td>
                                <input type="checkbox" class="student-checkbox" name="selected_students[]" id="checkbox-{{ $std->id }}" value="{{ $std->id }}" /> <b class="eligible">Yes</b>
                            </td>
                            <td>{{ $std->stdId }}</td>
                            <td>{{ $std->rollNumber }}</td>
                            <td width="9%">
                                <input type="text" class="form-control" name="roll_numbers[{{ $std->id }}]"/>
                            </td>
                            <td>
                                {{ $std->fullName.' '.$std->sureName }}
                                <div class="mt-2">
                                    @if(config('result_engine.promotion_revert_enabled') && ($activeAudit = ($activePromotionAudits ?? collect())->get($std->id)))
                                    <div class="small text-muted mb-1">
                                        Exam {{ $activeAudit->exam_id }};
                                        {{ $activeAudit->old_session }}/{{ $activeAudit->old_class }}/{{ $activeAudit->old_section ?? '-' }}
                                        → {{ $activeAudit->new_session }}/{{ $activeAudit->new_class }}/{{ $activeAudit->new_section ?? '-' }};
                                        cycle students {{ ($promotionCycleCounts ?? collect())->get($activeAudit->promotion_cycle_id, 1) }}
                                    </div>
                                    <form method="POST" action="{{ route('promotion.revert.centralized', ['promotionCycleId'=>$activeAudit->promotion_cycle_id]) }}" style="display:inline" onsubmit="return confirm('Revert this exact centralized promotion cycle for this student?');">
                                        @csrf
                                        <input type="hidden" name="student_id" value="{{ $std->id }}">
                                        <input type="hidden" name="confirm_cycle" value="{{ $activeAudit->promotion_cycle_id }}">
                                        <button type="submit" class="btn btn-sm btn-warning">Centralized Revert</button>
                                    </form>
                                    @elseif(!config('result_engine.promotion_revert_enabled') && ($lastArchive = \App\Models\ResultArchive::where('student_id',$std->id)->orderBy('created_at','desc')->first()))
                                    <form method="POST" action="{{ route('promotion.revert', ['stdId'=>$std->id]) }}" style="display:inline" onsubmit="return confirm('Revert this student to previous class/section?');">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-warning">Revert</button>
                                    </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @endforeach
                        <div class="mb-4"><input type="submit" value="Save" class="btn btn-success js-confirm-promotion-btn"> <a href="{{ route('studentPromotion') }}" class="btn btn-primary"><i class="fa-solid fa-arrow-left"></i> Back</a></div>
                        @else
                        <tr>
                            <td colspan="5">Sorry! No data found</td>
                        </tr>
                        @endif
                    </tbody>
                </table>
                <div class="mb-4"><input type="submit" value="Save" class="btn btn-success js-confirm-promotion-btn"> <a href="{{ route('studentPromotion') }}" class="btn btn-primary"><i class="fa-solid fa-arrow-left"></i> Back</a></div>
            </form>
    @else
    <div class="alert alert-info">
        Sorry! No data found
    </div>
    <div class="mb-4"> <a href="{{ route('studentPromotion') }}" class="btn btn-primary"><i class="fa-solid fa-arrow-left"></i> Back</a></div>
    @endif
    <script>
        // Toggle only student checkboxes, not every checkbox on the page.
        $('#select-all').on('change', function() {
            $('.student-checkbox:visible').prop('checked', this.checked);
        });

        // At least one selected student is required before submit.
        var isPromotionSubmitting = false;
        $('form[action="{{ route('confirmPromotData') }}"]').on('submit', function(e) {
            if (isPromotionSubmitting) {
                e.preventDefault();
                return;
            }

            if ($('.student-checkbox:checked').length < 1) {
                e.preventDefault();
                alert('Please select at least one student to promote.');
                return;
            }

            isPromotionSubmitting = true;
            var $btns = $(this).find('.js-confirm-promotion-btn');
            $btns.prop('disabled', true).val('Processing...');
        });
    </script>
@endsection
