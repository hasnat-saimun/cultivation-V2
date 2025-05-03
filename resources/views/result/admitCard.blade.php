@extends('result.include')
@section('backTitle')
Admit Card
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
                                    <h3>Admit Card Generate</h3>
                                </div>
                            </div>
                            <form class="new-added-form" action="{{ route('getAdmitCard') }}" method="POST">
                                @csrf
                                <div class="row">
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
                                    <div class="col-12 form-group">
                                        <label>Class *</label>
                                        <select class="select2" name="classId" required>
                                            <option value="">Select *</option>
                                            @php
                                                $classes = \App\Models\classManage::orderBy('id','DESC')->get();
                                            @endphp
                                            @if(!empty($classes))
                                                @foreach($classes as $cls)
                                                <option value="{{ $cls->id }}">{{ $cls->className }}</option>
                                                @endforeach
                                            @endif
                                        </select>
                                    </div>
                                    <div class="col-12 form-group">
                                        <label>Session *</label>
                                        <select class="select2" name="sessionId" required>
                                            <option value="">Select *</option>
                                            @php
                                                $sessions = \App\Models\sessionManage::orderBy('id','DESC')->get();
                                            @endphp
                                            @if(!empty($sessions))
                                                @foreach($sessions as $sess)
                                                <option value="{{ $sess->id }}">{{ $sess->session }}</option>
                                                @endforeach
                                            @endif
                                        </select>
                                    </div>
                                    <div class="col-12 form-group">
                                        <label>Section/Group *</label>
                                        <select class="select2" name="groupId" required>
                                            <option value="">Select *</option>
                                            @php
                                                $department = \App\Models\sectionManage::orderBy('id','DESC')->get();
                                            @endphp
                                            @if(!empty($department))
                                                @foreach($department as $dept)
                                                <option value="{{ $dept->id }}">{{ $dept->section }}</option>
                                                @endforeach
                                            @endif
                                        </select>
                                    </div>
                                    <div class="col-12 form-group mg-t-8">
                                        <button type="submit" class="btn-fill-lg btn-gradient-yellow btn-hover-bluedark">Get Data</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
@endsection