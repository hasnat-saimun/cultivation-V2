@extends('academic.include')
@section('backTitle')
All Notice
@endsection
@section('backIndex')
                <!-- Social Media Start Here -->
                <div class="row gutters-20 mt-4">
                    <div class="col-12 col-md-10 mx-auto">
                        <div class="card card-default">
                            <div class="card-header bg-light">
                                <h3>All Notice</h3>
                                <a href="{{ route('newNotice') }}" class="btn btn-success">New Notice</a>
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
                                <table id="myTable" class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>SL</th>
                                            <th>Headline</th>
                                            <th>Details</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @if(!empty($noticeBoard))
                                        @php
                                            $x = 1;
                                        @endphp
                                        @foreach($noticeBoard as $notice)
                                            <tr>
                                                <td>{{ $x }}</td>
                                                <td>{{ $notice->headline }}</td>
                                                <td>{!! Str::limit($notice->body,'30','......') !!}</td>
                                                <td>
                                        <a href="{{ route('prevNotice',  ['id'=>$notice->id]) }}"><i class="fa-solid fa-eye mx-2" style="color:rgb(35 170 211);"></i></a>
                                        <a href="{{ route('editNotice',['id'=>$notice->id]) }}"><i class="fa-solid fa-pen-to-square mx-2" style="color: #4125b1;"></i></a>
                                        <a href="{{ route('delNotice',['id'=>$notice->id]) }}"onclick="return confirm('Are you sure you want to delete this item?');" title="Get Id Card" ><i class="fa-solid fa-trash mx-2" style="color: #c10b26;"></i></a>
                                                    
                                                </td>
                                            </tr>
                                        @php
                                            $x++;
                                        @endphp
                                        @endforeach
                                        @else
                                            <tr>
                                                <td>1</td>
                                                <td>Test notice will goes hear</td>
                                                <td>Lorem ipsom is a common text in design world.</td>
                                                <td>-</td>
                                            </tr>
                                        @endif
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
@endsection