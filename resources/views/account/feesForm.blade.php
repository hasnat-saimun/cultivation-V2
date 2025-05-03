@extends('account.include')
@section('backTitle')
New Fees Name
@endsection
@section('backIndex')
<div class="row gutters-20 mb-4">
    <div class="col-10 mx-auto">
        <div class="row ">
            <div class="col-6 mx-auto  ">
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
                        <div class="mb-2">
                            <label for="feesName" class="form-label ">Fees Entry</label>
                            <input type="text" class="form-control form-control-sm" id="feesName" name="feesName" placeholder="" required>
                            </select>
                        </div>
                        <div class=" mx-auto gap-2 mt-5">
                            <button class="btn-fill-lg btn-gradient-yellow btn-hover-bluedark" type="submit">Submit</button>
                            <button class="btn-fill-lg bg-blue-dark btn-hover-bluedark" type="reset">Reset</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6 mx-auto">
                <div class="card card-body  border  ">
                
                    <table class=" table table-striped table-hover  shadow-lg  rounded" >
                        <thead class="table-info">
                            <tr>
                            <th>Fees Name</th>
                            <th>Action</th>
                            </tr>
                        </thead>
                        <tbody class="">
                        @if(!empty($feesList) && count($feesList)>0)
                            @foreach($feesList as $fd)
                            <tr>
                                <td>{{$fd->feesName}}</td>
                            <td>
                                    <a href="{{route('editFees',['id'=>$fd->id])}}"><i class="fa-solid fa-pen-to-square mx-2" style="color: #4125b1;"></i></a>
                                    <a onclick="confirm('are you sure')" href="{{route('deleteFees',['id'=>$fd->id])}}"><i class="fa-solid fa-trash mx-2" style="color: #c10b26;"></i></a>
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