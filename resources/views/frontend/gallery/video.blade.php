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
        <div class="col-md-12 text-center con-title my-4">
            <h2 class="hedingAbout wow fadeInLeft animated" data-wow-delay=".60s">Memorable <span> Moment (Video)</span> </h2>
        </div>
    </div>
    <div class="row align-items-center">
        @if($Datakey->count()>0) 
         <div class="col-10 mx-auto my-4">
            @foreach($Datakey as $data)
                <div class="col-md-4 mb-4  " >
                    <a class="wow fadeIn animated " data-wow-delay=".60s" href="{{ asset('/public/upload/image/photogallery/').'/'.$data->avatar}}" data-lightbox="mygallery" data-toggle="modal" data-target="#">
                        <img data-bs-toggle="modal" data-bs-target="#staticBackdrop" src="{{ asset('/public/upload/image/photogallery/').'/'.$data->avatar}}" alt="" class="w-100 img-rounded"/>
                    </a>
                </div>
            @endforeach
         </div>
        @else
        <div class="alert alert-info col-8 mx-auto">Sorry! No content available right now</div>
        @endif
    </div>
</section>
@endsection