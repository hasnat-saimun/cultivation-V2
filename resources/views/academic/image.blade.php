@extends('academic.include')
@section('backTitle')
  Include Image 
@endsection
@section('backIndex')
    @php 
    if(!empty($itemId)): 
        $plcList = \App\Models\PhotoGallery::find($itemId); 
        if(!empty($plcList)): 
            $title = $plcList->title; 
            $avatar = $plcList->avatar; 
    endif; 
    else: 
        $itemId = null; $title = ""; $avatar = ""; endif; @endphp
<!-- Dashboard summery Start Here -->
<div class="row gutters-20 mb-4">
    <div class="col-10 mx-auto">
        <div class="card">
            <div class="card-header">@yield('backTitle')</div>
            <div class="card-body cultivation">
                <div class="row">
                    <div class="col-12">
                        @if(session()->has('success'))
                        <div class="alert alert-success w-100">
                            {{ session()->get('success') }}
                        </div>
                        @endif @if(session()->has('error'))
                        <div class="alert alert-danger w-100">
                            {{ session()->get('error') }}
                        </div>
                        @endif
                    </div>
                </div>
                <form action="{{ route('savePhoto') }}" class="form row" method="POST" enctype="multipart/form-data">
                    <div class="col-12 col-md-6">
                        <input type="hidden" name="itemId" value="{{$itemId}}" />
                        @csrf
                        <div class="mb-3">
                            <label for="title">Head Line</label>
                            <input type="text" name="title" class="form-control" placeholder="Enter title of the image" value="{{ $title }}" />
                        </div>
                        <div class="mb-3">
                            <label for="avatar">Avatar(Photo)</label>
                            @if(!empty($avatar))
                            <div>
                                <img src="{{ asset('public/upload/image/PhotoGallery/').'/'.$avatar }}" class="w-75" height="300px">
                                <a href="{{ route('delPhotoContent',['id'=>$itemId]) }}" class="fw-bold text-danger">Delete</a>
                            </div>
                            @else
                            <input type="file" name="avatar" class="form-control-file" />
                            @endif
                        </div>
                        <div class="mb-3">
                            <button class="btn btn-success btn-lg mx-2" type="submit">Save</button>
                            <a class="btn btn-primary btn-lg mx-2" href="{{ route('newPhoto') }}">New Profile</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="card-body cultivation">
        <div class="card-header mb-3">Photo List</div>
        <table id="myTable" class="table table-striped">
            <thead>
                <tr>
                    <th>Hadeline</th>
                    <th>Photo</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @if(!empty($placementList)) @foreach($placementList as $item)
                <tr>
                    <td>{{ $item->title }}</td>
                    <td>{{ $item->avatar }}</td>
                    <td>
                        <a href="{{ $item->avatar }}"><i class="fa-solid fa-eye mx-2" style="color:rgb(35 170 211);"></i></a>
                        <a href="{{ route('editPhoto',['id'=>$item->id]) }}"><i class="fa-solid fa-pen-to-square mx-2" style="color: #4125b1;"></i></a>
                        <a href="{{ route('delPhoto',['id'=>$item->id]) }}"onclick="return confirm('Are you sure you want to delete this item?');" title="Get Id Card" ><i class="fa-solid fa-trash mx-2" style="color: #c10b26;"></i></a>
                    </td>
                </tr>
                @endforeach @else
                <tr>
                    <td>-</td>
                    <td>-</td>
                    <td>-</td>
                </tr>
                @endif
            </tbody>
        </table>
    </div>
</div>
<!-- Dashboard summery End Here -->
@endsection
