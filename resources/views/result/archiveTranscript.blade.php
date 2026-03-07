@extends('result.include')
@section('backTitle')
Archived Transcript
@endsection
@section('backIndex')
    <style>
        @page { size: A4 portrait; margin: 12mm; }
        html, body { background: #fff; }
        @media print {
            html, body { background: #fff !important; }
            #wrapper, .wrapper, .dashboard-page-one, .dashboard-content-one { background: #fff !important; }
            .d-print-none { display: none !important; }
            .breadcrumbs-area, .header-menu-one, .navbar { display: none !important; }
            .marksheet .card { box-shadow: none !important; border: none !important; }
            .marksheet .transcript { border: none !important; }
            .signature-row { display: grid !important; grid-template-columns: repeat(3, 1fr) !important; gap: 16px !important; width: 100% !important; }
            .marksheet table.table, .marksheet table.table-bordered { border-collapse: collapse !important; }
            .marksheet table.table thead th, .marksheet table.table-bordered thead th { background: #e5e7eb !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .marksheet table.table th, .marksheet table.table td, .marksheet table.table-bordered th, .marksheet table.table-bordered td { border: 1px solid #000 !important; }
            .result-header-band { background: #f3f4f6 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; border: 1px solid #000 !important; }
            .report-header { gap: 6px !important; margin-bottom: 6px !important; padding-bottom: 4px !important; border-bottom: 1px solid #cbd5e1 !important; }
            .report-header .hdr-logo { height: 48px !important; }
            .report-header .name { font-size: 18px !important; }
            .report-header .subline, .report-header .contacts { font-size: 11px !important; }
            .failed-subjects { font-size: 11px !important; }
            .failed-subjects h4 { margin-bottom: 4px !important; }
            .failed-subjects ul { -webkit-columns: 2 !important; columns: 2 !important; column-gap: 12px !important; list-style-position: inside !important; margin: 4px 0 !important; padding-left: 0 !important; }
            .failed-subjects li { break-inside: avoid !important; margin: 0 0 3px !important; padding: 0 !important; }
        }
        .marksheet .transcript {
            background: #fff;
            padding: 16px;
            border: 1px solid #e5e7eb;
        }
        .marksheet table.table, .marksheet table.table-bordered { font-size: 12px; border-collapse: collapse; }
        .marksheet table.table thead th, .marksheet table.table-bordered thead th { background: #f3f4f6; font-weight: 700; }
        .marksheet table.table th, .marksheet table.table td, .marksheet table.table-bordered th, .marksheet table.table-bordered td { padding: 6px; border: 1px solid #2d3748; }
        .marksheet h3 { margin-top: 8px; margin-bottom: 8px; }
        .signature-row { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-top: 20px; width: 100%; }
        .signature-box { display: flex; flex-direction: column; justify-content: flex-end; align-items: center; min-height: 90px; page-break-inside: avoid; }
        .signature-space { height: 60px; }
        .signature-line { width: 80%; border-bottom: 1px solid #2d3748; }
        .signature-role { font-weight: 600; margin-bottom: 6px; }
        .signature-label { margin-top: 6px; font-size: 11px; color: #4a5568; }
        .student-info th { width: 140px; white-space: nowrap; }
        .student-info td { word-break: break-word; overflow-wrap: anywhere; }
        .failed-subjects h4 { margin-bottom: 6px; }
        .failed-subjects ul { margin: 6px 0; }
    </style>
    @php
        $studentDetails = $archive->student;
        $adminId        = $studentDetails->stdId ?? ($studentDetails->id ?? null);
        $stdName        = ($studentDetails->fullName ?? '').' '.($studentDetails->sureName ?? '');
        $rollNumber     = $archive->old_roll ?? ($studentDetails->rollNumber ?? '');
        $fName          = $studentDetails->fatherName ?? ($studentDetails->father ?? '');
        $mName          = $studentDetails->motherName ?? ($studentDetails->mother ?? '');
        $archivedAt     = $archive->created_at ? $archive->created_at->format('d M Y') : '';
        $examName       = $archive->exam->examName ?? '';
        $meritRank      = $transcriptData['merit'] ?? null;
    @endphp
    @include('components.institute-header')
    <div class="container-fluid d-print-none mb-2 text-end">
        <button type="button" class="btn btn-warning btn-sm" onclick="window.print()">
            <i class="fas fa-print"></i> Print
        </button>
    </div>
    <div class="row gutters-20 mb-4 marksheet">
        <div class="card height-auto col-12 mx-auto">
            <div class="card-body row transcript">
                <div class="col-12 mb-3">
                    <div class="text-center">
                        <h3 class="mb-0 text-uppercase fw-bold">Academic Transcript (Archive)</h3>
                        <p class="fw-bold mb-1">{{ $examName }}</p>
                    </div>
                </div>
                <table class="col-8 col-md-8 mb-4">
                    <tbody>
                        <tr>
                            <th>Student ID</th>
                            <td>:</td>
                            <td colspan="4">{{ !empty($adminId) ? $adminId : '-' }}</td>
                        </tr>
                        <tr>
                            <th>Name</th>
                            <td>:</td>
                            <td colspan="4">{{ $stdName }}</td>
                        </tr>
                        <tr>
                            <th>Father Name</th>
                            <td>:</td>
                            <td colspan="4">{{ $fName }}</td>
                        </tr>
                        <tr>
                            <th>Mother Name</th>
                            <td>:</td>
                            <td colspan="4">{{ $mName }}</td>
                        </tr>
                        <tr>
                            <th>Roll Number</th>
                            <td>:</td>
                            <td>{{ is_numeric($rollNumber) ? str_pad((string)((int)$rollNumber), 2, '0', STR_PAD_LEFT) : $rollNumber }}</td>
                            <th>Session</th>
                            <td>:</td>
                            <td>{{ $sessionName }}</td>
                        </tr>
                        <tr>
                            <th>Class</th>
                            <td>:</td>
                            <td>{{ $className }}</td>
                            <th>Section</th>
                            <td>:</td>
                            <td>{{ $sectionName }}</td>
                        </tr>
                        @if(!empty($transcriptData['religious_subject_name']))
                        <tr>
                            <th>Religious Subject</th>
                            <td>:</td>
                            <td colspan="4">{{ $transcriptData['religious_subject_name'] }}</td>
                        </tr>
                        @endif
                        <tr>
                            <th>Merit Position</th>
                            <td>:</td>
                            <td colspan="4">{{ isset($meritRank) && is_numeric($meritRank) ? str_pad((string)((int)$meritRank), 2, '0', STR_PAD_LEFT) : '-' }}</td>
                        </tr>
                        <tr>
                            <th>Archived At</th>
                            <td>:</td>
                            <td colspan="4">{{ $archivedAt }}</td>
                        </tr>
                    </tbody>
                </table>
                <table class="col-4 col-md-4 mb-4 table-bordered text-center">
                    <thead>
                        <th>Range of Marks</th>
                        <th>Grade</th>
                        <th>Point</th>
                    </thead>
                    <tbody>
                        @foreach($gradeList as $gl)
                            <tr>
                                <td>{{ $gl->minMark }} - {{ $gl->maxMark }}</td>
                                <td>{{ $gl->gradeName }}</td>
                                <td>{{ $gl->gradePoint }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                @include('result.transcriptArchiveTable', [
                    'transcriptData' => $transcriptData,
                    'studentDetails' => $studentDetails,
                    'gradeList' => $gradeList,
                ])
                <div class="signature-row">
                    <div class="signature-box">
                        <div class="signature-role">Guardian</div>
                        <div class="signature-space"></div>
                        <div class="signature-line"></div>
                        <div class="signature-label">Signature</div>
                    </div>
                    <div class="signature-box">
                        <div class="signature-role">Class Teacher</div>
                        <div class="signature-space"></div>
                        <div class="signature-line"></div>
                        <div class="signature-label">Signature</div>
                    </div>
                    <div class="signature-box">
                        <div class="signature-role">Principal/Head Master</div>
                        <div class="signature-space"></div>
                        <div class="signature-line"></div>
                        <div class="signature-label">Signature</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection