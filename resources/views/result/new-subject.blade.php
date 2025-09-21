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
                                        <label>Assign Class *</label>
                                        <select name="classId" id="" class="form-control">
                                            <option value="">Select Class</option>
                                            <option value="0">All</option>
                                            @if($classList->count() > 0)
                                                @foreach($classList as $class)
                                                    <option value="{{ $class->id }}">{{ $class->className }}</option>
                                                @endforeach
                                            @endif
                                        </select>
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
                function validateTotalMarks() {
                    let cq = document.querySelector("input[name='cqValue'][type='text']");
                    let mcq = document.querySelector("input[name='mcqValue'][type='text']");
                    let practical = document.querySelector("input[name='practicalValue'][type='text']");

                    let cqVal = cq ? parseInt(cq.value) || 0 : 0;
                    let mcqVal = mcq ? parseInt(mcq.value) || 0 : 0;
                    let practicalVal = practical ? parseInt(practical.value) || 0 : 0;

                    let total = cqVal + mcqVal + practicalVal;

                    if (total > 100) {
                        alert("Total marks for CQ, MCQ, and Practical cannot exceed 100.");
                        return false;
                    }
                    return true;
                }

                // Attach validation to form submit
                document.addEventListener("DOMContentLoaded", function() {
                    let form = document.querySelector("form.new-added-form");
                    if(form){
                        form.onsubmit = function() {
                            return validateTotalMarks();
                        }
                    }
                });
                    function mcqMarks(checkbox){
                        if(checkbox.checked){
                            document.getElementById("mcqFiled").innerHTML = `<label for='mcqMarksValue'>MCQ Marks</label><input type='text' name='mcqValue' class='form-control' placeholder='Enter the mcq total marks'>`;
                        }else{
                            document.getElementById("mcqFiled").innerHTML = "";
                        }
                    }
                    function cqMarks(checkbox){
                        if(checkbox.checked){
                            document.getElementById("cqFiled").innerHTML = `<label for='cqMarksValue'>CQ Marks</label><input type='text' name='cqValue' class='form-control' placeholder='Enter the cq total marks'>`;
                        }else{
                            document.getElementById("cqFiled").innerHTML = "";
                        }
                    }
                    function practicalMarks(checkbox){
                        if(checkbox.checked){
                            document.getElementById("practicalFiled").innerHTML = `<label for='practicalMarksValue'>Practical Marks</label><input type='text' name='practicalValue' class='form-control' placeholder='Enter the practical total marks'>`;
                        }else{
                            document.getElementById("practicalFiled").innerHTML = "";
                        }
                    }
                </script>
@endsection