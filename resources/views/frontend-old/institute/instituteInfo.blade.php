@extends('frontend.include')
@section('fronttitle')
Syllabus
@endsection
@section('frontcontent')
<style>
    .hedingAbout{
        text-align:center;
        margin-bottom:50px;
        
    }
   .principla img{
        width:100%;
        height:400px;
        border-radius:100%;
        text-align:center;
        margin-top:30px; 
        box-shadow: 0px 8px 15px rgba(0, 0, 0, 0.1);
        
    }
    .principalspace{
        width:100%;
        height:auto;
        text-align:justify;
        margin:auto;
        font-family:Raleway;
        font-size:15px;
        padding-top:30px;
        padding-bottom:30px;
        line-height:29px;
        
    }
</style>

 <section class="my-4">
    <div class="container">
        @if(!empty($Datakey)) 
        @foreach($Datakey as $data)
        <div class="row mb-1">
            <div class="col-md-12 text-center con-title">
                <h2  class=" wow fadeInLeft animated" data-wow-delay=".60s">{{$data->insHeadline}}</h2>
           </div>
        </div>

        <div class="row align-items-center mt-0">
             <div class="col-md-8 col-12 mx-auto">
                <img  class="w-100 wow fadeIn animated" data-wow-delay="1s" src="{{ asset('/public/upload/image/cultivation/').'/'.$data->heroImg}}">
           </div>
             <div class="col-md-10 col-12 mx-auto">
                <h4 class="mt-4">About Us</h4>
                 <p class="wow fadeIn animated" data-wow-delay="1s" >  {{$data->insDetails}}
                 </p>
                 <h4>Establish Date</h4>
                 <p class="wow fadeIn animated" data-wow-delay="1s" >  
                 {{$data->establishDate}}
                 <h4>Total Area</h4>
                 </p><p class="wow fadeIn animated" data-wow-delay="1s" >  
                 {{$data->landSize}}
                 <h4>Our Mission</h4>
                 </p><p class="wow fadeIn animated" data-wow-delay="1s" > 
                 {{$data->mission}}
                 <h4>Our Vision</h4>
                 </p><p class="wow fadeIn animated" data-wow-delay="1s" > 
                 {{$data->vision}}
                 
                 </p>
            </div>    
        </div>
        @endforeach
        @endif
    </div>
</section>

   


@endsection