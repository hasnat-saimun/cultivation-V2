@extends('cultivation.include')
@section('backTitle')
Dashboard
@endsection
@section('backIndex')
<!-- Dashboard summery Start Here -->
<div class="row gutters-20 mb-4">
    <div class="col-xl-3 col-sm-6 col-12">
        <div class="dashboard-summery-one mg-b-20">
            <div class="row align-items-center">
                <div class="col-6">
                    <div class="item-icon bg-light-green ">
                        <i class="flaticon-classmates text-green"></i>
                    </div>
                </div>
                <div class="col-6">
                    <div class="item-content">
                        <div class="item-title">Students</div>
                        <div class="item-number"><span class="counter" data-num="150000">1,50,000</span></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-sm-6 col-12">
        <div class="dashboard-summery-one mg-b-20">
            <div class="row align-items-center">
                <div class="col-6">
                    <div class="item-icon bg-light-blue">
                        <i class="flaticon-multiple-users-silhouette text-blue"></i>
                    </div>
                </div>
                <div class="col-6">
                    <div class="item-content">
                        <div class="item-title">Teachers</div>
                        <div class="item-number"><span class="counter" data-num="2250">2,250</span></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-sm-6 col-12">
        <div class="dashboard-summery-one mg-b-20">
            <div class="row align-items-center">
                <div class="col-6">
                    <div class="item-icon bg-light-yellow">
                        <i class="flaticon-couple text-orange"></i>
                    </div>
                </div>
                <div class="col-6">
                    <div class="item-content">
                        <div class="item-title">Parents</div>
                        <div class="item-number"><span class="counter" data-num="5690">5,690</span></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-sm-6 col-12">
        <div class="dashboard-summery-one mg-b-20">
            <div class="row align-items-center">
                <div class="col-6">
                    <div class="item-icon bg-light-red">
                        <i class="flaticon-money text-red"></i>
                    </div>
                </div>
                <div class="col-6">
                    <div class="item-content">
                        <div class="item-title">Earnings</div>
                        <div class="item-number"><span>$</span><span class="counter" data-num="193000">1,93,000</span></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Dashboard summery End Here -->
@endsection