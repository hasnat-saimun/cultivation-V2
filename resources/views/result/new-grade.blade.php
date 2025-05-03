@extends('result.include')
@section('backTitle')
Create Grade
@endsection
@section('backIndex')
                <!-- Dashboard summery Start Here -->
                <div class="row gutters-20 mb-4">
                    <!-- Admit Form Area Start Here -->
                    <div class="card height-auto">
                        <div class="card-body col-10 mx-auto">
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
                                    <h3>Add New Grade</h3>
                                </div>
                            </div>
                            <form class="new-added-form" action="{{ route('confirmGrade') }}" method="POST" enctype="multipart/form-data">
                                @csrf
                                <div class="row">
                                    <div class="col-12 form-group">
                                        <label>Grade Name *</label>
                                        <input type="text" name="gradeName" placeholder="Enter the grade name" class="form-control" required>
                                    </div>
                                    <div class="col-12 form-group">
                                        <label>Grade Point *</label>
                                        <input type="text" name="gradePoint" placeholder="Enter the value of grade point" class="form-control" required>
                                    </div>
                                    <div class="col-12 form-group">
                                        <label>Minimum Marks *</label>
                                        <input type="text" name="minMark" placeholder="Enter the value of minimum marks" class="form-control" required>
                                    </div>
                                    <div class="col-12 form-group">
                                        <label>Maximum Marks *</label>
                                        <input type="text" name="maxMark" placeholder="Enter the value of maximum marks" class="form-control" required>
                                    </div>
                                    <div class="col-12 form-group">
                                        <label>Minimum Point *</label>
                                        <input type="text" name="minGp" placeholder="Enter the value of minimum point" class="form-control" required>
                                    </div>
                                    <div class="col-12 form-group">
                                        <label>Marximum Point *</label>
                                        <input type="text" name="maxGp" placeholder="Enter the value of maximum point" class="form-control" required>
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