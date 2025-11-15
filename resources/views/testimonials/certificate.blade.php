@extends('cultivation.include')
@section('backTitle')
Testimonial Certificate
@endsection
@section('backIndex')
 <div class="row justify-content-center">
    <div class="col-12 d-print-none mb-2 d-flex justify-content-end">
        <a href="{{ route('studentList') }}" class="btn btn-outline-secondary btn-sm me-2"><i class="fa-solid fa-arrow-left"></i> Back</a>
        @if(!empty($testimonial))
        <a href="{{ route('testimonials.edit', $testimonial->id) }}" class="btn btn-outline-primary btn-sm me-2"><i class="fa-solid fa-pen"></i> Edit</a>
        @endif
        <button type="button" class="btn btn-success btn-sm" onclick="window.print()"><i class="fa-solid fa-print"></i> Print</button>
    </div>
    <div class="col-lg-10">
        <style>
            .cert-pill{display:inline-block;background:#042954;color:#fff;padding:4px 14px;border-radius:14px;font-weight:700;margin-top:6px;-webkit-print-color-adjust:exact;print-color-adjust:exact;font-size:14px}
            .line{border-bottom:1px dotted #666;display:inline-block;min-width:140px}
            .frame-out{border:6px solid #0e56a9;padding:8px;background:#fff;-webkit-print-color-adjust:exact;print-color-adjust:exact}
            .frame-in{border:2px solid #0e56a9;padding:16px 18px 12px 14px;-webkit-print-color-adjust:exact;print-color-adjust:exact;position:relative}
            .wm-logo{position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);opacity:0.08;width:37%;max-width:460px;z-index:0;pointer-events:none;user-select:none;filter:grayscale(100%);-webkit-print-color-adjust:exact;print-color-adjust:exact}
            .frame-in > :not(.wm-logo){position:relative;z-index:2}
            .hdr-logo{position:absolute;left:10px;top:50%;transform:translateY(-50%);height:52px;width:52px;object-fit:contain}
            .header-block{position:relative;min-height:90px}
            .paper-a4{max-width:11.69in;margin:0 auto; outline:1px solid #e5e7eb; background:#fff; padding:7mm; -webkit-print-color-adjust:exact; print-color-adjust:exact}
            @page { size: A4 landscape; margin: 8mm 5mm 6mm 8mm; }
            @media print{
                html,body{height:auto !important;overflow:visible !important;background:#fff !important;margin:0 !important}
                #wrapper,.wrapper,.bg-ash,.dashboard-page-one,.dashboard-content-one{background:#fff !important}
                .sidebar-main,.header-menu-one,.breadcrumbs-area,.footer-wrap-layout1,.d-print-none{display:none !important}
                .dashboard-content-one{margin-left:0 !important}
                .paper-a4{width:auto;max-width:100%;margin:0 !important;padding:6mm !important; outline:1px solid #e5e7eb}
                .hdr-logo{height:48px;width:48px;left:8px}
                .header-block{min-height:86px}
                .card,.card-body,.card-header,.card-footer{box-shadow:none !important;border:none !important;background:#fff !important}
                .card-header{padding:4px 0 4px 0 !important}
                .card-body{padding:6px 10px !important; font-size:15px !important}
                .card-footer{padding:6px 10px !important}
                .my-4{margin:0 !important}
                .mt-4{margin-top:6px !important}
                .frame-out{padding:6px !important}
                .frame-in{padding:12px 14px 8px 12px !important}
                .paper-a4 p{margin:6px 0 !important}
                .paper-a4 h2,.paper-a4 h3,.paper-a4 h4{margin:6px 0 !important}
                .frame-out{page-break-inside:avoid}
                .location{font-size:14px;line-height:1.2}
            }
        </style>
        <div class="card border-success shadow-lg my-4 paper-a4" style="font-family:'Segoe UI', 'Times New Roman', serif;">
            <div class="card-header bg-white py-1 header-block">
                @if($logo)
                    <img class="hdr-logo" src="{{ asset('public/upload/image/cultivation/'.$logo) }}" alt="Logo">
                @endif
                <div class="text-center">
                    <h2 class="mb-0" style="font-weight:bold;color:#042954;">{{ $instituteName }}</h2>
                    <div style="font-size:12.5px;line-height:1.2;white-space:normal;">
                        <div class="fw-bold location">{{ $address }}</div><div class="fw-bold"> @if($establishDate) Estd. {{ $establishDate }} @endif </div><div class="fw-bold"> @if(!empty($email)) Email: {{ $email }} @endif @if(!empty($mobile)), Mobile: {{ $mobile }} @endif</div>
                    </div>
                    <div class="cert-pill">Testimonial Certificate</div>
                </div>
            </div>
            <div class="card-body px-4 py-3" style="font-size:17px;background:#fff;">
                <div class="frame-out">
                    <div class="frame-in">
                        @if($logo)
                        <img class="wm-logo" src="{{ asset('public/upload/image/cultivation/'.$logo) }}" alt="Watermark">
                        @endif
                        <div class="d-flex justify-content-between" style="font-size:16px;">
                            <div>SL: <span class="line">&nbsp;{{ $testimonial->ref_no ?: 'N/A' }}&nbsp;</span></div>
                            <div>Date: <span class="line">&nbsp;{{ $testimonial->issue_date ? date('d/m/Y', strtotime($testimonial->issue_date)) : '' }}&nbsp;</span></div>
                        </div>
                        <p class="mt-4" style="line-height:1.6">This is to certify that <strong>{{ $admission->fullName ?? $admission->student_name ?? $admission->studentName ?? $testimonial->student_name }} {{  $admission->sureName ?? "" }}</strong>, son/daughter of <strong>{{ $admission->father ?? $admission->father_name ?? $testimonial->father_name }}</strong> and <strong>{{ $admission->mother ?? $admission->mother_name ?? $testimonial->mother_name }}</strong>, Address <strong>{{ $admission->address ?? ($testimonial->village ? $testimonial->village : '') }}</strong>@if(!empty($testimonial->district)), Dist. <strong>{{ $testimonial->district }}</strong>@endif, passed the <strong>{{ $testimonial->exam_name ?? 'S.S.C./H.S.C' }} </strong>Examination in <strong>{{ $testimonial->ssc_year }}</strong> from this school under the <strong>{{ $testimonial->education_board ?? 'Cumilla' }} </strong> board bearing Roll No. <strong>{{ $testimonial->roll_no }}</strong> and Registration No. <strong>{{ $testimonial->reg_no }}</strong> and obtained Grade Point Average <strong>{{ $testimonial->gpa }}</strong> ({{ $testimonial->grade }}) in <strong>{{ $testimonial->subject }}</strong>.</p>
                        <div class="mb-3">His/Her DOB (in words): <span class="line">&nbsp;{{ isset($admission->dob) ? \Carbon\Carbon::parse($admission->dob)->format('jS F, Y') : ($testimonial->dob ? \Carbon\Carbon::parse($testimonial->dob)->format('jS F, Y') : '') }}&nbsp;</span></div>
                        <p>To the best of my knowledge he did not take part in any activities subversive of the state or discipline. His conduct and character are good.</p>
                        <p>I wish him every success in life.</p>
                    </div>
                </div>
            </div>
            <div class="card-footer bg-white border-0 mt-1 px-3 pb-2">
                <div class="row">
                    <div class="col-md-6">
                        <div>Composed by: <strong>{{ $testimonial->composed_by }}</strong></div>
                        <div>Date: <strong>{{ $testimonial->composed_date ? date('d/m/Y', strtotime($testimonial->composed_date)) : '' }}</strong></div>
                    </div>
                    <div class="col-md-6 text-right">
                        @if(!empty($principalSign))
                            <div class="mb-1"><img src="{{ asset('public/upload/image/cultivation/'.$principalSign) }}" alt="Signature" style="height:40px;"></div>
                        @endif
                        <div style="margin-bottom:8px;">__________________________</div>
                        <div><strong>{{ $headmasterName }}</strong><br>Head Master/Principle</div>
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
