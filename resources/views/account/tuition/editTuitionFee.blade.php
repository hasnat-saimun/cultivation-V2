@extends('account.include')
@section('backTitle')
Edit Tuition Fee
@endsection
@section('backIndex')
<div class="row gutters-20 mb-4">
    <div class="col-10 mx-auto">
        <div class="row mx-auto ">
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
                    @if(session()->has('warning'))
                        <div class="alert alert-warning">
                            {{session()->get('warning')}}
                        </div>
                    @endif
        <form method="POST" class="card-body form form-group" action="{{route('updateTuitionFee')}}">
            @csrf
            <input type="hidden" name="tuitionFeeId" value="{{$editData->id}}">
            <div class="row mb-4">
                <h4 class="text-bold">Edit Tuition Fee</h4>
            </div>
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label for="stdId" class="form-label">Student ID</label>
                    <input type="text" class="form-control" id="stdId" name="stdId" value="{{ $editData->stdId }}" required>
                </div>
                <div class="col-md-4 mb-3">
                    <label for="feesType" class="form-label">Fees Type</label>
                    <select class="form-select form-control" id="feesType" name="feesType" required>
                        @if(!empty($feesData) && count($feesData)>0)
                            @foreach($feesData as $f)
                                <option value="{{ $f->id }}" data-amount="{{ $f->feesAmount }}" {{ $editData->feesType == $f->id ? 'selected' : '' }}>{{ $f->feesName }}</option>
                            @endforeach
                        @else
                            <option value="">No fees found</option>
                        @endif
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <label for="amount" class="form-label">Amount</label>
                    <input type="number" class="form-control" id="amount" name="amount" value="{{ $editData->amount }}" required>
                </div>
                <div class="gap-2 mt-4">
                    <button class="btn-fill-lg btn-gradient-yellow btn-hover-bluedark" type="submit">Update</button>
                    <a href="{{route('tuitionFeeList')}}" class="btn-fill-lg bg-blue-dark btn-hover-bluedark">Back</a>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
    (function(){
        const sel = document.getElementById('feesType');
        const amountInput = document.getElementById('amount');
        if(sel && amountInput){
            function applySelectedAmount(){
                const opt = sel.options[sel.selectedIndex];
                if(!opt) return;
                const amt = opt.getAttribute('data-amount');
                if(amt !== null && amt !== ''){
                    amountInput.value = amt; // auto-fill / override to default
                }
            }
            sel.addEventListener('change', applySelectedAmount);
            // Optional: if existing amount empty (shouldn't usually) we hydrate once
            if(amountInput.value === '' || amountInput.value === '0'){
                applySelectedAmount();
            }
        }
    })();
</script>
@endsection