@extends('frontend.include')
@section('fronttitle')
video
@endsection
@section('frontcontent')
<style>
    .videobg{
        width:100%;
        height:auto;
        position:relative;
    }
    .text-overlays{
        width:100%;
        height:auto;
        position:absolute;
        top:50px;
        text-align:center;
        font-size:32px;
        font-weight:700;
        
    }
       .text-overlays span{
        font-size:32px;
        font-weight:700;
        color:red;
        
    } 
</style>
<section class="wow animate__fadeIn">
            <div class="container">
                <div class="row">
                    <div class="col-md-12">
                 <div class="videobg">
                     <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320"><path fill="#f9f9f9" fill-opacity="1" d="M0,224L0,256L36.9,256L36.9,96L73.8,96L73.8,288L110.8,288L110.8,0L147.7,0L147.7,288L184.6,288L184.6,192L221.5,192L221.5,128L258.5,128L258.5,288L295.4,288L295.4,0L332.3,0L332.3,320L369.2,320L369.2,256L406.2,256L406.2,32L443.1,32L443.1,160L480,160L480,288L516.9,288L516.9,128L553.8,128L553.8,224L590.8,224L590.8,192L627.7,192L627.7,64L664.6,64L664.6,192L701.5,192L701.5,96L738.5,96L738.5,256L775.4,256L775.4,32L812.3,32L812.3,128L849.2,128L849.2,32L886.2,32L886.2,256L923.1,256L923.1,160L960,160L960,192L996.9,192L996.9,96L1033.8,96L1033.8,192L1070.8,192L1070.8,320L1107.7,320L1107.7,128L1144.6,128L1144.6,32L1181.5,32L1181.5,288L1218.5,288L1218.5,160L1255.4,160L1255.4,160L1292.3,160L1292.3,160L1329.2,160L1329.2,64L1366.2,64L1366.2,320L1403.1,320L1403.1,256L1440,256L1440,320L1403.1,320L1403.1,320L1366.2,320L1366.2,320L1329.2,320L1329.2,320L1292.3,320L1292.3,320L1255.4,320L1255.4,320L1218.5,320L1218.5,320L1181.5,320L1181.5,320L1144.6,320L1144.6,320L1107.7,320L1107.7,320L1070.8,320L1070.8,320L1033.8,320L1033.8,320L996.9,320L996.9,320L960,320L960,320L923.1,320L923.1,320L886.2,320L886.2,320L849.2,320L849.2,320L812.3,320L812.3,320L775.4,320L775.4,320L738.5,320L738.5,320L701.5,320L701.5,320L664.6,320L664.6,320L627.7,320L627.7,320L590.8,320L590.8,320L553.8,320L553.8,320L516.9,320L516.9,320L480,320L480,320L443.1,320L443.1,320L406.2,320L406.2,320L369.2,320L369.2,320L332.3,320L332.3,320L295.4,320L295.4,320L258.5,320L258.5,320L221.5,320L221.5,320L184.6,320L184.6,320L147.7,320L147.7,320L110.8,320L110.8,320L73.8,320L73.8,320L36.9,320L36.9,320L0,320L0,320Z"></path></svg>
                     <div class="text-overlays wow fadeInLeft animated" data-wow-delay=".60s"> 
                        Video <span>Gallary</span>
                        
                     </div>
                 </div>                
                    </div>
                </div>
                <div class="row row-cols-1 row-cols-md-2 ">
                @if(!empty($Datakey)) 
                @foreach($Datakey as $data)
                    <div class="col fit-videos text-center sm-margin-30px-bottom">
                        <!-- start vimeo video -->
                        <iframe  width="550" height="315" src="{{ asset('/public/upload/image/VideoGallery/').'/'.$data->avatar}}"></iframe>
                        <!-- end vimeo video -->
                    </div>
                @endforeach
                @endif
                </div>
            </div>
        </section>
@endsection