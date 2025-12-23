@extends('result.singleinclude')
@section('backTitle')
All Marksheet
@endsection
@section('backIndex')
<style>
    @page { size: A4; margin: 12mm; }
    html, body { background: #fff; }
    @media print {
        html, body { background: #fff !important; }
        .main-website, .main-content, .container-fluid { background: #fff !important; }
        .table { border-collapse: collapse !important; }
        .table thead th { background: #e5e7eb !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .table th, .table td { border: 1px solid #000 !important; padding: 6px !important; }
    }
</style>
<div class="main-website">
    <div class="main-content">
        @include('components.institute-header')
        <div class="container-fluid mb-4">
            <form method="GET" action="{{ route('allMarksheet') }}" class="row g-2 align-items-end">
                <div class="col-md-2">
                    <label class="form-label">Exam *</label>
                    <select name="examId" class="form-control" required>
                        <option value="">Select</option>
                        @php $examList = \App\Models\Exam::orderBy('id','DESC')->get(); @endphp
                        @foreach($examList as $ex)
                            <option value="{{ $ex->id }}" {{ $ex->id == request('examId') ? 'selected' : '' }}>{{ $ex->examName }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Class *</label>
                    <select name="classId" class="form-control" required>
                        <option value="">Select</option>
                        @php $classList = \App\Models\classManage::orderBy('id','ASC')->get(); @endphp
                        @foreach($classList as $cl)
                            <option value="{{ $cl->id }}" {{ $cl->id == request('classId') ? 'selected' : '' }}>{{ $cl->className ?? ('Class-'.$cl->id) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Session</label>
                    <select name="sessionId" class="form-control">
                        <option value="">All</option>
                        @php $sessionList = \App\Models\sessionManage::orderBy('id','DESC')->get(); @endphp
                        @foreach($sessionList as $s)
                            <option value="{{ $s->id }}" {{ $s->id == request('sessionId') ? 'selected' : '' }}>{{ $s->session ?? ('Session-'.$s->id) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Section/Group</label>
                    <select name="sectionId" class="form-control">
                        <option value="">All</option>
                        @php $sectionList = \App\Models\sectionManage::orderBy('id','ASC')->get(); @endphp
                        @foreach($sectionList as $sec)
                            <option value="{{ $sec->id }}" {{ $sec->id == request('sectionId') ? 'selected' : '' }}>{{ $sec->section ?? ('Section-'.$sec->id) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <button class="btn btn-success w-100">Load Results</button>
                </div>
                <div class="col-md-2">
                    @if($studentsLoaded)
                        <a href="{{ route('allMarksheet') }}" class="btn btn-warning w-100">Reset</a>
                    @endif
                </div>
                <div class="col-md-4 d-flex align-items-center">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="compact" value="1" id="compactChk" {{ request('compact') ? 'checked' : '' }}>
                        <label class="form-check-label" for="compactChk">
                            Compact per-student subjects (hide empty subjects)
                        </label>
                    </div>
                </div>
            </form>
        </div>

        @if(!$examId || !$classId)
            <div class="alert alert-info container">Please select required filters (Exam & Class) to view results.</div>
        @endif

        @if($examId && $classId)
            <div class="result-section">
                <div class="row">
                    <div class="col-12">
                        <div class="result-text text-center mt-2">
                            <h4 class="fw-bold">Class Result Sheet @if($exam) - {{ $exam->examName }} @endif</h4>
                            @if($exam && $exam->passingSystem == 1)
                                <p class="text-muted mb-0"><small>Feature-wise Passing System Applied</small></p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            @php $subjectCount = count($subjects); @endphp
            @if($studentsLoaded && (count($passResults) + count($failResults)) === 0)
                <div class="alert alert-warning container">No marks found for the selected filters.</div>
            @endif

            @if(!$compactMode && count($passResults) > 0)
                <h5 class="mt-4 fw-bold text-success">Passed Students ({{ count($passResults) }})</h5>
                <div class="table-responsive dark-border mb-5">
                    <table class="w-100 table-striped table-bordered text-center table">
                        <tr class="table-dark text-dark">
                            <th rowspan="3"><b>Roll</b></th>
                            <th rowspan="3"><b>Student Id</b></th>
                            <th rowspan="3"><b>Name</b></th>
                            <th colspan="{{ max($subjectCount*6, 1) }}"><b>Subject</b></th>
                            <th rowspan="3"><b>Total</b></th>
                            <th rowspan="3"><b>Grade</b></th>
                            <th rowspan="3"><b>GPA</b></th>
                        </tr>
                        <tr class="table-dark text-dark">
                            @if(count($subjects) > 0)
                                @foreach($subjects as $sub)
                                    <th colspan="6"><b>{{ $sub->subjectName }}</b></th>
                                @endforeach
                            @else
                                <th><b>No subjects</b></th>
                            @endif
                        </tr>
                        <tr class="table-dark text-dark">
                            @if(count($subjects) > 0)
                                @foreach($subjects as $sub)
                                    <th><b>CQ</b></th>
                                    <th><b>MCQ</b></th>
                                    <th><b>P</b></th>
                                    <th><b>TOTAL</b></th>
                                    <th><b>GRADE</b></th>
                                    <th><b>POINT</b></th>
                                @endforeach
                            @else
                                <th><b>-</b></th>
                            @endif
                        </tr>
                        @foreach($passResults as $i=>$res)
                            <tr>
                                <td>{{ $res['student']->rollNumber }}</td>
                                <td>{{ $res['student']->stdId }}</td>
                                <td>{{ $res['student']->fullName }} {{ $res['student']->sureName }}</td>
                                @foreach($res['subjects'] as $sres)
                                    <td>{{ $sres['cq'] }}</td>
                                    <td>{{ $sres['mcq'] }}</td>
                                    <td>{{ $sres['practical'] }}</td>
                                    <td>{{ $sres['total'] }}</td>
                                    <td>{{ $sres['grade'] }}</td>
                                    <td>{{ $sres['gradePoint'] }}</td>
                                @endforeach
                                <td>{{ $res['totalMarks'] }}</td>
                                <td>{{ $res['finalLetter'] }}</td>
                                <td>{{ $res['finalGpa'] }}</td>
                            </tr>
                        @endforeach
                    </table>
                </div>
            @endif

            @if(!$compactMode && count($failResults) > 0)
                <h5 class="mt-4 fw-bold text-danger">Failed Students ({{ count($failResults) }})</h5>
                <div class="table-responsive dark-border mb-5">
                    <table class="w-100 table-striped table-bordered text-center table">
                        <tr class="table-dark text-dark">
                            <th rowspan="3"><b>Roll</b></th>
                            <th rowspan="3"><b>Student Id</b></th>
                            <th rowspan="3"><b>Name</b></th>
                            <th colspan="{{ max($subjectCount*6, 1) }}"><b>Subject</b></th>
                            <th rowspan="3"><b>Total</b></th>
                            <th rowspan="3"><b>Grade</b></th>
                            <th rowspan="3"><b>GPA</b></th>
                        </tr>
                        <tr class="table-dark text-dark">
                            @if(count($subjects) > 0)
                                @foreach($subjects as $sub)
                                    <th colspan="6"><b>{{ $sub->subjectName }}</b></th>
                                @endforeach
                            @else
                                <th><b>No subjects</b></th>
                            @endif
                        </tr>
                        <tr class="table-dark text-dark">
                            @if(count($subjects) > 0)
                                @foreach($subjects as $sub)
                                    <th><b>CQ</b></th>
                                    <th><b>MCQ</b></th>
                                    <th><b>P</b></th>
                                    <th><b>TOTAL</b></th>
                                    <th><b>GRADE</b></th>
                                    <th><b>POINT</b></th>
                                @endforeach
                            @else
                                <th><b>-</b></th>
                            @endif
                        </tr>
                        @foreach($failResults as $i=>$res)
                            <tr class="table-danger">
                                <td>{{ $res['student']->rollNumber }}</td>
                                <td>{{ $res['student']->stdId }}</td>
                                <td>{{ $res['student']->fullName }} {{ $res['student']->sureName }}</td>
                                @foreach($res['subjects'] as $sres)
                                    <td>{{ $sres['cq'] }}</td>
                                    <td>{{ $sres['mcq'] }}</td>
                                    <td>{{ $sres['practical'] }}</td>
                                    <td>{{ $sres['total'] }}</td>
                                    <td>{{ $sres['grade'] }}</td>
                                    <td>{{ $sres['gradePoint'] }}</td>
                                @endforeach
                                <td>{{ $res['totalMarks'] }}</td>
                                <td>{{ $res['finalLetter'] }}</td>
                                <td>{{ $res['finalGpa'] }}</td>
                            </tr>
                        @endforeach
                    </table>
                </div>
            @endif

            @if(!$compactMode && count($incompleteResults) > 0)
                <h5 class="mt-4 fw-bold text-secondary">Incomplete Students ({{ count($incompleteResults) }})</h5>
                <div class="table-responsive dark-border mb-5">
                    <table class="w-100 table-striped table-bordered text-center table">
                        <tr class="table-dark text-dark">
                            <th rowspan="3"><b>Roll</b></th>
                            <th rowspan="3"><b>Student Id</b></th>
                            <th rowspan="3"><b>Name</b></th>
                            <th colspan="{{ max($subjectCount*6, 1) }}"><b>Subject</b></th>
                            <th rowspan="3"><b>Total</b></th>
                            <th rowspan="3"><b>Grade</b></th>
                            <th rowspan="3"><b>GPA</b></th>
                        </tr>
                        <tr class="table-dark text-dark">
                            @if(count($subjects) > 0)
                                @foreach($subjects as $sub)
                                    <th colspan="6"><b>{{ $sub->subjectName }}</b></th>
                                @endforeach
                            @else
                                <th><b>No subjects</b></th>
                            @endif
                        </tr>
                        <tr class="table-dark text-dark">
                            @if(count($subjects) > 0)
                                @foreach($subjects as $sub)
                                    <th><b>CQ</b></th>
                                    <th><b>MCQ</b></th>
                                    <th><b>P</b></th>
                                    <th><b>TOTAL</b></th>
                                    <th><b>GRADE</b></th>
                                    <th><b>POINT</b></th>
                                @endforeach
                            @else
                                <th><b>-</b></th>
                            @endif
                        </tr>
                        @foreach($incompleteResults as $i=>$res)
                            <tr class="table-secondary">
                                <td>{{ $res['student']->rollNumber }}</td>
                                <td>{{ $res['student']->stdId }}</td>
                                <td>{{ $res['student']->fullName }} {{ $res['student']->sureName }}</td>
                                @foreach($res['subjects'] as $sres)
                                    <td>{{ $sres['cq'] }}</td>
                                    <td>{{ $sres['mcq'] }}</td>
                                    <td>{{ $sres['practical'] }}</td>
                                    <td>{{ $sres['total'] }}</td>
                                    <td>{{ $sres['grade'] }}</td>
                                    <td>{{ $sres['gradePoint'] }}</td>
                                @endforeach
                                <td>{{ $res['totalMarks'] }}</td>
                                <td>{{ $res['finalLetter'] }}</td>
                                <td>{{ $res['finalGpa'] ?? '-' }}</td>
                            </tr>
                        @endforeach
                    </table>
                </div>
            @endif

            {{-- Compact Mode Rendering: variable subjects per student --}}
            @if($compactMode)
                @if(count($passResultsCompact) > 0)
                    <h5 class="mt-4 fw-bold text-success">Passed Students ({{ count($passResultsCompact) }})</h5>
                    <div class="table-responsive dark-border mb-5">
                        <table class="w-100 table-striped table-bordered text-center table">
                            <tr class="table-dark text-dark">
                                <th><b>Roll</b></th>
                                <th><b>Student Id</b></th>
                                <th><b>Name</b></th>
                                <th><b>Subjects (with marks)</b></th>
                                <th><b>Total</b></th>
                                <th><b>Grade</b></th>
                                <th><b>GPA</b></th>
                            </tr>
                            @foreach($passResultsCompact as $i=>$res)
                                <tr>
                                    <td>{{ $res['student']->rollNumber }}</td>
                                    <td>{{ $res['student']->stdId }}</td>
                                    <td>{{ $res['student']->fullName }} {{ $res['student']->sureName }}</td>
                                    <td class="text-start">
                                        @if(isset($res['subjectsCompact']) && count($res['subjectsCompact'])>0)
                                            <ul class="mb-0">
                                                @foreach($res['subjectsCompact'] as $s)
                                                    <li>
                                                        <b>{{ $s['name'] }}</b>:
                                                        CQ {{ $s['cq'] }}, MCQ {{ $s['mcq'] }}, P {{ $s['practical'] }}, TOTAL {{ $s['total'] }}, G {{ $s['grade'] }}, GP {{ $s['gradePoint'] }}
                                                    </li>
                                                @endforeach
                                            </ul>
                                        @else
                                            <span class="text-muted">No subject marks</span>
                                        @endif
                                    </td>
                                    <td>{{ $res['totalMarks'] }}</td>
                                    <td>{{ $res['finalLetter'] }}</td>
                                    <td>{{ $res['finalGpa'] }}</td>
                                </tr>
                            @endforeach
                        </table>
                    </div>
                @endif

                @if(count($failResultsCompact) > 0)
                    <h5 class="mt-4 fw-bold text-danger">Failed Students ({{ count($failResultsCompact) }})</h5>
                    <div class="table-responsive dark-border mb-5">
                        <table class="w-100 table-striped table-bordered text-center table">
                            <tr class="table-dark text-dark">
                                <th><b>Roll</b></th>
                                <th><b>Student Id</b></th>
                                <th><b>Name</b></th>
                                <th><b>Subjects (with marks)</b></th>
                                <th><b>Total</b></th>
                                <th><b>Grade</b></th>
                                <th><b>GPA</b></th>
                            </tr>
                            @foreach($failResultsCompact as $i=>$res)
                                <tr class="table-danger">
                                    <td>{{ $res['student']->rollNumber }}</td>
                                    <td>{{ $res['student']->stdId }}</td>
                                    <td>{{ $res['student']->fullName }} {{ $res['student']->sureName }}</td>
                                    <td class="text-start">
                                        @if(isset($res['subjectsCompact']) && count($res['subjectsCompact'])>0)
                                            <ul class="mb-0">
                                                @foreach($res['subjectsCompact'] as $s)
                                                    <li>
                                                        <b>{{ $s['name'] }}</b>:
                                                        CQ {{ $s['cq'] }}, MCQ {{ $s['mcq'] }}, P {{ $s['practical'] }}, TOTAL {{ $s['total'] }}, G {{ $s['grade'] }}, GP {{ $s['gradePoint'] }}
                                                    </li>
                                                @endforeach
                                            </ul>
                                        @else
                                            <span class="text-muted">No subject marks</span>
                                        @endif
                                    </td>
                                    <td>{{ $res['totalMarks'] }}</td>
                                    <td>{{ $res['finalLetter'] }}</td>
                                    <td>{{ $res['finalGpa'] }}</td>
                                </tr>
                            @endforeach
                        </table>
                    </div>
                @endif

                @if(count($incompleteResultsCompact) > 0)
                    <h5 class="mt-4 fw-bold text-secondary">Incomplete Students ({{ count($incompleteResultsCompact) }})</h5>
                    <div class="table-responsive dark-border mb-5">
                        <table class="w-100 table-striped table-bordered text-center table">
                            <tr class="table-dark text-dark">
                                <th><b>Roll</b></th>
                                <th><b>Student Id</b></th>
                                <th><b>Name</b></th>
                                <th><b>Subjects (with marks)</b></th>
                                <th><b>Total</b></th>
                                <th><b>Grade</b></th>
                                <th><b>GPA</b></th>
                            </tr>
                            @foreach($incompleteResultsCompact as $i=>$res)
                                <tr class="table-secondary">
                                    <td>{{ $res['student']->rollNumber }}</td>
                                    <td>{{ $res['student']->stdId }}</td>
                                    <td>{{ $res['student']->fullName }} {{ $res['student']->sureName }}</td>
                                    <td class="text-start">
                                        @if(isset($res['subjectsCompact']) && count($res['subjectsCompact'])>0)
                                            <ul class="mb-0">
                                                @foreach($res['subjectsCompact'] as $s)
                                                    <li>
                                                        <b>{{ $s['name'] }}</b>:
                                                        CQ {{ $s['cq'] }}, MCQ {{ $s['mcq'] }}, P {{ $s['practical'] }}, TOTAL {{ $s['total'] }}, G {{ $s['grade'] }}, GP {{ $s['gradePoint'] }}
                                                    </li>
                                                @endforeach
                                            </ul>
                                        @else
                                            <span class="text-muted">No subject marks</span>
                                        @endif
                                    </td>
                                    <td>{{ $res['totalMarks'] }}</td>
                                    <td>{{ $res['finalLetter'] }}</td>
                                    <td>{{ $res['finalGpa'] ?? '-' }}</td>
                                </tr>
                            @endforeach
                        </table>
                    </div>
                @endif
            @endif
        @endif

        
    </div>
</div>
@endsection