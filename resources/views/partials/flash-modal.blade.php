@php
    $flashMessages = [];
    if(session()->has('success')) $flashMessages['success'] = session('success');
    if(session()->has('error')) $flashMessages['danger'] = session('error');
    if(session()->has('warning')) $flashMessages['warning'] = session('warning');
    if(session()->has('info')) $flashMessages['info'] = session('info');
    $validationErrors = isset($errors) && $errors->any() ? $errors->all() : [];
    $hasAnyFlash = count($flashMessages) > 0 || count($validationErrors) > 0;
@endphp

<!-- Global Flash Modal -->
<div class="modal fade" id="globalFlashModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Notice</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        @foreach($flashMessages as $type => $msg)
            <div class="alert alert-{{ $type }} mb-2">{!! nl2br(e($msg)) !!}</div>
        @endforeach

        @if(count($validationErrors) > 0)
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach($validationErrors as $ve)
                        <li>{{ $ve }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<!-- Programmatic helper to show global flash from client-side JS -->
<script>
  function showGlobalFlash(message, type='info'){
    try{
      var modalEl = document.getElementById('globalFlashModal');
      if(!modalEl) return;
      // hide inline alerts outside modal
      document.querySelectorAll('.alert').forEach(function(el){ if(!el.closest('#globalFlashModal')) el.style.display='none'; });
      var body = modalEl.querySelector('.modal-body');
      if(body) body.innerHTML = '<div class="alert alert-'+(type||'info')+' mb-0">'+message+'</div>';
      if(typeof bootstrap !== 'undefined' && typeof bootstrap.Modal === 'function'){
        var m = new bootstrap.Modal(modalEl, {});
        m.show();
      } else {
        try{ $('#globalFlashModal').modal('show'); }catch(e){}
      }
    }catch(e){ console.warn('showGlobalFlash error', e); }
  }
</script>

@if($hasAnyFlash)
@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function(){
        try{
            var modalEl = document.getElementById('globalFlashModal');
      if(modalEl){
        // Hide any inline alerts outside the modal to prevent duplicate messages
        document.querySelectorAll('.alert').forEach(function(el){
          if(!el.closest('#globalFlashModal')) el.style.display = 'none';
        });
        var bsModal = null;
        if(typeof bootstrap !== 'undefined' && typeof bootstrap.Modal === 'function'){
          bsModal = new bootstrap.Modal(modalEl, {});
          bsModal.show();
        } else {
          // fallback for older bootstrap versions
          try{ $('#globalFlashModal').modal('show'); }catch(e){}
        }
      }
        }catch(e){
            // fallback for older jQuery/bootstrap versions
      try{ $('#globalFlashModal').modal('show'); }catch(_){}
        }
    });
</script>
@endpush
@endif
