@extends('account.include')
@section('backTitle')
Edit Cash Calculas
@endsection
@section('backIndex')
<div class="row gutters-20 mb-4">
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
            <form method="POST" class="card-body form form-group" action="{{route('updateCashCalculas')}}">
                @csrf
                <input type="hidden" name="calculasId" value="{{$editData->id}}">
                <div class="mb-2">
                    <label for="source" class="form-label ">Source</label>
                    <input type="text" class="form-control form-control-sm" id="source" name="source" placeholder="" value="{{$editData->source}}" required>
                    </select>
                </div>
                <div class="mb-2">
                    <label for="amount" class="form-label ">Amount</label>
                    <input type="number" class="form-control form-control-sm" id="amount" name="amount" placeholder="" value="{{$editData->amount}}" required>
                </div>
                <div class="mb-2">
                    <label for="transaction" class="form-label">Type Of Transaction</label>
                    <select class="form-select select2" id="transaction" name="transaction" aria-label="Default select example"  required>
                        <option selected>{{$editData->transaction}}</option>
                        <option value="Debit">Debit</option>
                        <option value="Crtedit">Crtedit</option>
                    </select>
                </div>
                    <div class="col-6  d-grid gap-2 mt-5">
                        <a href="{{route('reportListView')}}"class="btn-fill-lg bg-blue-dark btn-hover-bluedark">Back</a>
                    <button class="btn-fill-lg btn-gradient-yellow btn-hover-bluedark" type="submit">Update</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection