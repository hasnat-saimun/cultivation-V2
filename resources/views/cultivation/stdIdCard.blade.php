@extends('cultivation.include')
@section('backTitle')
{{$std->fullName}} ID Card
@endsection
@section('backIndex')
                <!-- Dashboard summery Start Here -->
                <div class="row gutters-20 mb-4">
                    <!-- Admit Form Area Start Here -->
                    <div class="card height-auto col-10 mx-auto">
                        <div class="card-body">
                            <div class="heading-layout1">
                                <div class="item-title">
                                    <h3>Student ID Card</h3>
                                </div>
                            </div>
                            @if(isset($std))
                            @php 
                            $sessionData  = \App\Models\sessionManage::find($std->sessName);
                            $sectionData  = \App\Models\sectionManage::find($std->sectionName);
                            $classData  = \App\Models\classManage::find($std->className);
                            $classData  = \App\Models\classManage::find($std->className);
                            $departmentData  = \App\Models\Department::find($std->departmentName);
                                if(null !== $sessionData && $sessionData->count()>0){
                                    $sessionName= $sessionData->session;
                                }else{
                                    $sessionName= '-';
                                }

                                if(null !== $classData && $classData->count()>0){
                                    $className = $classData->className;
                                }else{
                                    $className= '-';
                                }

                                if(null !== $departmentData && $departmentData->count()>0){
                                    $departmentName = $departmentData->departmentName;
                                }else{
                                    $departmentName= '-';
                                }




                                if(null !== $sectionData && $sectionData->count()>0){
                                    $sectionName = $sectionData->section;
                                }else{
                                    $sectionName= '-';
                                }
                            @endphp 
                            <div class="row">
                                <div class="col-6 mx-auto">
                                    <div class="row" id="idCardOne">
                                        <!-- ID CARD DESIGN ONE -->
                                        <div class="col-6 col-md-12 row mb-4">
                                            <div class="col-md-12">
                                                <div class="id-bg p-2 text-center pt-1 row">
                                                    <div class="col-12">
                                                        @include('cultivation.stdIdHeader')
                                                    </div>
                                                    <div class="col-6 mx-auto display-5 fw-bold bg-success text-white rounded mb-2">STUDENT ID CARD</div>
                                                    <div class="row mt-1 align-items-center no-gutter">
                                                        <div class="col-3 mt-4">
                                                            @if(!empty($std->avatar))
                                                            <img src="{{ asset('/public/upload/image/student/') }}/{{ $std->avatar }}" alt="{{ $std->stdId }}" class="w-100 img-thumbnail">
                                                            @else
                                                            <img src="{{ asset('/public/back-office/img/') }}/avatar.jpeg" alt="{{ $std->stdId }}" class="w-100 img-thumbnail">
                                                            @endif
                                                        </div>
                                                        <div class="col-9 text-left text-dark">
                                                            <p class="mb-0"><span class="fw-bold"> Student ID:</span> {{ $std->stdId }}</p>
                                                            <p class="mb-0"><span class="fw-bold"> Name:</span> {{ $std->fullName }} {{ $std->lastName }}</p>
                                                            <div class="row mb-0">
                                                                <div class="col-6">
                                                                    <p class="mb-0"><span class="fw-bold"> Roll Number:</span> {{ $std->rollNumber}}</p>
                                                                </div>
                                                                <div class="col-6">
                                                                    <p class="mb-0"><span class="fw-bold"> Class:</span> {{ $className}}</p>
                                                                </div>
                                                            </div>
                                                            <div class="row mb-0">
                                                                <div class="col-6">
                                                                    <p class="mb-0"><span class="fw-bold"> Department:</span> {{ $departmentName}}</p>
                                                                </div>
                                                                <div class="col-6">
                                                                    <p class="mb-0"><span class="fw-bold"> Session:</span> {{ $sessionName}}</p>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="text-center mt-4 col-4">
                                                            <p class="fw-bold text-dark mb-0">Student Sign</p>
                                                        </div>
                                                        <div class="text-right mt-4 col-8">
                                                            <p class="fw-bold text-dark mb-0 mr-4 pr-4">Authorize Sign</p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <button class="btn btn-success btn-lg mb-4 d-print-none mt-4" onclick="printDiv('idCardOne')">Print</button>
                                        </div>
                                    @extends('cultivation.include')
                                    @section('backTitle')
                                    {{ $card['name'] }} ID Card
                                    @endsection
                                    @section('backIndex')
                                    <div class="row gutters-20 mt-4">
                                        <div class="col-12">
                                            <div class="card card-default shadow-sm">
                                                <div class="card-header bg-gradient-info text-white d-flex justify-content-between align-items-center flex-wrap gap-2">
                                                    <h3 class="mb-0"><i class="fa-solid fa-id-card"></i> Student ID Card</h3>
                                                    <div class="d-flex gap-2 align-items-center">
                                                        <button type="button" class="btn btn-success btn-sm" onclick="printCards()"><i class="fa-solid fa-print"></i> Print</button>
                                                        @php $fmt = request()->get('format','landscape'); $qsL = ['format'=>'landscape']; $qsP = ['format'=>'portrait']; @endphp
                                                        <div class="btn-group" role="group" aria-label="Format">
                                                            <a href="{{ route('stdIdCard', ['stdId'=>$std->id] + $qsL) }}" class="btn btn-outline-light btn-sm {{ $fmt==='landscape' ? 'active' : '' }}">Landscape</a>
                                                            <a href="{{ route('stdIdCard', ['stdId'=>$std->id] + $qsP) }}" class="btn btn-outline-light btn-sm {{ $fmt==='portrait' ? 'active' : '' }}">Portrait</a>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="card-body">
                                                    <div id="printArea">
                                                        @if(request()->get('format','landscape') === 'portrait')
                                                            @include('cultivation.partials.student-id-card-portrait', ['card' => $card, 'branding' => $branding])
                                                        @else
                                                            @include('cultivation.partials.student-id-card', ['card' => $card, 'branding' => $branding])
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    }</div>
                                    @endsection

                                    @push('styles')
                                    <style>
                                    :root { --id-primary:#0f172a; --id-accent:#2563eb; --id-muted:#6b7280; --id-border:#e5e7eb; --id-bg:#f8fafc; --card-w:85.60mm; --card-h:53.98mm; }
                                    .bg-gradient-info { background: linear-gradient(135deg,#17a2b8 0%,#138496 100%); }
                                    .id-card-shell { max-width: 440px; margin:0 auto; color:var(--id-primary); font-family:'Segoe UI', Tahoma, sans-serif; }
                                    .id-face { background:#fff; border:1px solid var(--id-border); border-radius:16px; overflow:hidden; box-shadow:0 12px 30px rgba(15,23,42,.12); display:flex; flex-direction:column; gap:14px; padding:16px; }
                                    .id-face.back { margin-top:12px; box-shadow:0 6px 18px rgba(15,23,42,.08); }
                                    .id-header { margin:-16px -16px 0 -16px; padding:14px 16px; background:linear-gradient(115deg,var(--id-primary) 0%, var(--id-accent) 100%); color:#fff; display:flex; align-items:center; gap:12px; }
                                    .logo-wrap { height:58px; width:58px; background:rgba(255,255,255,.12); border-radius:14px; display:flex; align-items:center; justify-content:center; }
                                    .logo-img { max-height:50px; width:auto; object-fit:contain; }
                                    .logo-fallback { font-weight:700; font-size:22px; }
                                    .ins-meta { flex:1; min-width:0; }
                                    .ins-name { font-weight:700; font-size:1.05rem; line-height:1.2; }
                                    .ins-sub { font-size:.82rem; color:rgba(255,255,255,.85); line-height:1.3; }
                                    .badge-chip { background:rgba(255,255,255,.15); color:#fff; padding:8px 12px; border-radius:999px; font-weight:700; letter-spacing:.5px; text-transform:uppercase; font-size:.78rem; }
                                    .id-body { display:grid; grid-template-columns:120px 1fr; gap:14px; align-items:center; }
                                    .photo-box { height:140px; border-radius:14px; overflow:hidden; border:1px solid var(--id-border); background:var(--id-bg); }
                                    .photo { width:100%; height:100%; object-fit:cover; }
                                    .info .student-name { font-weight:800; font-size:1.1rem; }
                                    .info .meta { color:var(--id-muted); font-size:.9rem; margin-bottom:6px; }
                                    .info .meta.muted { margin-top:6px; }
                                    .pill-row { display:flex; flex-wrap:wrap; gap:8px; margin-bottom:6px; }
                                    .pill { background:var(--id-bg); border:1px solid var(--id-border); border-radius:999px; padding:6px 10px; font-size:.82rem; color:var(--id-primary); }
                                    .id-footer { display:flex; justify-content:space-between; gap:10px; border-top:1px dashed var(--id-border); padding-top:10px; }
                                    .foot-col .label { font-size:.75rem; color:var(--id-muted); letter-spacing:.3px; }
                                    .foot-col .value { font-weight:700; font-size:.95rem; }
                                    .id-signatures { display:flex; justify-content:space-between; gap:12px; margin-top:10px; }
                                    .sig-line { flex:1; text-align:center; border-top:1px solid var(--id-border); padding-top:8px; font-size:.85rem; color:var(--id-muted); }
                                    .back-title { font-weight:700; font-size:1rem; color:var(--id-primary); }
                                    .back-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); gap:10px; }
                                    .back-grid .label { font-size:.8rem; color:var(--id-muted); }
                                    .back-grid .value { font-weight:700; font-size:.95rem; }
                                    @media print { .card, .btn, .alert, .card-header { display:none !important; } #printArea { display:block !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; } .id-card-shell{ page-break-inside:avoid; } }
                                    @media print {
                                        #printArea { width:auto !important; }
                                        .id-card-shell { width: var(--card-w) !important; }
                                        .id-face { width: var(--card-w) !important; height: var(--card-h) !important; border-radius: 3.18mm !important; }
                                        @page { size: A4; margin: 10mm; }
                                    }
                                    </style>
                                    @endpush

                                    @push('scripts')
                                    <script>
                                    function printCards(){ const printArea=document.getElementById('printArea'); if(!printArea) return; const original=document.body.innerHTML; document.body.innerHTML=printArea.innerHTML; window.print(); document.body.innerHTML=original; }
                                    </script>
                                    @endpush