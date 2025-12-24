<div class="container-fluid mb-3">
    @include('components.institute-header')
    @php
        // Resolve query/filter context gracefully for both classwise and individual views
        $examIdCtx    = request('examId') ?? ($examId ?? ($examDetails->id ?? null));
        $classIdCtx   = request('classId') ?? ($classId ?? ($classDetails->className ?? $studentDetails->className ?? null));
        $sessionIdCtx = request('sessionId') ?? ($sessionId ?? ($studentDetails->sessName ?? null));
        $sectionIdCtx = request('sectionId') ?? ($sectionId ?? ($studentDetails->sectionId ?? null));

        $examNameCtx = '-';
        if(!empty($examIdCtx)){
            $exModel = \App\Models\Exam::find($examIdCtx);
            $examNameCtx = $exModel ? $exModel->examName : ($examName ?? '-');
        } else if(isset($examName)) {
            $examNameCtx = $examName;
        }

        $classNameCtx = '-';
        if(!empty($classIdCtx) && is_numeric($classIdCtx)){
            $clModel = \App\Models\classManage::find($classIdCtx);
            $classNameCtx = $clModel ? ($clModel->className ?? ('Class-'.$clModel->id)) : ($className ?? '-');
        } else if(isset($className)) {
            // $className may be a model or plain string
            $classNameCtx = is_object($className) ? ($className->className ?? '-') : ($className ?: '-');
        }

        $sessionNameCtx = '-';
        if(!empty($sessionIdCtx)){
            $sessModel = \App\Models\sessionManage::find($sessionIdCtx);
            $sessionNameCtx = $sessModel ? ($sessModel->session ?? ('Session-'.$sessModel->id)) : ($sessionName ?? '-');
        } else if(isset($sessionName)) {
            $sessionNameCtx = $sessionName;
        }

        $sectionNameCtx = '-';
        if(!empty($sectionIdCtx)){
            $secModel = \App\Models\sectionManage::find($sectionIdCtx);
            $sectionNameCtx = $secModel ? ($secModel->section ?? ('Section-'.$secModel->id)) : '-';
        }

        // Optional: Group info if available (alias of section/group)
    @endphp
    <div class="row g-2 align-items-center">
        <div class="col-12">
            <h4 class="fw-bold text-center my-3">Tabulation Sheet for @if($exam) - {{ $exam->examName }} @endif</h4>
            <div class="p-2 border rounded result-header-band" style="background:#f8fafc;">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="d-flex flex-wrap gap-3 small">
                        <div class="mr-4"><strong>Class:</strong> <span>{{ $classNameCtx }}</span></div>
                        <div class="mr-4"><strong>Section/Group:</strong> <span>{{ $sectionNameCtx }}</span></div>
                        <div><strong>Session:</strong> <span>{{ $sessionNameCtx }}</span></div>
                    </div>
                    <div class="d-flex align-items-center gap-3">
                        <div class="small d-none d-print-block"><strong>Printed:</strong> {{ now()->format('d M Y, h:i A') }}</div>
                        <div class="d-print-none">
                        <button type="button" class="btn btn-warning btn-sm" onclick="window.print()">
                            <i class="fas fa-print"></i> Print
                        </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>