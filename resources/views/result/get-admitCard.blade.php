@extends('result.include')
@section('backTitle')
Seat Plans
@endsection
@php
    $classData = \App\Models\classManage::find($classId);
    $sectionData = \App\Models\sectionManage::find($groupId);
    $sessionData = \App\Models\sessionManage::find($sessionId);
    $examData = \App\Models\Exam::find($examId);
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
        $sessionName = $sessionData->session;
    else:
        $sessionName = "-";
    endif;
    if($examData):
        $examName = $examData->examName;
    else:
        $examName = "-";
    endif;

    $serverData = \App\Models\ServerConfig::latest('id')->first();
    $insName = $serverData->instituteName ?? 'Institute Name';
    $location = $serverData->address ?? '';
    $logo = $serverData->logo ?? null;
@endphp
@section('backIndex')
    <style>
        @page { size: A4 portrait; margin: 6mm; }
        .seat-plan-page {
            font-family: "Poppins", "Segoe UI", Tahoma, sans-serif;
            color: #111827;
            background: #fff;
        }
        .seat-plan-controls { margin-bottom: 12px; }
        .seat-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 10px;
            background: #fff;
            width: 100%;
        }
        .seat-card {
            border: 2px dashed #444;
            margin: 0;
            background: #fff;
            padding: 6px 10px 8px;
            min-height: 252px;
            break-inside: avoid;
            page-break-inside: avoid;
            overflow: hidden;
        }
        .seat-header { display: flex; align-items: center; gap: 8px; margin-bottom: 3px; }
        .seat-logo {
            width: 58px;
            height: 58px;
            object-fit: contain;
            border-radius: 50%;
            background: #fff;
            border: 1px solid #8290a3;
            flex: 0 0 58px;
        }
        .seat-head-lines { line-height: 1.1; }
        .seat-school {
            font-size: 25px;
            font-weight: 700;
            color: #111827;
            text-transform: uppercase;
            letter-spacing: 0;
            font-family: "Poppins", "Segoe UI", Tahoma, sans-serif;
        }
        .seat-location { font-size: 14px; color: #374151; font-weight: 500; }
        .seat-badge-wrap { text-align: center; margin: 5px 0 7px; }
        .seat-badge {
            display: inline-block;
            border: 2px solid #0f766e;
            color: #111827;
            border-radius: 8px;
            padding: 3px 11px 2px;
            font-size: 14px;
            font-weight: 600;
            background: transparent;
            line-height: 1.1;
        }
        .seat-body { display: grid; grid-template-columns: 1fr 108px; gap: 7px; align-items: start; }
        .seat-info { font-size: 14px; line-height: 1.22; color: #111827; font-weight: 500; }
        .seat-row { display: grid; line-height:2.5rem; grid-template-columns: 92px 10px 1fr; }
        .seat-row .k { font-weight: 500; }
        .seat-photo {
            width: 108px;
            height: 100px;
            border: 1px solid #8a95a6;
            object-fit: cover;
            background: #fff;
            display: block;
        }
        .roll-box {
            width: 108px;
            border-left: 1px solid #8a95a6;
            border-right: 1px solid #8a95a6;
            border-bottom: 1px solid #8a95a6;
            background: #fff;
            text-align: center;
        }
        .roll-label {
            border-top: 1px solid #8a95a6;
            border-bottom: 1px solid #8a95a6;
            font-size: 25px;
            font-weight: 700;
            color: #111827;
            line-height: 1;
            padding: 0;
            font-family: "Times New Roman", Georgia, serif;
        }
        .roll-value {
            font-size: 25px;
            font-weight: 700;
            color: #111827;
            padding: 1px 0 0;
            line-height: 1.05;
            font-family: "Times New Roman", Georgia, serif;
        }

        @media (max-width: 1200px) {
            .seat-school { font-size: 28px; }
        }
        @media (max-width: 900px) {
            .seat-grid { grid-template-columns: 1fr; }
        }
        @media print {
            html, body,
            #wrapper,
            .wrapper,
            .dashboard-page-one,
            .dashboard-content-one,
            .main-website,
            .main-content,
            .seat-plan-page {
                background: #fff !important;
                margin: 0 !important;
                padding: 0 !important;
                overflow: visible !important;
            }
            .card,
            .card-body,
            .card-header { border: 0 !important; box-shadow: none !important; background: #fff !important; }
            .breadcrumbs-area,
            .header-menu-one,
            .sidebar-main,
            .footer-wrap-layout1,
            .seat-page-meta,
            .d-print-none {
                display: none !important;
            }
            .d-print-none { display: none !important; }
            .seat-grid {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 8px;
                width: 100% !important;
            }
            .seat-card {
                min-height: 244px;
                background: #fff !important;
                margin: 0 !important;
            }
            .seat-school { font-size: 20px; }
            .seat-location { font-size: 12px; }
            .seat-badge { font-size: 12px; }
            .seat-row { grid-template-columns: 78px 8px 1fr; }
            .seat-info { font-size: 12px; }
            .seat-photo { width: 96px; height: 86px; }
            .roll-box { width: 96px; }
            .roll-label { font-size: 25px; }
            .roll-value { font-size: 25px; }
        }
    </style>
    <div class="seat-plan-page">
    @if($studentList->count()>0)
        <div class="row seat-page-meta">
            <div class="col-6 col-md-3 mb-2"><b>Group:</b>  {{ $sectionName }}</div>
            <div class="col-6 col-md-3 mb-2"><b>Class:</b>  {{ $className }}</div>
            <div class="col-6 col-md-3 mb-2"><b>Session:</b> {{ $sessionName }}</div>
            <div class="col-6 col-md-3 mb-2"><b>Exam:</b> {{ $examName }}</div>
        </div>
        <div class="card">
            <div class="card-header">Seat Plan Cards</div>
            <div class="card-body" id="seatPlanFull">
                <div class="seat-plan-controls d-print-none">
                    <button class="btn btn-success btn-lg mb-3" onclick="window.print()"><i class="fa-light fa-print"></i> All Print</button>
                    <a href="{{ route('admitCard') }}" class="btn btn-lg btn-primary mb-3"><i class="fa-solid fa-arrow-left"></i> Go Back</a>
                </div>

                <div class="seat-grid">
                    @foreach($studentList as $std)
                        @php
                            $deptName = optional(\App\Models\Department::find((int)($std->departmentName ?? 0)))->departmentName;
                            $groupText = $deptName ?: 'N/A';
                            $photoUrl = !empty($std->avatar)
                                ? asset('/public/upload/image/student/'.$std->avatar)
                                : asset('/public/back-office/img/avatar.jpeg');
                        @endphp
                        <div class="seat-card">
                            <div class="seat-header">
                                @if(!empty($logo))
                                    <img src="{{ asset('/public/upload/image/cultivation/'.$logo) }}" alt="logo" class="seat-logo">
                                @else
                                    <div class="seat-logo"></div>
                                @endif
                                <div class="seat-head-lines">
                                    <div class="seat-school">{{ strtoupper($insName) }}</div>
                                    <div class="seat-location">{{ $location }}</div>
                                </div>
                            </div>

                            <div class="seat-badge-wrap">
                                <span class="seat-badge">{{ $examName }} Seat Plan</span>
                            </div>

                            <div class="seat-body">
                                <div class="seat-info">
                                    <div class="seat-row"><span class="k">Student ID</span><span>:</span><span>{{ $std->stdId ?: $std->id }}</span></div>
                                    <div class="seat-row"><span class="k">Name</span><span>:</span><span>{{ trim(($std->fullName ?? '').' '.($std->sureName ?? '')) }}</span></div>
                                    <div class="seat-row"><span class="k">Section</span><span>:</span><span>{{ $sectionName }}</span></div>
                                    <div class="seat-row"><span class="k">Group</span><span>:</span><span>{{ $groupText }}</span></div>
                                    <div class="seat-row"><span class="k">Session</span><span>:</span><span>{{ $sessionName }}</span></div>
                                    <div class="seat-row"><span class="k">Gender</span><span>:</span><span>{{ $std->gender ?: 'N/A' }}</span></div>
                                </div>

                                <div>
                                    <img src="{{ $photoUrl }}" alt="{{ $std->stdId }}" class="seat-photo">
                                    <div class="roll-box">
                                        <div class="roll-label">Roll</div>
                                        <div class="roll-value">{{ is_numeric($std->getRawOriginal('rollNumber')) ? (int)$std->getRawOriginal('rollNumber') : $std->rollNumber }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="seat-plan-controls d-print-none mt-2">
                    <button class="btn btn-success btn-lg" onclick="window.print()"><i class="fa-light fa-print"></i> All Print</button>
                    <a href="{{ route('admitCard') }}" class="btn btn-lg btn-primary"><i class="fa-solid fa-arrow-left"></i> Go Back</a>
                </div>
            </div>
        </div>
    @else
    <div class="alert alert-info">
        Sorry! No data found
    </div>
    <div class="mb-4"> <a href="{{ route('admitCard') }}" class="btn btn-primary"><i class="fa-solid fa-arrow-left"></i> Back</a></div>
    @endif
    </div>
@endsection