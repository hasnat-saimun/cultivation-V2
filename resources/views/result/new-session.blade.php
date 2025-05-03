@extends('result.include')
@section('backTitle')
Create Session
@endsection
@section('backIndex')
                <!-- Dashboard summery Start Here -->
                <div class="row gutters-20 mb-4">
                    <!-- Admit Form Area Start Here -->
                    <div class="card height-auto">
                        <div class="card-header bg-light">
                            <a href="{{ route('allSession') }}" class="btn btn-success">Session List</a>
                        </div>
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
                                    <h3>Add New Session</h3>
                                </div>
                            </div>
                            <form class="new-added-form" action="{{ route('confirmSession') }}" method="POST" enctype="multipart/form-data">
                                @csrf
                                <div class="row">
                                    <div class="col-12 form-group">
                                        <label>Session Name *</label>
                                        <input type="text" name="session" placeholder="Enter session name" class="form-control" required>
                                    </div>
                                    <div class="col-12 form-group mg-t-8">
                                        <button type="submit" class="btn-fill-lg btn-gradient-yellow btn-hover-bluedark">Save</button>
                                        <button type="reset" class="btn-fill-lg bg-blue-dark btn-hover-bluedark">Reset</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
@endsection