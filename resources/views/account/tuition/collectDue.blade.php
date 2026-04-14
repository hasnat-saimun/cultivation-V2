@extends('account.include')
@section('backTitle')
Collect Dues Amount
@endsection
@section('backIndex')
<div class="row gutters-20 mb-4">
    <div class="col-10 mx-auto">
        <div class="row mx-auto ">
            @if(session()->has('error'))
                <div class="alert alert-danger">{{session()->get('error')}}</div>
            @endif
            @if(session()->has('success'))
                <div class="alert alert-success">{{session()->get('success')}}</div>
            @endif
            @if(session()->has('warning'))
                <div class="alert alert-warning">{{session()->get('warning')}}</div>
            @endif

            <form method="POST" class="card-body form form-group" action="{{ route('collectDueSubmit') }}">
                @csrf
                <input type="hidden" name="tuitionFeeId" value="{{ $editData->id }}">
                @php
                    $dueAmount = (float)($editData->due_amount ?? $editData->amount ?? 0);
                    $paidAmount = (float)($editData->paid_amount ?? $editData->amount ?? 0);
                    $remainingDue = max(0, $dueAmount - $paidAmount);
                @endphp
                <div class="row mb-4">
                    <h4 class="text-bold">Collect Dues Amount</h4>
                </div>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Student ID</label>
                        <input type="text" class="form-control" value="{{ $editData->stdId }}" readonly>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Fees Type</label>
                        @php
                            $feeInfo = null;
                            foreach(($feesData ?? []) as $f){
                                if((int)$f->id === (int)$editData->feesType){
                                    $feeInfo = $f;
                                    break;
                                }
                            }
                        @endphp
                        <input type="text" class="form-control" value="{{ $feeInfo->feesName ?? ('Fee #'.$editData->feesType) }}" readonly>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Fee Month</label>
                        <input type="month" class="form-control" value="{{ !empty($editData->fee_month) ? \Carbon\Carbon::parse($editData->fee_month)->format('Y-m') : $editData->created_at->format('Y-m') }}" readonly>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label">Setup Amount</label>
                        <input type="number" class="form-control" value="{{ number_format($dueAmount, 2, '.', '') }}" readonly>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Collected Amount</label>
                        <input type="number" class="form-control" value="{{ number_format($paidAmount, 2, '.', '') }}" readonly>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Current Due Amount</label>
                        <input type="number" class="form-control" value="{{ number_format($remainingDue, 2, '.', '') }}" readonly>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label for="collectAmount" class="form-label">Collect Due Amount</label>
                        <input type="number" step="0.01" min="0.01" max="{{ number_format($remainingDue, 2, '.', '') }}" class="form-control" id="collectAmount" name="collectAmount" value="{{ old('collectAmount') }}" {{ $remainingDue <= 0 ? 'disabled' : '' }} required>
                    </div>

                    <div class="gap-2 mt-4">
                        <button class="btn-fill-lg btn-gradient-yellow btn-hover-bluedark" type="submit" {{ $remainingDue <= 0 ? 'disabled' : '' }}>Collect Due</button>
                        <a href="{{ route('duesDashboard') }}" class="btn-fill-lg bg-blue-dark btn-hover-bluedark">Back</a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
