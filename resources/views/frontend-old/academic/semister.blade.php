@extends('frontend.include')
@section('fronttitle')
Syllabus
@endsection
@section('frontcontent')
<section class="container">
    <div class="row">
        <div class="col-md-12 text-center con-title">
            <h2 class="wow fadeInLeft animated" data-wow-delay=".60s">Semister</h2>
        </div>
    </div>
    <div calss="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    Syllabus 
                </div>
                <div class="card-body">
                    <!-- On tables -->
                    <table id="myTable" class="display border" >
                        <thead>
                            <tr>
                                <th>Semister</th>
                                <th>Class</th>
                                <th>Department</th>
                                <th>Session</th>
                                <th>Publish Date</th>
                                <th>View</th>
                            </tr>
                        </thead>
                        <tbody>
                        @foreach($Datakey as $data)
                            <tr>
                                <td>{{$data->title}}</td>
                                @php 
                                    $itemClass      = \App\Models\classManage::find($data->assignClass);
                                    $itemDepartment = \App\Models\Department::find($data->assignDepartment);
                                    $itemSession    = \App\Models\sessionManage::find($data->assignSession);
                                @endphp
                                <td>{{ $itemClass->className }}</td>
                                <td>{{ $itemDepartment->departmentName }}</td>
                                <td>{{ $itemSession->session }}</td>
                                <td>{{$data->created_at}}</td>
                                <td><a data-fancybox data-type="iframe" href="{{asset('/')}}/public/upload/image/cultivation/semisterPlan/{{ $data->attachment }}" target="_blank"> <i class="fa fa-eye" style="color: green;"></i> </a></td>
                            </tr>  
                        @endforeach          
                        </tbody>
                    </table>                         
                </div>
            </div>
        </div>
    </div>
</section>
@endsection