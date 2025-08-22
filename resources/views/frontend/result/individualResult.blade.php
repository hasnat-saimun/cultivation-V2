@extends('frontend.include')
@section('fronttitle')
Syllabus
@endsection
@section('frontcontent')
<section class="container mt-4">
    <div class="row">
        <div class="col-10 mx-auto text-center con-title mt-4">
            <h2 class="hedingAbout wow fadeInLeft animated my-4" data-wow-delay=".60s">Individual  <span>Result</span> </h2>
        </div>
    </div>
    <div calss="row">
        <div class="col-10 mx-auto my-4">
            <div class="card">
                <div class="card-header">
                    Syllabus 
                </div>
                <div class="card-body">
                    <!-- On tables -->
                    <table id="myTable" class="display border" >
                        <thead>
                            <tr>
                                <th>SL</th>
                                <th>Semister</th>
                                <th>Department</th>
                                <th>Session</th>
                                <th>Publish Date</th>
                                <th>View</th>
                            </tr> 
                        </thead>
                        <tbody>
                            <tr>
                                <td>1</td>
                                <td>3rd Semister</td>
                                <td>Honours</td>
                                <td>2024-25</td>
                                <td>25 Jan 2025</td>
                                <td><a data-fancybox data-type="iframe" href="#" target="_blank"> <i class="fa fa-eye" style="color: green;"></i> </a></td>
                            </tr>          
                        </tbody>
                    </table>                         
                </div>
            </div>
        </div>
    </div>
</section>
@endsection