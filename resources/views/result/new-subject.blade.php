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
                                    @if($errors->any())
                                        <div class="alert alert-danger w-100"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
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
                                    @include('result.partials.subject-class-scope', ['selectedClassIds' => [], 'allClasses' => false])
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
                                    <div class="col-12 form-group">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="isReligious" name="isReligious" value="1">
                                            <label class="form-check-label" for="isReligious">Mark as Religious Subject</label>
                                        </div>
                                    </div>
                                    <div class="col-12 form-group" id="defaultRelSixWrap" style="display:none;">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="defaultReligiousForAllClass" name="defaultReligiousForAllClass" value="1">
                                            <label class="form-check-label" for="defaultReligiousForAllClass">Set as default Religious subject for All Class</label>
                                        </div>
                                    </div>
                                    <div class="col-12 form-group" id="defaultRelAllWrap" style="display:none;">
                                        <label>Set as default Religious subject for classes</label>
                                        <div class="row">
                                            @if($classList->count() > 0)
                                                @foreach($classList as $class)
                                                    <div class="col-6">
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="checkbox" id="defaultRelClass{{ $class->id }}" name="defaultReligiousClasses[]" value="{{ $class->id }}">
                                                            <label class="form-check-label" for="defaultRelClass{{ $class->id }}">{{ $class->className }}</label>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            @endif
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
                    const isRel = document.getElementById('isReligious');
                    const wrapSix = document.getElementById('defaultRelSixWrap');
                    const wrapAll = document.getElementById('defaultRelAllWrap');
                    const allToggle = document.getElementById('defaultReligiousForAllClass');
                    if(isRel){
                        const toggle = ()=> {
                            const show = isRel.checked ? 'block' : 'none';
                            if(wrapSix) wrapSix.style.display = show;
                            if(wrapAll) wrapAll.style.display = show;
                        };
                        isRel.addEventListener('change', toggle);
                        toggle();
                    }
                    if(allToggle){
                        allToggle.addEventListener('change', function(){
                            const boxes = document.querySelectorAll("input[name='defaultReligiousClasses[]']");
                            boxes.forEach(b => { b.checked = allToggle.checked; });
                        });
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
