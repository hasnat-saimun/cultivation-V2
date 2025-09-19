@extends('result.include')
@section('backTitle')
Edit Subject
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
                                        <label>Passing System *</label>
                                        <select name="passingSystem" class="form-control">
                                            <option value="1" {{ $item->passingSystem == 1 ? 'selected' : '' }}>Feature Wise</option>
                                            <option value="2" {{ $item->passingSystem == 2 ? 'selected' : '' }}>Total Marks</option>
                                        </select>
                                    </div>
                                    <div class="col-12 form-group">
                                        <label>Available Feature *</label>
                                        @php
                                            $features = $item->availableFeature ? explode(',', $item->availableFeature) : [];
                                        @endphp
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" onclick="cqMarks(this)" name="availableFeature[]" type="checkbox" id="CQ" value="CQ"
                                                {{ $item->CQ == null ?  '' : 'checked' }}>
                                            <label class="form-check-label" for="CQ">CQ</label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" onclick="mcqMarks(this)" name="availableFeature[]" type="checkbox" id="MCQ" value="MCQ"
                                                {{ $item->MCQ == null ?  '' : 'checked' }}>
                                            <label class="form-check-label" for="MCQ">MCQ</label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" onclick="practicalMarks(this)" name="availableFeature[]" type="checkbox" id="Practical" value="Practical"
                                                {{ $item->Practical == null ?  '' : 'checked' }}>
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
                            let value = "{{ $item->MCQ ?? '' }}";
                            if(checkbox.checked){
                                document.getElementById("mcqFiled").innerHTML = `<label for='mcqMarksValue'>MCQ Marks</label><input type='text' name='mcqValue' class='form-control' placeholder='Enter the mcq total marks' value='${value}'>`;
                            }else{
                                document.getElementById("mcqFiled").innerHTML = "";
                            }
                        }
                        function cqMarks(checkbox){
                            let value = "{{ $item->CQ ?? '' }}";
                            if(checkbox.checked){
                                document.getElementById("cqFiled").innerHTML = `<label for='cqMarksValue'>CQ Marks</label><input type='text' name='cqValue' class='form-control' placeholder='Enter the cq total marks' value='${value}'>`;
                            }else{
                                document.getElementById("cqFiled").innerHTML = "";
                            }
                        }
                        function practicalMarks(checkbox){
                            let value = "{{ $item->Practical ?? '' }}";
                            if(checkbox.checked){
                                document.getElementById("practicalFiled").innerHTML = `<label for='practicalMarksValue'>Practical Marks</label><input type='text' name='practicalValue' class='form-control' placeholder='Enter the practical total marks' value='${value}'>`;
                            }else{
                                document.getElementById("practicalFiled").innerHTML = "";
                            }
                        }

                        // On page load, show fields for already checked features and set values
                        document.addEventListener("DOMContentLoaded", function() {
                            if(document.getElementById("CQ").checked) cqMarks(document.getElementById("CQ"));
                            if(document.getElementById("MCQ").checked) mcqMarks(document.getElementById("MCQ"));
                            if(document.getElementById("Practical").checked) practicalMarks(document.getElementById("Practical"));
                        });
                    </script>
@endsection