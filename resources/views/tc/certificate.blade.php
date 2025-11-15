@extends('cultivation.include')
@section('backTitle')
Transfer Certificate
@endsection
@section('backIndex')
<div class="row justify-content-center">
    <div class="col-12 d-print-none mb-2 d-flex justify-content-end">
        <a href="{{ route('studentList') }}" class="btn btn-outline-secondary btn-sm me-2"><i class="fa-solid fa-arrow-left"></i> Back</a>
        @if(!empty($tc))
        <a href="{{ route('tc.edit', $tc->id) }}" class="btn btn-outline-primary btn-sm me-2"><i class="fa-solid fa-pen"></i> Edit</a>
        @endif
        <button type="button" class="btn btn-success btn-sm" onclick="window.print()"><i class="fa-solid fa-print"></i> Print</button>
    </div>
    <div class="col-lg-10">
        <style>
            .cert-pill{display:inline-block;background:#0b6b3a;color:#fff;padding:4px 14px;border-radius:14px;font-weight:700;margin-top:6px;-webkit-print-color-adjust:exact;print-color-adjust:exact;font-size:14px}
            .line{border-bottom:1px dotted #666;display:inline-block;min-width:140px}
            .frame-out{border:6px solid #14532d;padding:8px;background:#fff;-webkit-print-color-adjust:exact;print-color-adjust:exact}
            .frame-in{border:2px solid #14532d;padding:16px 18px 12px 14px;-webkit-print-color-adjust:exact;print-color-adjust:exact;position:relative}
            .wm-logo{position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);opacity:0.08;width:37%;max-width:460px;z-index:0;pointer-events:none;user-select:none;filter:grayscale(100%);-webkit-print-color-adjust:exact;print-color-adjust:exact}
            .frame-in > :not(.wm-logo){position:relative;z-index:2}
            .paper-a4{width:297mm;max-width:100%;margin:0 auto; outline:1px solid #e5e7eb; background:#fff; padding:6mm; -webkit-print-color-adjust:exact; print-color-adjust:exact}
            @page { size: A4 landscape; margin: 8mm; }
            @media print{
                .cert-pill{border:1px solid #000;background:#0b6b3a !important;color:#fff !important;padding:4px 14px;border-radius:14px;font-weight:700;margin-top:6px !important;-webkit-print-color-adjust:exact;print-color-adjust:exact;font-size:14px}
                html,body{height:auto !important;overflow:visible !important;background:#fff !important;margin:0 !important}
                #wrapper,.wrapper,.bg-ash,.dashboard-page-one,.dashboard-content-one{background:#fff !important}
                .sidebar-main,.header-menu-one,.breadcrumbs-area,.footer-wrap-layout1,.d-print-none{display:none !important}
                .dashboard-content-one{margin-left:0 !important}
                .paper-a4{width:100% !important;max-width:100% !important;margin:0 !important;padding:5mm !important; outline:1px solid #e5e7eb; page-break-inside:avoid;}
                .wm-logo{opacity:0.08 !important;width:24% !important;max-width:340px !important}
                .card,.card-body,.card-header,.card-footer{box-shadow:none !important;border:none !important;background:#fff !important}
                .card-header{padding:2px 0 2px 0 !important}
                .card-body{padding:5mm 6mm !important; font-size:14px !important; line-height:1.5 !important}
                .card-footer{padding:4mm 6mm !important}
                .my-4{margin:0 !important}
                .mt-4{margin-top:4px !important}
                .frame-out{padding:4mm !important}
                .frame-in{padding:4mm 5mm 3mm 5mm !important}
            }
        </style>
        <div class="card border-success shadow-lg my-4 paper-a4" style="font-family:'Segoe UI', 'Times New Roman', serif;">
            <div class="card-header bg-white py-1">
                @include('components.institute-header')
                <div class="text-center"><span class="cert-pill">Transfer Certificate</span></div>
            </div>
            <div class="card-body px-4 py-3" style="font-size:16px;background:#fff;">
                <div class="frame-out">
                    <div class="frame-in">
                        @if(!empty($config?->logo))
                            @php
                                $appBase = rtrim(config('app.url'), '/').'/public';
                                $logoFile = $config->logo;
                                $logoSrc = preg_match('~^https?://~i', $logoFile) ? $logoFile : $appBase.'/upload/image/cultivation/'.$logoFile;
                            @endphp
                            <img class="wm-logo" src="{{ $logoSrc }}" alt="Watermark">
                        @endif

                        <div class="d-flex justify-content-between" style="font-size:15px;">
                            <div><span class="fw-bold">Ref No:</span> <span class="line">&nbsp;{{ $tc->ref_no ?: 'N/A' }}&nbsp;</span></div>
                            <div><span class="fw-bold">Date:</span> <span class="line">&nbsp;{{ $tc->issue_date ? date('d/m/Y', strtotime($tc->issue_date)) : '' }}&nbsp;</span></div>
                        </div>

                        <p class="mt-4" style="line-height:1.6">This is to certify that <strong>{{ $tc->student_name }}</strong>,
                        son/daughter of <strong>{{ $tc->father_name }}</strong> and <strong>{{ $tc->mother_name }}</strong>,
                        resident of <strong>{{ $tc->address }}</strong>, was a bona fide student of this institution.
                        He/She studied in class <strong>{{ $tc->leaving_class ?? $tc->class_name }}</strong> in the session <strong>{{ $tc->session }}</strong>
                        bearing Roll No. <strong>{{ $tc->roll_no }}</strong> and Reg/ID No. <strong>{{ $tc->reg_no }}</strong>.
                        His/Her date of birth is <strong>{{ isset($tc->dob) ? \Carbon\Carbon::parse($tc->dob)->format('jS F, Y') : '' }}</strong>.
                        He/She has been granted a Transfer Certificate on his/her request due to <strong>{{ $tc->reason ?? 'personal grounds' }}</strong>.
                        </p>

                        <p>During his/her stay, his/her conduct was <strong>{{ $tc->conduct ?? 'good' }}</strong> and character was <strong>{{ $tc->character ?? 'good' }}</strong>. There are no dues outstanding against him/her as per office records.</p>

                        @if(!empty($tc->remarks))
                            <p><strong>Remarks:</strong> {{ $tc->remarks }}</p>
                        @endif
                    </div>
                </div>
            </div>
            <div class="card-footer bg-white border-0 mt-1 px-3 pb-2">
                <div class="row">
                    <div class="col-md-6">
                        <div>Composed by: <strong>{{ $tc->composed_by }}</strong></div>
                        <div>Date: <strong>{{ $tc->composed_date ? date('d/m/Y', strtotime($tc->composed_date)) : '' }}</strong></div>
                    </div>
                    <div class="col-md-6 text-right">
                        <div style="margin-bottom:2px;">
                        @if(!empty($principalSign))
                            <div><img src="{{ asset('public/upload/image/cultivation/'.$principalSign) }}" alt="Signature" style="height:40px;"></div>
                        @endif
                        </div>
                        <div style="margin-bottom:8px;">__________________________</div>
                        <div><strong>{{ $headmasterName }}</strong><br>Head Master/Principal</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@if(!empty($autoPrint))
<script>
window.addEventListener('load', function(){ setTimeout(function(){ window.print(); }, 200); });
</script>
@endif
@endsection
