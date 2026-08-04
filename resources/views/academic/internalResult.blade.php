@extends('academic.include')
@section('backTitle')
Internal Results Management
@endsection
@section('backIndex')
@php 
    if(!empty($itemId)):
        $items       = \App\Models\InternalResult::find($itemId);
        if(!empty($items)): 
            $title              = $items->title;
            $assignClass        = $items->assignClass;
            $assignDepartment   = $items->assignDepartment;
            $assignSection      = $items->assignSection ?? '';
            $assignSession      = $items->assignSession;
            $attachment         = $items->attachment;
        else:
            // Item not found (e.g., after delete) -> set safe defaults
            $title              = "";
            $assignClass        = "";
            $assignDepartment   = "";
            $assignSection      = "";
            $assignSession      = "";
            $attachment         = "";
        endif;
    else:
        $itemId             = null;
        $title              = "";
        $assignClass        = "";
        $assignDepartment   = "";
        $assignSection      = "";
        $assignSession      = "";
        $attachment         = "";
    endif;
    // Compute selected section id for edit compatibility with legacy text values
    $selectedSectionId = null;
    if(!empty($assignSection)){
        $existSection = \App\Models\sectionManage::find($assignSection);
        if(empty($existSection)){
            $existSection = \App\Models\sectionManage::where('section',$assignSection)->first();
        }
        $selectedSectionId = $existSection->id ?? null;
    }
@endphp
<div class="row gutters-20 mb-4">
    <div class="col-10 mx-auto">
        <div class="card">
            <div class="card-header">Internal Results Management</div>
            <div class="card-body cultivation">
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
                        @if ($errors->any())
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    </div>
                </div>
                <form action="{{ route('saveInternalResult') }}" class="form" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="itemId" value="{{ $itemId }}">
                    @csrf
                    <div class="mb-3">
                        <label for="title">Title</label>
                        <input type="text" name="title" class="form-control" placeholder="Enter the title of the internal result" value="{{ old('title', $title) }}">
                    </div>
                    <div class="mb-3">
                        <label for="assignClass">Class</label>
                        <select name="assignClass" class="form-select">
                            @if(!empty($itemId))
                            @php
                                $existClass = \App\Models\classManage::find($assignClass);
                            @endphp
                            @if(!empty($existClass))
                            <option value="{{ $existClass->id }}" selected>{{ $existClass->className }}</option>
                            @endif
                            @endif
                            @php
                                $classes = \App\Models\classManage::orderBy('id','DESC')->get();
                            @endphp
                            @if(!empty($classes))
                                @foreach($classes as $cls)
                                <option value="{{ $cls->id }}" {{ (string)old('assignClass', $assignClass) === (string)$cls->id ? 'selected' : '' }}>{{ $cls->className }}</option>
                                @endforeach
                            @else
                                <option value="">-</option>
                            @endif
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="assignDepartment">Department</label>
                        <select name="assignDepartment" class="form-select">
                            @if(!empty($itemId))
                            @php
                                $existDept = \App\Models\Department::find($assignDepartment);
                            @endphp
                            @if(!empty($existDept))
                            <option value="{{ $existDept->id }}" selected>{{ $existDept->departmentName }}</option>
                            @endif
                            @endif
                            @php
                                $department = \App\Models\Department::orderBy('id','DESC')->get();
                            @endphp
                            @if(!empty($department))
                                @foreach($department as $dept)
                                <option value="{{ $dept->id }}" {{ (string)old('assignDepartment', $assignDepartment) === (string)$dept->id ? 'selected' : '' }}>{{ $dept->departmentName }}</option>
                                @endforeach
                            @else
                                <option value="">-</option>
                            @endif
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="assignSection">Section</label>
                        <select name="assignSection" class="form-select">
                            @php $sectionList = \App\Models\sectionManage::orderBy('id','ASC')->get(); @endphp
                            @php $selectedSectionId = old('assignSection', $selectedSectionId); @endphp
                            @if(!empty($sectionList))
                                @foreach($sectionList as $sec)
                                <option value="{{ $sec->id }}" {{ ($selectedSectionId == $sec->id) ? 'selected' : '' }}>{{ $sec->section }}</option>
                                @endforeach
                            @else
                                <option value="">-</option>
                            @endif
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="assignSession">Session</label>
                        <select name="assignSession" class="form-select">
                            @if(!empty($itemId))
                            @php
                                $existSession = \App\Models\sessionManage::find($assignSession);
                            @endphp
                            @if(!empty($existSession))
                            <option value="{{ $existSession->id }}" selected>{{ $existSession->session }}</option>
                            @endif
                            @endif
                            @php
                                $session = \App\Models\sessionManage::orderBy('id','DESC')->get();
                            @endphp
                            @if(!empty($session))
                                @foreach($session as $sess)
                                <option value="{{ $sess->id }}" {{ (string)old('assignSession', $assignSession) === (string)$sess->id ? 'selected' : '' }}>{{ $sess->session }}</option>
                                @endforeach
                            @else
                                <option value="">-</option>
                            @endif
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="attachment">Attachment(PDF/Photo)</label>
                        @if(!empty($attachment))
                        <div>
                            <iframe src="{{ asset('public/upload/image/cultivation/internalResult/').'/'.$attachment }}" class="w-25" height="300px"></iframe>
                            <x-delete-action :action="route('delInternalResultContent',['id'=>$itemId])" class="fw-bold text-danger">Delete</x-delete-action>
                        </div>
                        @else
                        <input type="file" name="attachment" class="form-control-file">
                        @endif
                    </div>
                    <div class=" mt-4">
                        <button class="btn btn-success btn-lg mx-2" type="submit">Save</button>
                        <a class="btn btn-primary btn-lg mx-2" href="{{ route('internalResultManage') }}">New Internal Result</a>
                    </div>
                </form>
            </div>
            <form id="deleteInternalResultForm" method="POST" class="d-none">
                @csrf
                @method('DELETE')
            </form>
            <div class="card-header">Existing Internal Results</div>
            <div class="card-body cultivation">
                <table id="myTable" class="table table-striped">
                    <thead>
                        <tr>
                            <th>SL</th>
                            <th>Title</th>
                            <th>Class</th>
                            <th>Department</th>
                            <th>Section</th>
                            <th>Session</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if(!empty($resultList))
                        @php $x = 1; @endphp
                        @foreach($resultList as $item)
                        @php 
                            $itemClass      = \App\Models\classManage::find($item->assignClass);
                            $itemDepartment = \App\Models\Department::find($item->assignDepartment);
                            $itemSession    = \App\Models\sessionManage::find($item->assignSession);
                        @endphp
                            <tr>
                                <td>{{ $x }}</td>
                                <td>{{ $item->title }}</td>
                                <td>{{ $itemClass->className ?? '-' }}</td>
                                <td>{{ $itemDepartment->departmentName ?? '-' }}</td>
                                <td>
                                    @php 
                                        $sec = \App\Models\sectionManage::find($item->assignSection);
                                        if(empty($sec) && !empty($item->assignSection)){
                                            $sec = \App\Models\sectionManage::where('section',$item->assignSection)->first();
                                        }
                                    @endphp
                                    {{ $sec->section ?? ($item->assignSection ?? '-') }}
                                </td>
                                <td>{{ $itemSession->session ?? '-' }}</td>
                                <td>
                                    <a href="{{ route('editInternalResult',['id'=>$item->id]) }}"><i class="fa-solid fa-pen-to-square mx-2" style="color: #4125b1;"></i></a>
                                    <a href="#" class="js-delete-internal-result" data-id="{{ $item->id }}" title="Delete"><i class="fa-solid fa-trash mx-2" style="color: #c10b26;"></i></a>
                                </td>
                            </tr>
                        @php $x++; @endphp
                        @endforeach
                        @else
                            <tr>
                                <td colspan="6">Sorry! No data found</td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
            <!-- Simple Delete Confirmation Modal -->
            <div id="deleteConfirmModal" class="d-print-none" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.45); z-index:1050;">
                <div style="background:#fff; max-width:420px; margin:12% auto; border-radius:8px; box-shadow:0 6px 24px rgba(0,0,0,0.2);">
                    <div style="padding:16px 20px; border-bottom:1px solid #eee;">
                        <h5 class="mb-0">Confirm Deletion</h5>
                    </div>
                    <div style="padding:18px 20px;">
                        Are you sure you want to delete this Internal Result?
                    </div>
                    <div style="padding:14px 20px; display:flex; justify-content:flex-end; gap:10px; border-top:1px solid #f1f1f1;">
                        <button type="button" id="cancelDeleteBtn" class="btn btn-secondary">Cancel</button>
                        <button type="button" id="confirmDeleteBtn" class="btn btn-danger">Delete</button>
                    </div>
                </div>
            </div>

            <!-- Lightweight Toast for success/error -->
            <div id="miniToast" class="d-print-none" style="position:fixed; top:20px; right:20px; z-index:1060; display:none;">
                <div id="miniToastBody" style="background:#1e7e34; color:#fff; padding:12px 16px; border-radius:6px; box-shadow:0 6px 24px rgba(0,0,0,0.15);"></div>
            </div>

            <script>
            document.addEventListener('DOMContentLoaded', function(){
                // Delete modal logic
                const modal = document.getElementById('deleteConfirmModal');
                const cancelBtn = document.getElementById('cancelDeleteBtn');
                const confirmBtn = document.getElementById('confirmDeleteBtn');
                const deleteForm = document.getElementById('deleteInternalResultForm');
                let currentId = null;

                const baseDeleteUrl = "{{ route('delInternalResult',['id'=>'__ID__']) }}";

                function openModal(id){ currentId = id; modal.style.display = 'block'; }
                function closeModal(){ modal.style.display = 'none'; currentId = null; }

                document.querySelectorAll('.js-delete-internal-result').forEach(btn => {
                    btn.addEventListener('click', function(e){ e.preventDefault(); openModal(this.dataset.id); });
                });
                cancelBtn.addEventListener('click', function(){ closeModal(); });
                modal.addEventListener('click', function(e){ if(e.target === modal) closeModal(); });
                confirmBtn.addEventListener('click', function(){
                    if(!currentId) return;
                    const url = baseDeleteUrl.replace('__ID__', currentId);
                    deleteForm.action = url;
                    deleteForm.requestSubmit();
                });

                // Toast logic: show success/error messages as small toast
                const toast = document.getElementById('miniToast');
                const toastBody = document.getElementById('miniToastBody');
                const successMsg = @json(session('success'));
                const errorMsg = @json(session('error'));
                if(successMsg || errorMsg){
                    toastBody.textContent = successMsg || errorMsg;
                    toastBody.style.background = successMsg ? '#1e7e34' : '#c10b26';
                    toast.style.display = 'block';
                    setTimeout(() => { toast.style.display = 'none'; }, 3500);
                }
            });
            </script>
        </div>
    </div>
</div>
@endsection
