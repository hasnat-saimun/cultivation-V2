@extends('academic.include')
@section('backTitle')
Notice Update
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
                                    <h3>Notice Update</h3>
                                </div>
                            </div>
                            @if(!empty($notice))
                            <form class="form" action="{{ route('updateNotice') }}" method="POST" enctype="multipart/form-data">
                                @csrf
                                <input type="hidden" name="noticeId" value="{{ $notice->id }}">
                                <div class="row">
                                    <div class="col-12 form-group">
                                        <label>Headline *</label>
                                        <input type="text" name="noticeHeadline" placeholder="Enter notice headline" class="form-control" value="{{ $notice->headline }}" required>
                                    </div>
                                    <div class="col-12 form-group">
                                        <label>Body *</label>
                                        <textarea name="noticeBody" placeholder="Describe the details of the notice" class="form-control" row="12" required>{!! $notice->body !!}</textarea>
                                    </div>
                                    <button type="submit" class="btn btn-success mx-2">Update Notice</button>
                                    <a href="{{ route('newNotice') }}" class="btn btn-primary mx-2">Create New</a>
                                </div>
                            </form>
                            @else
                                <div class="alert alert-danger">Sorry! No data found with your query</div>
                            @endif
                        </div>
                    </div>
                </div>
@endsection