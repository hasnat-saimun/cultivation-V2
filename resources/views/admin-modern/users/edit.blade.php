@extends('admin-modern.layouts.app')

@section('title', 'Edit User')

@section('content')
    <x-admin-modern.page-header
        title="Edit User"
        subtitle="Modern parallel wrapper for user update using existing ERP submit flow"
        :breadcrumb="['Home', 'Users', 'Edit User']"
    />

    <x-admin-modern.table-shell title="User Register Form">
        <div class="am-btn-row" style="margin-bottom: 0.7rem;">
            <a href="{{ route('adminModernUsersIndex') }}" class="am-btn-outline">Registered List</a>
            @if(isset($user))
                <a href="{{ route('adminModernUsersCreate') }}" class="am-btn-primary">Add New</a>
            @endif
        </div>

        @error('insLogo')
            <div class="am-flash is-error" role="alert">
                <span>{{ $message }}</span>
            </div>
        @enderror

        <form action="{{ route('saveUser') }}" method="POST" enctype="multipart/form-data">
            @csrf
            @if(isset($user))
                <input type="hidden" name="userId" value="{{ $user->id }}">
            @endif

            @php
                $showAccessBox = false;
                $assignedClasses = [];
                $assignedSubjects = [];
                $assignedSections = [];
                if(isset($user) && $user->userType == 1) {
                    $showAccessBox = true;
                    $assignedClasses = $user->access_class_array ?? [];
                    $assignedSubjects = $user->access_subject_array ?? [];
                    $assignedSections = $user->access_section_array ?? [];
                }
            @endphp

            <div class="am-grid am-grid-2" style="margin-bottom: 0.7rem;">
                <div>
                    <label for="adminName" style="display:block; font-weight:600; margin-bottom:0.35rem;">Admin Name</label>
                    <input type="text" name="adminName" id="adminName" placeholder="Enter the admin name" required value="{{ isset($user) ? $user->adminName : '' }}" style="width:100%; border:1px solid var(--am-border); border-radius:10px; padding:0.55rem 0.7rem; background:var(--am-surface); color:var(--am-text);">
                </div>
                <div>
                    <label for="userName" style="display:block; font-weight:600; margin-bottom:0.35rem;">User Name</label>
                    <input type="text" name="userName" id="userName" placeholder="Enter the user name" required value="{{ isset($user) ? $user->adminUser : '' }}" {{ isset($user) ? 'readonly' : '' }} style="width:100%; border:1px solid var(--am-border); border-radius:10px; padding:0.55rem 0.7rem; background:var(--am-surface); color:var(--am-text);">
                </div>
                <div>
                    <label for="userMobile" style="display:block; font-weight:600; margin-bottom:0.35rem;">User Mobile</label>
                    <input type="text" name="userMobile" id="userMobile" placeholder="Enter user mobile number" required value="{{ isset($user) ? $user->adminMobile : '' }}" style="width:100%; border:1px solid var(--am-border); border-radius:10px; padding:0.55rem 0.7rem; background:var(--am-surface); color:var(--am-text);">
                </div>
                <div>
                    <label for="userMail" style="display:block; font-weight:600; margin-bottom:0.35rem;">User Email</label>
                    <input type="text" name="userMail" id="userMail" placeholder="Enter user email address" required value="{{ isset($user) ? $user->adminMail : '' }}" {{ isset($user) ? 'readonly' : '' }} style="width:100%; border:1px solid var(--am-border); border-radius:10px; padding:0.55rem 0.7rem; background:var(--am-surface); color:var(--am-text);">
                </div>
                <div>
                    @php $isDemo = strpos(config('app.url'), 'demoadmin.cultivationapp.com') !== false; @endphp
                    <label for="userType" style="display:block; font-weight:600; margin-bottom:0.35rem;">User Type</label>
                    <select id="userType" onchange="userSelect()" name="userType" required style="width:100%; border:1px solid var(--am-border); border-radius:10px; padding:0.55rem 0.7rem; background:var(--am-surface); color:var(--am-text);">
                        <option value="">Select</option>
                        <option value="1" {{ (isset($user) && $user->userType == 1) ? 'selected' : '' }}>Teacher Admin</option>
                        <option value="2" {{ (isset($user) && $user->userType == 2) ? 'selected' : '' }}>Cash Admin</option>
                        @if(!$isDemo)
                            <option value="3" {{ (isset($user) && $user->userType == 3) ? 'selected' : '' }}>General Admin</option>
                        @endif
                    </select>
                </div>
                <div>
                    <label for="pass" style="display:block; font-weight:600; margin-bottom:0.35rem;">User Password</label>
                    <input type="password" name="pass" id="pass" placeholder="{{ isset($user) ? 'Leave this field blank if you don\'t need to change password' : 'Enter the password' }}" {{ isset($user) ? '' : 'required' }} style="width:100%; border:1px solid var(--am-border); border-radius:10px; padding:0.55rem 0.7rem; background:var(--am-surface); color:var(--am-text);">
                    @if(isset($user))
                        <small style="display:block; margin-top:0.35rem; color:var(--am-muted);">Leave this field blank if you don't need to change password.</small>
                    @endif
                </div>
                @if(!isset($user))
                    <div>
                        <label for="confirmPass" style="display:block; font-weight:600; margin-bottom:0.35rem;">Confirm Password</label>
                        <input type="password" name="confirmPass" id="confirmPass" placeholder="Enter the confirm password" required style="width:100%; border:1px solid var(--am-border); border-radius:10px; padding:0.55rem 0.7rem; background:var(--am-surface); color:var(--am-text);">
                    </div>
                @endif
            </div>

            <div id="accessBox" class="row p-3 {{ (old('userType') == 1 || (isset($user) && $user->userType == 1)) ? '' : 'd-none' }}" style="border:1px solid var(--am-border); border-radius:10px; margin-bottom:0.9rem;">
                <div class="col-lg-6 col-12 mb-3">
                    <div class="card shadow-sm">
                        <div class="card-body p-3">
                            <h6 class="mb-2">Assign (Marks Entry)</h6>
                            <p class="small text-muted mb-2">Assign classes, sections and subjects a teacher may enter marks for.</p>
                            <div class="mb-2">
                                <select id="assign_class_select" class="form-select">
                                    <option value="">Select class</option>
                                    @foreach($classList as $cls)
                                        <option value="{{ $cls->id }}">{{ $cls->className }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-2">
                                <select id="assign_section_select" class="form-select mt-2 d-none">
                                    <option value="">Select section</option>
                                    <option value="all">All Sections</option>
                                    <option value="none">No Section (show all class data)</option>
                                    @if(isset($sectionList))
                                        @foreach($sectionList as $sec)
                                            <option value="{{ $sec->id }}" data-section-name="{{ $sec->section }}">{{ $sec->section }}</option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>
                            <div class="mb-2">
                                <select id="assign_group_select" class="form-select mt-2 d-none">
                                    <option value="">All Groups</option>
                                    @if(isset($groupList))
                                        @foreach($groupList as $grp)
                                            <option value="{{ $grp->id }}">{{ $grp->departmentName }}</option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>
                            <div class="mb-2">
                                <select id="assign_subject_select" class="form-select mt-2 d-none">
                                    <option value="">Select subject</option>
                                    @if($subjectList)
                                        @foreach($subjectList as $sub)
                                            <option value="{{ $sub->id }}" data-assign-class="{{ $sub->assign_class ?? '' }}">{{ $sub->subjectName }} ({{ $sub->subjectType }})</option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>
                            <div class="mb-2">
                                <button type="button" id="assign_btn" class="btn btn-success btn-sm d-none">Assign</button>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-sm table-striped mt-2" id="assign_table">
                                    <thead>
                                        <tr><th>Class</th><th>Section</th><th>Group (Optional)</th><th>Subject</th><th></th></tr>
                                    </thead>
                                    <tbody>
                                        @php
                                            $rows = [];
                                            if(isset($user) && $user->userType == 1){
                                                $comps = \App\Models\TeacherClassSubject::where('teacher_id', $user->id)->get();
                                                foreach($comps as $c){
                                                    $rows[] = ['cid'=>$c->class_id,'sid'=>($c->section_id === null ? null : $c->section_id),'gid'=>($c->group_id === null ? null : $c->group_id),'subid'=>($c->subject_id === null ? null : $c->subject_id)];
                                                }
                                                if(empty($rows)){
                                                    foreach($assignedSubjects as $subid) {
                                                        $subModel = optional(collect($subjectList)->firstWhere('id', $subid));
                                                        $cid = $subModel && $subModel->assign_class ? $subModel->assign_class : ($assignedClasses[0] ?? null);
                                                        $sid = $assignedSections[0] ?? null;
                                                        if(!$cid) continue;
                                                        $rows[] = ['cid'=>$cid,'sid'=>$sid,'gid'=>null,'subid'=>$subid];
                                                    }
                                                    foreach($assignedClasses as $cid) {
                                                        $found = false;
                                                        foreach($rows as $r) { if($r['cid'] == $cid) { $found = true; break; } }
                                                        if(!$found) {
                                                            $rows[] = ['cid'=>$cid,'sid'=>$assignedSections[0] ?? null,'gid'=>null,'subid'=>null];
                                                        }
                                                    }
                                                }
                                            } else {
                                                foreach($assignedSubjects as $subid) {
                                                    $subModel = optional(collect($subjectList)->firstWhere('id', $subid));
                                                    $cid = $subModel && $subModel->assign_class ? $subModel->assign_class : ($assignedClasses[0] ?? null);
                                                    $sid = $assignedSections[0] ?? null;
                                                    if(!$cid) continue;
                                                    $rows[] = ['cid'=>$cid,'sid'=>$sid,'gid'=>null,'subid'=>$subid];
                                                }
                                                foreach($assignedClasses as $cid) {
                                                    $found = false;
                                                    foreach($rows as $r) { if($r['cid'] == $cid) { $found = true; break; } }
                                                    if(!$found) {
                                                        $rows[] = ['cid'=>$cid,'sid'=>$assignedSections[0] ?? null,'gid'=>null,'subid'=>null];
                                                    }
                                                }
                                            }
                                        @endphp
                                        @foreach($rows as $r)
                                            @php
                                                $cid = $r['cid'];
                                                $sid = $r['sid'];
                                                $gid = $r['gid'] ?? null;
                                                $subid = $r['subid'];
                                                $clsText = optional(collect($classList)->firstWhere('id', $cid))->className ?? ('Class #'.$cid);
                                                $secText = $sid ? ( ($sid==='all') ? 'All Sections' : ( ($sid==='none') ? 'No Section (show all class data)' : (optional(collect($sectionList)->firstWhere('id',$sid))->section ?? ('Section #'.$sid)) ) ) : '';
                                                $grpText = $gid ? (optional(collect($groupList ?? collect())->firstWhere('id',$gid))->departmentName ?? ('Group #'.$gid)) : 'All Groups';
                                                $subText = $subid ? (optional(collect($subjectList)->firstWhere('id',$subid))->subjectName ?? ('Subject #'.$subid)) : '';
                                                $key = $cid.'-'.($sid ?? '').'-'.($gid ?? '').'-'.($subid ?? '');
                                            @endphp
                                            <tr data-key="{{ $key }}">
                                                <td>{{ $clsText }}</td>
                                                <td>{{ $secText }}</td>
                                                <td>{{ $grpText }}</td>
                                                <td>{{ $subText }}</td>
                                                <td><button type="button" class="btn btn-sm btn-danger remove-assign">Remove</button></td>
                                                <input type="hidden" name="className[]" value="{{ $cid }}">
                                                <input type="hidden" name="section[]" value="{{ $sid }}">
                                                <input type="hidden" name="optionalGroup[]" value="{{ $gid }}">
                                                <input type="hidden" name="subject[]" value="{{ $subid }}">
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6 col-12 mb-3">
                    <div class="card shadow-sm">
                        <div class="card-body p-3">
                            <h6 class="mb-2">Primary Class / Section (Attendance)</h6>
                            <p class="small text-muted mb-2">Primary class/section is used by a class-teacher when taking daily attendance.</p>
                            <div class="mb-3">
                                <label class="form-label">Primary Class</label>
                                <select name="primaryClass" class="form-select">
                                    <option value="">-- None --</option>
                                    @foreach($classList as $cls)
                                        <option value="{{ $cls->id }}" {{ (old('primaryClass') == $cls->id || (isset($user) && $user->primary_class_id == $cls->id)) ? 'selected' : '' }}>{{ $cls->className }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Primary Section</label>
                                <select name="primarySection" class="form-select">
                                    <option value="">-- None --</option>
                                    @if(isset($sectionList))
                                        @foreach($sectionList as $sec)
                                            <option value="{{ $sec->id }}" {{ (old('primarySection') == $sec->id || (isset($user) && $user->primary_section_id == $sec->id)) ? 'selected' : '' }}>{{ $sec->section }}</option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>
                            <div class="small text-muted">Selecting a primary class does not grant marks-entry rights — use the Assign panel for that.</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="am-btn-row">
                <button type="submit" class="am-btn-primary" style="border:0; cursor:pointer;">Save</button>
            </div>
        </form>
    </x-admin-modern.table-shell>

    <script>
        function userSelect() {
            var str = document.getElementById('userType').value;
            if(str == "1") {
                $("#accessBox").removeClass("d-none");
            } else {
                $("#accessBox").addClass("d-none");
            }
        }

        function classSelect() {
            console.log('hasnat')
            var str   = document.getElementById('classType').value;
            if(str == "") {
                $("#subjectBox").addClass("d-none");
                document.getElementById("subjectBox").classList.add = "d-none";
            }
            if(str == 1) {
                $("#subjectBox").removeClass("d-none");
            }else{
                $("#subjectBox").addClass("d-none");
                document.getElementById("subjectBox").classList.add = "d-none";
            }
        }

        // Assignment panel logic
        document.addEventListener('DOMContentLoaded', function(){
            const classSelect = document.getElementById('assign_class_select');
            const sectionSelect = document.getElementById('assign_section_select');
            const groupSelect = document.getElementById('assign_group_select');
            const subjectSelect = document.getElementById('assign_subject_select');
            const assignBtn = document.getElementById('assign_btn');
            const assignTableBody = document.querySelector('#assign_table tbody');

            // helper to show/hide
            function show(el){ el.classList.remove('d-none'); }
            function hide(el){ el.classList.add('d-none'); }

            classSelect && classSelect.addEventListener('change', function(){
                if(this.value){
                    show(sectionSelect);
                    // reset section and subject
                    sectionSelect.value = '';
                    groupSelect.value = '';
                    subjectSelect.value = '';
                    hide(groupSelect);
                    hide(subjectSelect);
                    hide(assignBtn);
                } else {
                    hide(sectionSelect);
                    hide(groupSelect);
                    hide(subjectSelect);
                    hide(assignBtn);
                }
            });

            sectionSelect && sectionSelect.addEventListener('change', function(){
                const cls = classSelect.value;
                if(this.value && cls){
                    show(groupSelect);
                    // show all configured subjects for teacher assignment
                    for(const opt of subjectSelect.options){
                        if(!opt.value) continue;
                        opt.style.display = '';
                    }
                    subjectSelect.value = '';
                    show(subjectSelect);
                    hide(assignBtn);
                } else {
                    hide(groupSelect);
                    hide(subjectSelect);
                    hide(assignBtn);
                }
            });

            subjectSelect && subjectSelect.addEventListener('change', function(){
                if(this.value) show(assignBtn); else hide(assignBtn);
            });

            // store assignments to prevent duplicates
            const assignments = new Set();

            // Initialize assignments set from any existing rows rendered on the server
            document.querySelectorAll('#assign_table tbody tr').forEach(function(tr){
                const cls = tr.querySelector('input[name="className[]"]')?.value || '';
                const sec = tr.querySelector('input[name="section[]"]')?.value || '';
                const grp = tr.querySelector('input[name="optionalGroup[]"]')?.value || '';
                const sub = tr.querySelector('input[name="subject[]"]')?.value || '';
                if(cls) assignments.add([cls,sec,grp,sub].join('-'));
            });

            // Delegate remove button clicks so server-rendered rows are removable too
            assignTableBody && assignTableBody.addEventListener('click', function(e){
                const btn = e.target.closest && e.target.closest('.remove-assign');
                if(!btn) return;
                const tr = btn.closest('tr');
                if(!tr) return;
                // determine the assignment key from hidden inputs inside the row
                const cls = tr.querySelector('input[name="className[]"]')?.value || '';
                const sec = tr.querySelector('input[name="section[]"]')?.value || '';
                const grp = tr.querySelector('input[name="optionalGroup[]"]')?.value || '';
                const sub = tr.querySelector('input[name="subject[]"]')?.value || '';
                const key = [cls,sec,grp,sub].join('-');
                if(assignments.has(key)) assignments.delete(key);
                tr.remove();
            });

            assignBtn && assignBtn.addEventListener('click', function(){
                const clsId = classSelect.value; const secId = sectionSelect.value; const grpId = groupSelect.value; const subId = subjectSelect.value;
                if(!clsId || !secId || !subId) return showGlobalFlash('Please select class, section and subject.','danger');
                const key = [clsId,secId,grpId,subId].join('-');
                if(assignments.has(key)) return showGlobalFlash('This assignment already added','warning');
                assignments.add(key);
                const clsText = classSelect.options[classSelect.selectedIndex].text;
                let secText = sectionSelect.options[sectionSelect.selectedIndex].text;
                if(secId === 'all') secText = 'All Sections';
                if(secId === 'none') secText = 'No Section (show all class data)';
                const grpText = grpId ? groupSelect.options[groupSelect.selectedIndex].text : 'All Groups';
                const subText = subjectSelect.options[subjectSelect.selectedIndex].text;

                const tr = document.createElement('tr');
                tr.innerHTML = `<td>${clsText}</td><td>${secText}</td><td>${grpText}</td><td>${subText}</td><td><button type="button" class="btn btn-sm btn-danger remove-assign">Remove</button></td>`;
                assignTableBody.appendChild(tr);

                // hidden inputs for controller sync
                const hiddenCls = document.createElement('input'); hiddenCls.type='hidden'; hiddenCls.name='className[]'; hiddenCls.value=clsId; hiddenCls.dataset.key=key; tr.appendChild(hiddenCls);
                const hiddenSec = document.createElement('input'); hiddenSec.type='hidden'; hiddenSec.name='section[]'; hiddenSec.value=secId; hiddenSec.dataset.key=key; tr.appendChild(hiddenSec);
                const hiddenGrp = document.createElement('input'); hiddenGrp.type='hidden'; hiddenGrp.name='optionalGroup[]'; hiddenGrp.value=grpId; hiddenGrp.dataset.key=key; tr.appendChild(hiddenGrp);
                const hiddenSub = document.createElement('input'); hiddenSub.type='hidden'; hiddenSub.name='subject[]'; hiddenSub.value=subId; hiddenSub.dataset.key=key; tr.appendChild(hiddenSub);

                // remove handler
                tr.querySelector('.remove-assign').addEventListener('click', function(){
                    assignments.delete(key);
                    tr.remove();
                });

                // reset selects for next assignment
                sectionSelect.value=''; groupSelect.value=''; subjectSelect.value=''; hide(subjectSelect); hide(assignBtn);
            });

            // Existing assignments are rendered server-side in the table; no JS prepopulate needed.
        });
    </script>
@endsection
