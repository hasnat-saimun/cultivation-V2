@extends('account.include')
@section('backTitle')
New Fees Name
@endsection
@section('backIndex')
<div class="row mb-4">
    <div class="col-10 mx-auto">
        <div class="row ">
            <div class="col-10 mx-auto">
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
                    <form method="POST" class="card-body form form-group" action="{{route('saveFees')}}">
                        @csrf
                        <div class="row">
                            <!-- Class selection removed -->
                            <div class="col-6 mb-2">
                                <label for="feesName" class="form-label ">Fees Type</label>
                                <input type="text" class="form-control form-control-sm" id="feesName" name="feesName" placeholder="Enter the fees name" required>
                                </select>
                            </div>
                            <div class="col-6 mb-2">
                                <label for="feesAmount" class="form-label ">Fees Amount</label>
                                <input type="number" class="form-control form-control-sm" id="feesAmount" name="feesAmount" placeholder="Enter the fees amount" required>
                                </select>
                            </div>
                            <div class=" mx-auto gap-2 mt-5">
                                <button class="btn-fill-lg btn-gradient-yellow btn-hover-bluedark" type="submit">Submit</button>
                                <button class="btn-fill-lg bg-blue-dark btn-hover-bluedark" type="reset">Reset</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="row mt-3">
            <div class="col-md-10 mx-auto">
                <div class="card card-body  border  ">
                
                    <table class=" table table-striped table-hover  shadow-lg  rounded" >
                        <thead class="table-info">
                            <tr>
                            <!-- Class column removed -->
                            <th>Fees Name</th>
                            <th>Amount</th>
                            <th>Action</th>
                            </tr>
                        </thead>
                        <tbody class="">
                        @if(!empty($feesList) && count($feesList)>0)
                            @foreach($feesList as $fd)
                            <tr>
                                <td>{{$fd->feesName}}</td>
                                <td>{{$fd->feesAmount}}</td>
                            <td>
                                    <a href="{{route('editFees',['id'=>$fd->id])}}"><i class="fa-solid fa-pen-to-square mx-2" style="color: #4125b1;"></i></a>
                                    <x-delete-action :action="route('deleteFees',['id'=>$fd->id])"><i class="fa-solid fa-trash mx-2" style="color: #c10b26;"></i></x-delete-action>
                                </td>
                            </tr>
                            @endforeach
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
