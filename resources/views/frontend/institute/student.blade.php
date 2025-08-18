@extends('frontend.include')
@section('fronttitle')
Student
@endsection
@section('frontcontent')
<style>
body {
    background-image: url("/public/frontend/assets/images/bg/bg.png");
}
#myTable th{
    text-align:center;
}

</style>

 <section class="mt-4">
    <div class="container">
        <div class="row">
            <div class="col-md-12 text-center con-title">
                <h2 class="wow fadeInLeft animated" data-wow-delay=".60s"> Our <span>Student</span></h2>
           </div>
        </div>
            <!-- On tables -->
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        Student List
                    </div>
                    <div class="card-body">
                <table id="myTable" class="display border" >
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Session</th>
                            <th>Class</th>
                            <th>Department</th>
                            <th>Section</th>
                            <th>Mobile</th>
                            <th>Photo</th>
                            
                        </tr>
                    </thead>
                    <tbody>
                        @if(!empty($Datakey))
                        @foreach($Datakey as $std)
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
                             <td> <img src="{{ asset('public/upload/image/student').'/'.$std->avatar }}" style="width:100px; height:100px;"/></td>
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
    </div>
</section>

   


@endsection