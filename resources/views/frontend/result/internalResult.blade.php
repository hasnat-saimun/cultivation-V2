@extends('frontend.include')
@section('fronttitle')
Syllabus
@endsection
@section('frontcontent')
<section class="container">
    <div class="row">
        <div class="col-md-12 text-center con-title">
            <h2 class="wow fadeInLeft animated" data-wow-delay=".60s">Internal Result</h2>
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