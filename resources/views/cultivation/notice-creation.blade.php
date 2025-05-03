@extends('academic.include')
@section('backTitle')
Notice Creation
@endsection
@section('backIndex')
                <!-- Dashboard summery Start Here -->
                <div class="row gutters-20 mb-4">
                    <!-- Admit Form Area Start Here -->
                    <div class="card height-auto col-8 mx-auto">
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
                                    <h3>Notice Creation</h3>
                                </div>
                            </div>
                            <form class="form" action="{{ route('saveNotice') }}" method="POST" enctype="multipart/form-data">
                                @csrf
                                <div class="row">
                                    <div class="col-12 form-group">
                                        <label>Headline *</label>
                                        <input type="text" name="noticeHeadline" placeholder="Enter notice headline" class="form-control" required>
                                    </div>
                                    <div class="col-12 form-group">
                                        <label>Description *</label>
                                        <textarea name="noticeBody" placeholder="Describe the details of the notice" class="form-control" row="12" required></textarea>
                                    </div>
                                    <button type="submit" class="btn btn-success mx-2">Create Notice</button> 
                                    <a href="{{ route('noticeList') }}" class="btn btn-primary mx-2">All Notice</a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
@endsection