@extends('account.include')
@section('backTitle')
Tuition Fee
@endsection
@section('backIndex')
<div class="row gutters-20 mb-4">
    <div class="col-10 mx-auto">
        <div class="row mx-auto ">
            
            <form method="POST" class="card-body form" action="{{route('saveTuitionfee')}}">@if(session()->has('error'))
                <div class="alert alert-danger">
                    {{session()->get('error')}}
                </div>
            @endif
            @if(session()->has('success'))
                <div class="alert alert-success">
                    {{session()->get('success')}}
                </div>
            @endif
                @csrf
                <div class="row mb-4">
                    <h4 class="text-bold">Student Fees Collection</h4>
                </div>
                <div class="row align-items-center">
                    <div class="col-4 form-group">
                        <input type="text" class="form-control" placeholder="Enter student ID to collect tution fee" name="stdId" id="stdId" required >
                    </div>
                    <div class="col-4 text-center form-group">
                        <a href="#" onclick="getStudent()" class="btn-fill-lg btn-gradient-yellow btn-hover-bluedark">Get Data</a>
                    </div>
                </div>
                <div class="row" id="studentData">
                </div>
            </form>
        </div>
    </div>
</div>
<script>
    function getStudent() {
        var str   = document.getElementById('stdId').value;
        if(str == "") {
            document.getElementById("studentData").innerHTML = "";
            return;
        }else {
            var xmlhttp     = new XMLHttpRequest();
            xmlhttp.onreadystatechange = function() {
                if (this.readyState == 4 && this.status == 200) {
                    document.getElementById("studentData").innerHTML = this.responseText;
                }
            };
            xmlhttp.open("GET","{{ url('/') }}/"+"getStudentForTutionFee/"+str,true);
            xmlhttp.send();
        }
    }
</script>

@endsection