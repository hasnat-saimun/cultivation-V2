@extends('academic.include')
@section('backTitle')
Create Department
@endsection
@section('backIndex')
                <!-- Dashboard summery Start Here -->
                <div class="row gutters-20 mb-4">
                    <!-- Admit Form Area Start Here -->
                    <div class="card height-auto col-10 mx-auto">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-12">
                                    @if(session()->has('success'))
                                        <div class="alert alert-success w-100">
                                            {{ session()->get('success') }}
                                        </div>
                                    @endif
                                    @if(session()->has('error'))
                                        <div class="alert alert-danger w-100">
                                            {{ session()->get('error') }}
                                        </div>
                                    @endif
                                </div>
                            </div>
                            <div class="heading-layout1">
                                <div class="item-title">
                                    <h3>Add New Department</h3>
                                </div>
                            </div>
                            <div class="row">
                                <!-- ID CARD DESIGN ONE -->
                                <div class="col-12 col-md-6 mx-auto row">
                                    <div class="col-md-12">
                                        <div class="id-bg p-4 text-center pt-1 row">
                                            <div class="col-12">
                                                <h3 class="fw-bold mb-0">Sonar Bangla University College</h3>
                                                <p class="fw-bold mb-0 small">Poyat, Gobindapur, Vorasar, Burichong, Cumilla</p>
                                                <p class="fw-bold mb-0 small mb-1">Tel:- 01716841785, EIN:- 434713</p>
                                            </div>
                                            <div class="col-6 mx-auto display-5 fw-bold bg-success text-white rounded mb-4">STUDENT ID CARD</div>
                                            <div class="row mt-1 align-items-center no-gutter">
                                                <div class="col-4">
                                                    <img src="{{ asset('/public/back-office/img/') }}/avatar.jpeg" alt="Student" class="w-100">
                                                </div>
                                                <div class="col-8 text-left">
                                                    <p class="mb-0"><span class="fw-bold"> Name:</span> Abu Yousuf</p>
                                                    <p class="mb-0"><span class="fw-bold"> Roll Number:</span> 265233</p>
                                                    <p class="mb-0"><span class="fw-bold"> Class:</span> Eleven</p>
                                                    <p class="mb-0"><span class="fw-bold"> Session:</span> 2022-23</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
@endsection