@extends('cultivation.include')
@section('backTitle') User Guide @endsection
@section('backIndex')
<div class="row justify-content-center">
    <div class="col-lg-11">
        <div class="card shadow-sm mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Cultivation V2 – User Guide <span class="badge bg-info text-dark" style="font-size:11px;">v{{ $version }}</span> <span class="text-muted" style="font-size:11px;">Updated: {{ $updatedAt }}</span></h5>
                <div>
                    <a href="{{ route('cultivationIndex') }}" class="btn btn-sm btn-outline-secondary">Dashboard</a>
                    <button id="print-guide" class="btn btn-sm btn-primary">Print</button>
                </div>
            </div>
            <div class="card-body" id="guide-wrapper">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div class="flex-grow-1 me-2">
                        <input type="text" id="guide-search" class="form-control form-control-sm" placeholder="Search in guide (live filter)...">
                    </div>
                    <div>
                        <span id="search-count" class="text-muted" style="font-size:12px;"></span>
                    </div>
                </div>
                <div id="guide-content" class="markdown-body">{!! $html !!}</div>
            </div>
        </div>
    </div>
</div>
@endsection
@push('styles')
<style>
.markdown-body {font-size:15px; line-height:1.55;}
.markdown-body h1,.markdown-body h2,.markdown-body h3 {margin-top:24px; font-weight:600;}
.markdown-body pre {background:#0f172a; color:#f1f5f9; padding:12px 14px; border-radius:6px; font-size:13px; overflow:auto;}
.markdown-body code {background:#f1f5f9; padding:2px 5px; border-radius:4px; font-size:13px;}
.markdown-body table {width:100%; border-collapse:collapse; margin:16px 0;}
.markdown-body th, .markdown-body td {border:1px solid #e2e8f0; padding:6px 8px;}
.markdown-body img {max-width:100%; border:1px solid #e2e8f0; border-radius:4px; margin:8px 0;}
#guide-wrapper {background:#ffffff;}
.toc {background:#f8fafc; border:1px solid #e2e8f0; padding:12px 14px; border-radius:6px; margin-bottom:18px;}
.toc h5 {margin-top:0; font-size:14px; font-weight:600;}
.toc ul {list-style:none; padding-left:0; margin:0;}
.toc ul li {margin:4px 0;}
.toc a {text-decoration:none; font-size:13px;}
@media print {
    .sidebar-main,.header-menu-one,.breadcrumbs-area,.d-print-none,#print-guide {display:none!important;}
    .toc {display:none!important;}
    #guide-search, #search-count {display:none!important;}
    html, body {background:#ffffff!important;}
    .wrapper, .dashboard-page-one, .card, .card-body, #guide-wrapper, #guide-content {background:#ffffff!important;}
    .card {box-shadow:none!important; border:none!important;}
    .card-header {border-bottom:1px solid #e2e8f0;}
}
</style>
@endpush
@push('scripts')
<script>
// Build TOC from already-rendered HTML
const contentEl = document.getElementById('guide-content');
const headings = contentEl.querySelectorAll('h1, h2, h3');
if(headings.length){
    const toc = document.createElement('div');
    toc.className = 'toc';
    toc.innerHTML = '<h5>On This Page</h5>';
    const ul = document.createElement('ul');
    headings.forEach(h => {
        if(!h.id){ h.id = h.textContent.trim().toLowerCase().replace(/[^a-z0-9]+/g,'-'); }
        const li = document.createElement('li');
        li.innerHTML = `<a href="#${h.id}">${h.tagName==='H3'?'↳ ':''}${h.textContent}</a>`;
        ul.appendChild(li);
    });
    toc.appendChild(ul);
    contentEl.prepend(toc);
}
// Smooth scroll for TOC links
contentEl.addEventListener('click', e => {
    if(e.target.matches('.toc a')){
        e.preventDefault();
        const id = e.target.getAttribute('href').substring(1);
        const target = document.getElementById(id);
        if(target){ window.scrollTo({top: target.getBoundingClientRect().top + window.scrollY - 70, behavior:'smooth'}); }
    }
});
// Live search
const searchInput = document.getElementById('guide-search');
const searchCount = document.getElementById('search-count');
const searchable = Array.from(contentEl.querySelectorAll('p, li, h1, h2, h3, code, td'));
function doSearch(){
    const q = searchInput.value.trim().toLowerCase();
    let matches = 0;
    searchable.forEach(el => {
        if(!q){
            el.classList.remove('guide-hide');
            el.innerHTML = el.textContent; // remove highlight
            return;
        }
        const text = el.textContent.toLowerCase();
        if(text.includes(q)){
            matches++;
            // highlight
            const raw = el.textContent;
            const regex = new RegExp(q.replace(/[.*+?^${}()|[\]\\]/g,'\\$&'),'gi');
            el.innerHTML = raw.replace(regex, m => `<mark style="background: #fde68a;">${m}</mark>`);
            el.classList.remove('guide-hide');
        } else {
            el.classList.add('guide-hide');
        }
    });
    searchCount.textContent = q ? `${matches} match${matches!==1?'es':''}` : '';
}
searchInput.addEventListener('input', doSearch);
// Print button
document.getElementById('print-guide').addEventListener('click', () => window.print());
</script>
<style>
.guide-hide{display:none!important;}
mark{padding:0 2px; color:#1e293b;}
</style>
@endpush