@extends('result.include')
@section('backTitle')
Create Subject
@endsection
@section('backIndex')
                <!-- Dashboard summery Start Here -->
                <div class="row gutters-20 mb-4">
                    <!-- Admit Form Area Start Here -->
                    <div class="card height-auto">
                        <div class="card-header bg-light">
                            <a href="{{ route('allSubject') }}" class="btn btn-success">Subject List</a>
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
                                    <h3>Add New Subject</h3>
                                </div>
                            </div>
                            <form class="new-added-form" action="{{ route('confirmSubject') }}" method="POST" enctype="multipart/form-data">
                                @csrf
                                <div class="row">
                                    <div class="col-12 form-group">
                                        <label>Subject Name *</label>
                                        <input type="text" name="subjectName" placeholder="Enter subject name" class="form-control" required>
                                    </div>
                                    <div class="col-12 form-group">
                                        <label>Subject Type *</label>
                                        <select name="subjectType" id="" class="form-control">
                                            <option value="Main">Main</option>
                                            <option value="Optional">Optional</option>
                                        </select>
                                    </div>
                                    <div class="col-12 form-group">
                                        <label>Base Mark *</label>
                                        <input type="text" name="baseMark" placeholder="Enter the base make" class="form-control" required>
                                    </div>
                                    <div class="col-12 form-group">
                                        <label>Available Feature *</label>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" onclick="cqMarks(this)" name="cqValue" type="checkbox" id="CQ" value="CQ">
                                            <label class="form-check-label" for="CQ">CQ</label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" onclick="mcqMarks(this)" name="mcqValue" type="checkbox" id="MCQ" value="MCQ">
                                            <label class="form-check-label" for="MCQ">MCQ</label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" onclick="practicalMarks(this)" name="practicalValue" type="checkbox" id="Practical" value="Practical">
                                            <label class="form-check-label" for="Practical">Practical</label>
                                        </div>
                                        <div class="row">
                                            <div class="col-4" id="cqFiled"></div>
                                            <div class="col-4" id="mcqFiled"></div>
                                            <div class="col-4" id="practicalFiled"></div>
                                        </div>
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
                <script>
                    function cqMarks(checkbox){
                        if(checkbox.checked){
                            document.getElementById("cqField").innerHTML = `<lable for='cqMarksValue'>CQ Marks</label><input type='text' name='cqValue' class='form-control' placeholder='Enter the cq total marks'>`;
                        }else{
                            document.getElementById("cqField").innerHTML = "";
                        }
                    }
                </script>
@endsection