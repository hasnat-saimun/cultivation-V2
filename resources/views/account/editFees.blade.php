@extends('account.include')
@section('backTitle')
Institute Info
@endsection
@section('backIndex')

<div class="row gutters-20 mb-4">
    <div class="col-10 mx-auto">
        <div class="row ">
            <div class="col-12 mx-auto">
                <div class="card shadow  p-2 border-0 ">
                @if(session()->has('error'))
                    <div class="alert alert-danger">
                        {{session()->get('error')}}
                    </div>
                @endif
                @if(session()->has('success'))
                    <div class="alert alert-success">
                        {{session()->get('success')}}
                    </div>
                @endif
                    <form method="POST" class="card-body form form-group" action="{{route('updateFees')}}">
                        @csrf
                    <div class="row">
                        <input type="hidden" name="feesId" value="{{$editData->id}}">
                        <div class="col-6 mb-2">
                            @php
                                 $classData  = \App\Models\classManage::find($editData->class);
                            @endphp
                            <label for="class" class="form-label ">Class Name</label>
                            <select class="select2" name="class" >
                                        @if(!empty($classData))
                                        <option value="{{$classData->id}}">{{$classData->className}} </option>
                                        @endif
                                        @if(!empty($classDetails) && count($classDetails)>0)
                                            @foreach($classDetails as $cd)
                                            <option value="{{ $cd->id}}">{{ $cd->className}}</option>
                                            @endforeach
                                        @endif
                                        </select>
                        </div>
                        <div class="col-6 mb-2">
                            <label for="feesName" class="form-label ">Fees Type</label>
                            <input t ype="text" class="form-control form-control-sm" id="feesName" name="feesName" placeholder="Enter the fees name" value="{{$editData->feesName}}" required>
                        </div>
                        <div class="col-6 mb-2">
                            <label for="feesAmount" class="form-label ">Fees Amount</label>
                            <input type="text" class="form-control form-control-sm" id="feesAmount" name="feesAmount" placeholder="Enter the fess amount" value="{{$editData->feesAmount}}" required>
                        </div>
                        <div class=" mx-auto gap-2 mt-5">
                            <a href="{{route('feesForm')}}" class="btn-fill-lg bg-blue-dark btn-hover-bluedark" type="submit">Back</a>
                            <button class="btn-fill-lg btn-gradient-yellow btn-hover-bluedark" type="submit">Update</button>
                        </div>
</div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection