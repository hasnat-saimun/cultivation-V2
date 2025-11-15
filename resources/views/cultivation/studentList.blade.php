@extends('cultivation.include')
@section('backTitle')
Student List
@endsection
@section('backIndex')
                <!-- Social Media Start Here -->
                <div class="row gutters-20 mt-4">
                    <div class="col-12 col-md-12 mx-auto">
                        <div class="card card-default">
                            <div class="card-header bg-light">
                                <a href="{{route('admitStudent')}}" class="btn btn-success">New Admission</a>
                            </div>
                            <div class="card-header bg-light">
                                <h3>Student List</h3>
                            </div>
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
                                <table id="myTable" class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Name</th>
                                            <th>Session</th>
                                            <th>Class</th>
                                            <th>Department</th>
                                            <th>Section</th>
                                            <th>Mobile</th>
                                            <th>ID Card</th>
                                            <th>Testimonial</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @if(!empty($studentData))
                                        @foreach($studentData as $std)
                                        @php 
                                            $sessionDetails = \App\Models\sessionManage::all();
                                            $sessionData  = \App\Models\sessionManage::find($std->sessName);
                                            $classData  = \App\Models\classManage::find($std->className);
                                            $sectionData  = \App\Models\sectionManage::find($std->sectionName);
                                            $departmentData  = \App\Models\Department::find($std->departmentName);
                                        @endphp
                                        <tr>
                                            <td>{{ $std->stdId }}</td>
                                            <td>{{ $std->fullName." ".$std->sureName }}</td>
                                            @if(!empty($sessionData))
                                            <td>{{$sessionData->session}}</td>
                                            @else
                                            <td>-</td>
                                            @endif
                                            @if(!empty($classData))
                                            <td>{{$classData->className}}</td>
                                            @else
                                            <td>-</td>
                                            @endif
                                            @if(!empty($departmentData))
                                            <td>{{$departmentData->departmentName}}</td>
                                            @else
                                            <td>-</td>
                                            @endif
                                            @if(!empty($sectionData))
                                            <td>{{$sectionData->section}}</td>
                                            @else
                                            <td>-</td>
                                            @endif
                                            <td>{{ $std->phone }}</td>
                                            <td class="text-center"><a href="{{ route('stdIdCard',['stdId'=>$std->id]) }}"><i class="fa-solid fa-id-card mx-2" style="color:#19761f;"></i></a></td>
                                            @php 
                                                $existingT = \App\Models\Testimonial::where('admission_id', $std->id)->latest('id')->first();
                                                $eligible = false;
                                                if(!empty($classData) && !empty($classData->className)){
                                                    $cn = strtolower(trim($classData->className));
                                                    $eligible = ($cn === 'ten' || $cn === 'twelve' || $cn === '10' || $cn === '12' || strpos($cn,'ten') !== false || strpos($cn,'twelve') !== false);
                                                }
                                            @endphp
                                            <td>
                                                @if(!$eligible)
                                                    <span class="badge bg-secondary">Not Eligible</span>
                                                @else
                                                    @if($existingT)
                                                        <span class="badge bg-success">Created</span>
                                                    @else
                                                        <span class="badge bg-warning text-dark">Not Created</span>
                                                    @endif
                                                @endif
                                            </td>
                                            <td>
                                                <a href="{{ route('viewAdmission',['stdId'=>$std->id]) }}" title="View Profile"><i class="fa-solid fa-eye mx-2" style="color:rgb(35 170 211);"></i></a>
                                                <a href="{{ route('editStudent',['stdId'=>$std->id]) }}" title="Edit Student"><i class="fa-solid fa-pen-to-square mx-2" style="color: #4125b1;"></i></a>
                                                <a href="{{ route('delStudent',['stdId'=>$std->id]) }}" onclick="return confirm('Are you sure you want to delete this item?');" title="Delete Student"><i class="fa-solid fa-trash mx-2" style="color: #c10b26;"></i></a>
                                                @if($eligible)
                                                    @if($existingT)
                                                        <a href="{{ route('testimonials.show', $existingT->id) }}" title="View Testimonial"><i class="fa-solid fa-certificate mx-2" style="color:#2f6fed;"></i></a>
                                                        <a href="{{ route('testimonials.print', $existingT->id) }}" title="Print Testimonial" target="_blank"><i class="fa-solid fa-print mx-2" style="color:#168c6c;"></i></a>
                                                    @else
                                                        <a href="{{ route('testimonials.create', ['admission' => $std->id]) }}" title="Create Testimonial"><i class="fa-solid fa-certificate mx-2" style="color: #168c6c;"></i></a>
                                                    @endif
                                                @else
                                                    <i class="fa-solid fa-circle-info mx-2" title="Testimonial available only for Class Ten & Twelve" style="color:#9aa0a6;"></i>
                                                @endif
                                            </td>
                                        </tr>
                                        @endforeach
                                        @else
                                        <tr>
                                            <td>SBC02</td>
                                            <td>Rasek Khondokar</td>
                                            <td>2023-2024</td>
                                            <td>Science</td>
                                            <td>01234567890</td>
                                            <td>Science</td>
                                            <td>01234567890</td>
                                            <td>Edit</td>
                                        </tr>
                                        @endif
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
@endsection

@push('styles')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap4.min.css">
@endpush

@push('scripts')
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap4.min.js"></script>
<script>
    (function(){
        function initDT(){
            if (!window.jQuery || !jQuery.fn || !jQuery.fn.DataTable) { return false; }
            var $ = jQuery;
            var $tbl = $('#myTable');
            if($tbl.length && !$tbl.hasClass('dt-initialized')){
                $tbl.addClass('dt-initialized').DataTable({
                    pageLength: 25,
                    order: [[0,'asc']],
                    lengthMenu: [10,25,50,100],
                    language: { search: "Search:", lengthMenu: "Show _MENU_ entries" },
                    responsive: true
                });
            }
            return true;
        }
        // try immediately, then fall back to DOM ready
        if(!initDT()){
            document.addEventListener('DOMContentLoaded', initDT);
        }
    })();
</script>
@endpush