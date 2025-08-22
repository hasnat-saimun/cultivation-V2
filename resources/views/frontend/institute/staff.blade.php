@extends('frontend.include')
@section('fronttitle')
Syllabus
@endsection
@section('frontcontent')
<style>
#myTable th,td{
        text-align:left !important;
        vertical-align:center;
}
#myTable th{
    font-weight:bold;
}
</style>

 <section class="container mt-4">
    <div class="row">
        <div class="col-md-12 text-center con-title mt-4">
            <h2 class="hedingAbout wow fadeInLeft animated my-4" data-wow-delay=".60s">Let's have looks of our <span> Staff Panel</span> </h2>
        </div>
    </div>
    <div class="row align-items-center">
         <div class="col-10 mx-auto">
                @if($Datakey->count()>0) 
                @foreach($Datakey as $data)
                <table class="table table-bordered my-4">
                    <td style="width:10%"><img  class="w-100 img-thumbnail" src="{{ asset('public/upload/image/staff').'/'.$data->avatar }}"></td>
                    <td style="width:90%">
                        <table class="table table-bordered">
                            <tr>
                                <th style="width:15%">Name</th>
                                <td colspan="3">: {{$data->firstName}} {{$data->lastName}}</td>
                            </tr>
                            <tr>
                                <th style="width:15%">Designation</th>
                                <td colspan="3">: {{$data->designation}}</td>
                            </tr>
                            <tr>
                                <th style="width:15%">Mobile</th>
                                <td style="width:35%">: {{$data->mobile}}</td>
                                <th style="width:15%">Email</th>
                                <td style="width:35%">: {{$data->email}}</td>
                            </tr>
                        </table>
                    </td>
                </table>

                @endforeach
                @else
                    <table class="table table-bordered my-4">
                        <tr class="alert alert-info p-4">
                            <td colspan="6">Sorry! No data found</td>
                        </tr>
                    </table>
                @endif  
         </div>
    </div>
</section>
@endsection