@extends('frontend.include')
@section('fronttitle')
Syllabus
@endsection
@section('frontcontent')
<style>
.contactHeading{
    font-size:32px;
    font-weight:700;
    text-align:center;
    font-family:Raleway;
    margin-top:50px;
    margin-bottom:70px;
}
.contactHeading span{
    font-size:32px;
    font-weight:700;
    text-align:center;
    font-family:Raleway;
    color:red;
}

</style>
   <div class="container">
       <div calss="row text-center">
           <div class="col-md-12">
               <div class="contactHeading">
                   Get In <span>Touch</span>
               </div>
           </div>
        </div> 
        <div class='row'>
            @if(Session::get('success'))
              <div class="alert alert-success">
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                  <i class="material-icons">close</i>
                </button>
                <span>{!! Session::get('success') !!}</span>
              </div>
            @endif
            @if(Session::get('error'))
              <div class="alert alert-warning">
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                  <i class="material-icons">close</i>
                </button>
                <span>{!! Session::get('error') !!}</span>
              </div>
            @endif            
            <form    method="post" id="myForm" action="" enctype="multipart/form-data"> 
            <div class="row">
               <div class="col-md-6">
                        @csrf
                      <div style="margin-bottom:20px; " >
                        <input type="text" class="form-control" name="name" placeholder="Name">
                      </div>
                      <div style="margin-bottom:20px; ">
                        <input type="email" name="email" class="form-control" placeholder="Email">
                      </div>
                      <div style="margin-bottom:20px; ">
                        <input type="text" name="phone" class="form-control" placeholder="Phone">
                      </div>
                      <div style="margin-bottom:20px; ">
                        <input type="text" name="subject" class="form-control" placeholder="Subject">
                      </div>                  
               </div>
               <div class="col-md-6">
                   <div class="form-floating">
                      <textarea class="form-control" name="message" placeholder="Message here" style="height:200px; margin-bottom:10px; "id="floatingTextarea"></textarea>
                    </div>
               </div>
               <div class="row">
                   <div class="col-md-3">
                       <button style="margin-bottom:20px; " type="submit" class="btn btn-success btn-block "> Submit </button>
                   </div>                   
               </div>
            </div>
            </form>             
        </div>
       </div>
@endsection