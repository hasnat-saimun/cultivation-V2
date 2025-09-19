@extends('result.include')
@section('backTitle')
Create Marksheet
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
                                    <h3>Generate Marksheet</h3>
                                </div>
                            </div>
                            <form class="new-added-form" action="{{ route('generateMarksheet') }}" method="POST">
                                @csrf
                                <div class="row">
                                    <div class="col-12 form-group">
                                        <label>Student ID *</label>
                                        <input type="text" class="form-control" name="stdId" placeholder="Example: SBCSTDID-1">
                                    </div>
                                    <div class="col-12 form-group">
                                        <label>Exam *</label>
                                        <select class="select2" name="examId" required>
                                            <option value="">Select *</option>
                                            @php
                                                $examList = \App\Models\Exam::orderBy('id','DESC')->get();
                                            @endphp
                                            @if(!empty($examList))
                                                @foreach($examList as $exm)
                                                <option value="{{ $exm->id }}">{{ $exm->examName }}</option>
                                                @endforeach
                                            @endif
                                        </select>
                                    </div>
                                    <div class="col-12 form-group mg-t-8">
                                        <button type="submit" class="btn btn-success">Generate Marksheet</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
@endsection