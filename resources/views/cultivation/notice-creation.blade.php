@extends('academic.include')
@section('backTitle')
Notice Creation
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
                                    <h3>Notice Creation</h3>
                                </div>
                            </div>
                            <form class="form" action="{{ route('saveNotice') }}" method="POST" enctype="multipart/form-data">
                                @csrf
                                <div class="row">
                                    <div class="col-12 form-group">
                                        <label>Notice Type *</label>
                                        <select name="noticeType" id="noticeType" class="form-select form-control" required>
                                            <option value="1">General Notice</option>
                                            <option value="2">Image/PDF Notice</option>
                                        </select>
                                    </div>
                                    <div class="col-12 form-group">
                                        <label>Headline *</label>
                                        <input type="text" name="noticeHeadline" placeholder="Enter notice headline" class="form-control" required>
                                    </div>
                                    <div id="generalFields" class="col-12 form-group">
                                        <label>Description *</label>
                                        <textarea name="noticeBody" placeholder="Describe the details of the notice" class="form-control" rows="12"></textarea>
                                    </div>
                                    <div id="fileFields" class="col-12 form-group d-none">
                                        <label>Attachment (Image/PDF) *</label>
                                        <input type="file" name="attachment" class="form-control" accept="image/*,.pdf">
                                        <small class="text-muted">Allowed: jpg, jpeg, png, gif, pdf. Max 5MB.</small>
                                    </div>
                                    <button type="submit" class="btn btn-success mx-2">Create Notice</button> 
                                    <a href="{{ route('noticeList') }}" class="btn btn-primary mx-2">All Notice</a>
                                </div>
                            </form>
                            <script>
                            document.addEventListener('DOMContentLoaded', function () {
                                const typeEl = document.getElementById('noticeType');
                                const generalFields = document.getElementById('generalFields');
                                const fileFields = document.getElementById('fileFields');
                                const bodyEl = document.querySelector('textarea[name="noticeBody"]');
                                const fileEl = document.querySelector('input[name="attachment"]');
                                function toggleNoticeFields() {
                                    const type = parseInt(typeEl.value, 10);
                                    if (type === 2) {
                                        generalFields.classList.add('d-none');
                                        fileFields.classList.remove('d-none');
                                        if (bodyEl) bodyEl.removeAttribute('required');
                                        if (fileEl) fileEl.setAttribute('required','required');
                                    } else {
                                        generalFields.classList.remove('d-none');
                                        fileFields.classList.add('d-none');
                                        if (bodyEl) bodyEl.setAttribute('required','required');
                                        if (fileEl) fileEl.removeAttribute('required');
                                    }
                                }
                                typeEl.addEventListener('change', toggleNoticeFields);
                                toggleNoticeFields();
                            });
                            </script>
                        </div>
                    </div>
                </div>
@endsection