@extends('result.include')
@section('backTitle')
All Grade
@endsection
@section('backIndex')
                <!-- Social Media Start Here -->
                <div class="row gutters-20 mt-4">
                    <div class="col-12 col-md-10 mx-auto">
                        <div class="card card-default">
                            <div class="card-header bg-light">
                                <h3>Grade List</h3>
                                <a href="{{ route('createGrade') }}" class="btn btn-success">Create Grade</a>
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
                                            <th>Sl</th>
                                            <th>Name</th>
                                            <th>Grade Point</th>
                                            <th>Min Marks</th>
                                            <th>Max Marks</th>
                                            <th>Min Point</th>
                                            <th>Max Point</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @if(!empty($itemData))
                                        @php
                                        $x = 1;
                                        @endphp
                                        @foreach($itemData as $item)
                                            <tr>
                                                <td>{{ $x }}</td>
                                                <td>{{ $item->gradeName }}</td>
                                                <td>{{ $item->gradePoint }}</td>
                                                <td>{{ $item->minMark }}</td>
                                                <td>{{ $item->maxMark }}</td>
                                                <td>{{ $item->minGp }}</td>
                                                <td>{{ $item->maxGp }}</td>
                                                <td>
                                                    <a href="{{ route('editGrade',['itemId'=>$item->id]) }}"><i class="fa-light fa-pen-to-square fa-xl"></i></a>
                                                    <a href="{{ route('delGrade',['itemId'=>$item->id]) }}"><i class="fa-thin fa-circle-trash fa-xl"></i></a>
                                                </td>
                                            </tr>
                                        @php
                                        $x++;
                                        @endphp
                                        @endforeach
                                        @else
                                            <tr>
                                                <td>SBC02</td>
                                                <td>Rasek Khondokar</td>
                                                <td>2023-2024</td>
                                                <td>Science</td>
                                                <td>01234567890</td>
                                                <td>Edit</td>
                                            </tr>
                                        @endif
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
@endsection