@extends('result.include')
@section('backTitle')
Edit Exam
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
                                    <h3>Update Exam</h3>
                                </div>
                            </div>
                            @if(isset($item))
                            <form class="new-added-form" action="{{ route('updateExam') }}" method="POST" enctype="multipart/form-data">
                                <input type="hidden" name="itemId" value="{{ $item->id }}">
                                @csrf
                                <div class="row">
                                    <div class="col-12 form-group">
                                        <label>Exam Name *</label>
                                        <input type="text" name="examName" value="{{ $item->examName }}" placeholder="Enter exam name" class="form-control" required>
                                    </div>
                                    <div class="col-12 form-group">
                                        <label>Exam Startdate *</label>
                                        <input type="date" name="examDate" value="{{ $item->examDate }}" placeholder="Enter exam start date" class="form-control" required>
                                    </div>
                                    <div class="col-12 form-group">
                                        <label>Exam Enddate *</label>
                                        <input type="date" name="closeDate" value="{{ $item->closeDate }}" placeholder="Enter exam close date" class="form-control" required>
                                    </div>
                                    <div class="col-12 form-group">
                                        <label>Exam Base Mark *</label>
                                        <input type="text" name="baseMark" value="{{ $item->baseMark }}" placeholder="Enter the value of base mark of the exam" class="form-control" required>
                                    </div>
                                    <div class="col-12 form-group">
                                        <label>Exam for Class *</label>
                                        <select name="examClass" class="form-control" required>
                                            <option value="0">All Class</option>
                                            @php 
                                                $itemData = \App\Models\Classes::orderBy('id','DESC')->get();
                                            @endphp
                                            @if(!empty($itemData))
                                            @foreach($itemData as $item)
                                            <option value="{{ $item->id }}">{{ $item->className }}</option>
                                            @endforeach
                                            @endif
                                        </select>
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