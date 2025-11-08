@extends('academic.include')
@section('backTitle')
Notice Update
@endsection
@section('backIndex')
                <!-- Dashboard summery Start Here -->
                <div class="row gutters-20 mb-4">
                    <!-- Admit Form Area Start Here -->
                    <div class="card height-auto col-8 mx-auto">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-12">
                                    @if(session()->has('success'))
                                        <div class="alert alert-success w-100">
                                            {{ session()->get('success') }}
                                        </div>
                                    @endif
                                    @if(session()->has('error'))
                                        <div class="alert alert-danger w-100">
                                            {{ session()->get('error') }}
                                        </div>
                                    @endif
                                </div>
                            </div>
                            <div class="heading-layout1">
                                <div class="item-title">
                                    <h3>Notice Update</h3>
                                </div>
                            </div>
                            @if(!empty($notice))
                            @php
                                // Infer type: if attachment exists => type 2 else type 1
                                $currentType = !empty($notice->attachment) ? 2 : 1;
                            @endphp
                            <form class="form" action="{{ route('updateNotice') }}" method="POST" enctype="multipart/form-data" id="noticeEditForm">
                                @csrf
                                <input type="hidden" name="noticeId" value="{{ $notice->id }}">
                                <div class="row">
                                    <div class="col-md-4 form-group">
                                        <label>Notice Type *</label>
                                        <select name="noticeType" id="noticeType" class="form-select form-control" required>
                                            <option value="1" {{ $currentType==1?'selected':'' }}>General Notice</option>
                                            <option value="2" {{ $currentType==2?'selected':'' }}>Image/PDF Notice</option>
                                        </select>
                                    </div>
                                    <div class="col-12 form-group mt-2">
                                        <label>Headline *</label>
                                        <input type="text" name="noticeHeadline" placeholder="Enter notice headline" class="form-control" value="{{ $notice->headline }}" required>
                                    </div>
                                    <div class="col-12 form-group bodyBox {{ $currentType==2?'d-none':'' }}">
                                        <label>Description *</label>
                                        <textarea name="noticeBody" id="noticeBody" placeholder="Describe the details" class="form-control" rows="10" {{ $currentType==1?'required':'' }}>{{ $currentType==1? $notice->body : '' }}</textarea>
                                    </div>
                                    <div class="col-12 form-group attachmentBox {{ $currentType==1?'d-none':'' }}">
                                        <label>Attachment (Image/PDF) {{ !empty($notice->attachment)?'(leave blank to keep existing)':'' }} @if($currentType==2 && empty($notice->attachment))*@endif</label>
                                        <input type="file" name="attachment" id="attachment" class="form-control" accept="image/*,.pdf" {{ $currentType==2 && empty($notice->attachment)?'required':'' }}>
                                        @if(!empty($notice->attachment))
                                            @php $ext = strtolower(pathinfo($notice->attachment, PATHINFO_EXTENSION)); @endphp
                                            <div class="mt-2 p-2 border rounded bg-light">
                                                <strong>Current Attachment:</strong>
                                                @if(in_array($ext,['jpg','jpeg','png','gif','webp']))
                                                    <img src="{{ asset('public/'.$notice->attachment) }}" alt="Current" style="max-height:120px" class="d-block mt-2">
                                                @elseif($ext==='pdf')
                                                    <i class="fa-regular fa-file-pdf" style="font-size:42px;color:#c00;"></i>
                                                    <a href="{{ asset('public/'.$notice->attachment) }}" target="_blank" class="ms-2">Open PDF</a>
                                                @else
                                                    <a href="{{ asset('public/'.$notice->attachment) }}" target="_blank">Download current file</a>
                                                @endif
                                                <div class="form-check mt-2">
                                                    <input class="form-check-input" type="checkbox" value="1" id="removeAttachment" name="removeAttachment">
                                                    <label class="form-check-label" for="removeAttachment">Remove existing attachment</label>
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                    <div class="col-12 mt-3">
                                        <button type="submit" class="btn btn-success mx-2">Update Notice</button>
                                        <a href="{{ route('newNotice') }}" class="btn btn-primary mx-2">Create New</a>
                                        <a href="{{ route('noticeList') }}" class="btn btn-secondary mx-2">All Notices</a>
                                    </div>
                                </div>
                            </form>
                            <script>
                                (function(){
                                    const typeEl = document.getElementById('noticeType');
                                    const bodyBox = document.querySelector('.bodyBox');
                                    const attachBox = document.querySelector('.attachmentBox');
                                    const bodyEl = document.getElementById('noticeBody');
                                    const fileEl = document.getElementById('attachment');
                                    function toggleFields(){
                                        if(typeEl.value === '2'){
                                            bodyBox.classList.add('d-none');
                                            bodyEl.removeAttribute('required');
                                            attachBox.classList.remove('d-none');
                                            if(!document.getElementById('removeAttachment') && !fileEl.value){
                                                fileEl.setAttribute('required','required');
                                            }
                                        }else{
                                            attachBox.classList.add('d-none');
                                            fileEl.removeAttribute('required');
                                            bodyBox.classList.remove('d-none');
                                            bodyEl.setAttribute('required','required');
                                        }
                                    }
                                    typeEl.addEventListener('change',toggleFields);
                                })();
                            </script>
                            @else
                                <div class="alert alert-danger">Sorry! No data found with your query</div>
                            @endif
                        </div>
                    </div>
                </div>
@endsection