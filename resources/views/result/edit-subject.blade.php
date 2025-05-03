@extends('result.include')
@section('backTitle')
Edit Subject
@endsection
@section('backIndex')
                <!-- Dashboard summery Start Here -->
                <div class="row gutters-20 mb-4">
                    <!-- Admit Form Area Start Here -->
                    <div class="card height-auto">
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
                                    <h3>Update Subject</h3>
                                </div>
                            </div>
                            @if(isset($item))
                            <form class="new-added-form" action="{{ route('updateSubject') }}" method="POST" enctype="multipart/form-data">
                                @csrf
                                <input type="hidden" name="itemId" value="{{ $item->id }}">
                                <div class="row">
                                    <div class="col-12 form-group">
                                        <label>Subject Name *</label>
                                        <input type="text" name="subjectName" value="{{ $item->subjectName }}" placeholder="Enter the class name" class="form-control" required>
                                    </div>
                                    <div class="col-12 form-group">
                                        <label>Subject Type *</label>
                                        <select name="subjectType" id="" class="form-control">
                                            @if(!empty($item->subjectType))
                                            <option value="{{ $item->subjectType }}">{{ $item->subjectType }}</option>
                                            @endif
                                            <option value="Main">Main</option>
                                            <option value="Optional">Optional</option>
                                        </select>
                                    </div>
                                    <div class="col-12 form-group">
                                        <label>Available Feature *</label>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="checkbox" id="CQ" value="CQ">
                                            <label class="form-check-label" for="CQ">CQ</label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="checkbox" id="MCQ" value="MCQ">
                                            <label class="form-check-label" for="MCQ">MCQ</label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="checkbox" id="Practical" value="Practical">
                                            <label class="form-check-label" for="Practical">Practical</label>
                                        </div>
                                    </div>
                                    <div class="col-12 form-group mg-t-8">
                                        <button type="submit" class="btn-fill-lg btn-gradient-yellow btn-hover-bluedark">Save</button>
                                        <button type="reset" class="btn-fill-lg bg-blue-dark btn-hover-yellow">Reset</button>
                                    </div>
                                </div>
                            </form>
                            @else
                            <div class="row">
                                <div class="col-12">
                                    <div class="alert alert-info">
                                        Opps! Sorry, No data found for update
                                    </div>
                                </div>
                            </div>    
                            @endif
                        </div>
                    </div>
                </div>
@endsection