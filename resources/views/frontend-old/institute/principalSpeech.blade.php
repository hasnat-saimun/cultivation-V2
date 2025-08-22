@extends('frontend.include')
@section('fronttitle')
Syllabus
@endsection
@section('frontcontent')
<style>
body {
  background-image: url("/public/frontend/assets/images/bg/bg.png");
}
    .hedingAbout{
        text-align:center;
        margin-bottom:50px;
        
    }
   .principlaimg{
        width:100%;
        height:400px;
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
    .principalname{
        margin:auto;
        font-size:22px;
        font-weight:bold;
        text-align:center;
        padding-top:20px;
    }
</style>

 <section>
    <div class="container">
        <div class="row">
            <div class="col-md-12 text-center con-title">
                <h1 class="wow fadeInLeft animated mt-2 mb-3" data-wow-delay=".60s">Principal <span>Speech</span></h1>
           </div>
        </div>
        <div class="row align-items-center">
            @if(!empty($pSpeech))
            @php
                if(!empty($cultivation)):
                    $insName = $cultivation->institueName;
                else:
                    $insName = "Jahanara-Ayub Academy";
                endif;
            @endphp
            <div class="col-8 col-md-3 text-center mx-auto">
                @if(!empty($principal))
                <div class="card">
                    <img  class="w-100 wow bounce animated" data-wow-delay="1s" src="{{ asset('public/upload/image/teacher').'/'.$principal->avatar }}"/>
                    <div class="card-footer">
                        <p>{{ $principal->firstName ." ". $principal->lastName  }}</p>
                        <p>@if($principal->designation==1) Principal @elseif($principal->designation==2) Principal(Incharge) @endif<br> {{ $insName }} </p>
                    </div>
                </div>
                @else
                <div class="card">
                    <img  class="w-100 wow bounce animated" data-wow-delay="1s" src="{{ asset('/public/avatar.png') }}" />
                    <div class="card-footer">
                        <p>Engr. Abu Yousuf</p>
                        <p>Principal<br> Jahanar-Ayub Academy</p>
                    </div>
                </div>
                @endif
           </div>
             <div class="col-12 col-md-9">
                @if(!empty($pSpeech))
                <h5 class="mt-0">{{$pSpeech->importantSpeech}}</h5>
                <p class="mt-3 wow fadeIn animated" data-wow-delay=".60s">
                    {{$pSpeech->generalSpeech}}
                </p>
                @else
                <h5 class="mt-0">We want to make good students as well as good people.</h5>
                <p class="mt-3 wow fadeIn animated" data-wow-delay=".60s">
                Life is not always smooth sailing; it’s more like a roller coaster with its ups and downs. But remember, it’s the bumps and twists that make the ride exciting and memorable. When you face challenges or setbacks, it’s easy to feel discouraged. However, it’s during these tough times that your true strength shines through. It’s the moments when you refuse to give up that define your character and set the stage for your success.
                </p>
                @endif
            </div>     
        @endif            
        </div>

    </div>
</section>

   


@endsection