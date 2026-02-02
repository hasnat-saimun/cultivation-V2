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
                <div class="row">
                    <div class="col-12"><h1>Manage the promotion of student from the list</h1></div>
                    <div class="col-6 col-md-4 mb-2"><b>Group/Section:</b>  {{ isset($type) && $type==='classwise' ? 'All Sections' : $sectionName }}</div>
                    <div class="col-6 col-md-4 mb-2"><b>Current Class:</b>  {{ $className }}</div>
                    <div class="col-6 col-md-4 mb-2"><b>Session:</b> {{ $session_name }}</div>
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
                        <label>Promoted Section</label>
                        <select class="select2" name="promotSection">
                            <option value="">Keep current / Select new</option>
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
                        <input type="hidden" name="studentId[]" value="{{ $std->stdId }}">
                        <tr>
                            <td>
                                <input type="checkbox" name="checkbox[]" id="checkbox-{{ $std->id }}" value="{{ $std->stdId }}" /> <b class="eligible">Yes</b>
                            </td>
                            <td>{{ $std->stdId }}</td>
                            <td>{{ $std->rollNumber }}</td>
                            <td width="9%">
                                <input type="rollNum" class="form-control" id="rollNum" name="rollNum[]"/>
                            </td>
                            <td>
                                {{ $std->fullName.' '.$std->sureName }}
                                <div class="mt-2">
                                    @php $lastArchive = \App\Models\ResultArchive::where('student_id',$std->id)->orderBy('created_at','desc')->first(); @endphp
                                    @if($lastArchive)
                                    <form method="POST" action="{{ route('promotion.revert', ['stdId'=>$std->id]) }}" style="display:inline" onsubmit="return confirm('Revert this student to previous class/section?');">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-warning">Revert</button>
                                    </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @endforeach
                        <div class="mb-4"><input type="submit" value="Save" class="btn btn-success"> <a href="{{ route('studentPromotion') }}" class="btn btn-primary"><i class="fa-solid fa-arrow-left"></i> Back</a></div>
                        @else
                        <tr>
                            <td colspan="5">Sorry! No data found</td>
                        </tr>
                        @endif
                    </tbody>
                </table>
                <div class="mb-4"><input type="submit" value="Save" class="btn btn-success"> <a href="{{ route('studentPromotion') }}" class="btn btn-primary"><i class="fa-solid fa-arrow-left"></i> Back</a></div>
            </form>
    @else
    <div class="alert alert-info">
        Sorry! No data found
    </div>
    <div class="mb-4"> <a href="{{ route('studentPromotion') }}" class="btn btn-primary"><i class="fa-solid fa-arrow-left"></i> Back</a></div>
    @endif
    <script>
        // Listen for click on toggle checkbox
        $('#select-all').click(function(event) {   
            if(this.checked) {
                // Iterate each checkbox
                $(':checkbox').each(function() {
                    this.checked = true;                      
                });
            } else {
                $(':checkbox').each(function() {
                    this.checked = false;                       
                });
            }
        }); 
    </script>
@endsection