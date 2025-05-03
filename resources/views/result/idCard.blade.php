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
                                <div class="col-12 col-md-4 mx-auto row">
                                    <div class="col-md-2 bg-success text-white"><div class="v-text">STUDENT</div></div>
                                    <div class="col-md-10"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
@endsection