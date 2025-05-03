@extends('account.include')
@section('backTitle')
Account
@endsection
@section('backIndex')
                <!-- Dashboard Content Start Here -->
                <div class="row my-4 intro-box text-center">
                    <div class="col-2">
                        <a href="{{ route('tuitionFee') }}" class="btn btn-secondary btn-lg p-3 h3"><i class="fa-sharp fa-solid fa-display-chart-up-circle-dollar"></i> Collect TuitionFee</a>
                    </div>
                    <div class="col-3">
                        <a href="{{ route('cashCalculasView') }}" class="btn btn-secondary btn-lg p-3 h3"><i class="fa-sharp fa-solid fa-display-chart-up-circle-dollar"></i> Cash Calculas</a>
                    </div>
                    <div class="col-2">
                        <a href="{{ route('feesForm') }}" class="btn btn-secondary btn-lg p-3 h3"><i class="fa-sharp fa-solid fa-display-chart-up-circle-dollar"></i> Add Fees</a>
                    </div>
                </div>
@endsection