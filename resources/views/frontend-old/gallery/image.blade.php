@extends('frontend.include')
@section('fronttitle')
Syllabus
@endsection
@section('frontcontent')
<section>
    <div class="container" style="margin-top:50px;">
        <div class="row">
            <div class="col-md-12 text-center con-title">
                <h2 class=" wow fadeInLeft animated" data-wow-delay=".60s">Photo <span>Galery</span></h2>
                <p class=" wow fadeInLeft animated" data-wow-delay=".60s">Fusce id sem at ligula laoreet hendrerit venenatis sed purus. Ut pellentesque maximus lacus, nec pharetra augue.</p>
            </div>
        </div>
        <div class="row">
        @if(!empty($Datakey)) 
        @foreach($Datakey as $data)
            <div class="col-md-4 mb-4  " >
                <a class="wow fadeIn animated " data-wow-delay=".60s" href="{{ asset('/public/upload/image/photogallery/').'/'.$data->avatar}}" data-lightbox="mygallery" data-toggle="modal" data-target="#">
                    <img data-bs-toggle="modal" data-bs-target="#staticBackdrop" src="{{ asset('/public/upload/image/photogallery/').'/'.$data->avatar}}" alt="" class="w-100 img-rounded"/>
                </a>
            </div>
            @endforeach
             @endif
        </div>
    </div>
</section>
@endsection