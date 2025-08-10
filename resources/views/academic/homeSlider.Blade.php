@extends('academic.include')
@section('backTitle')
Institute Info
@endsection
@section('backIndex')
@php 
    $homeSlider = \App\Models\homeSlider::orderBy('id','DESC')->first();
    if(!empty($homePage)):
        $pageId                 = $homeSlider->id;
        $headLine               = $homeSlider->headLine;
        $detail                 = $homeSlider->detail;
        $avatar                 = $homeSlider->avatar;
    else:
        $pageId                 = null;
        $headLine               = "";
        $detail                 = "";
        $avatar                 = "";
    endif;
@endphp
<!-- Dashboard summery Start Here -->
<div class="row gutters-20 mb-4">
    <div class="col-10 mx-auto">
        <div class="card">
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
            <div class="card-header">Slider Info</div>
            <div class="card-body cultivation">
                <form action="{{ route('sliderDetail') }}" class="form" method="POST" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="pageId">
                    <div class="mb-3">
                        <label for="headLine">Headline</label>
                        <input type="text" name="headLine" class="form-control" placeholder="Enter the headline">
                    </div>
                    <div class="mb-3">
                        <label for="detail">Details</label>
                        <textarea name="detail" class="form-control" placeholder="Enter description about institute"></textarea>
                    <div class="mb-3">
                    <label for="avatar">Avatar (150px X 150px)</label>
                        <input type="file" name="avatar" id="avatar"class="form-control-file">
                    </div>
                    <div class="mt-3 ">
                        <button class="btn btn-success btn-lg" type="submit">Save</button>
                    </div>
                </form>
            </div>
        </div>
    <div class="col-12">
        <div class="card-body cultivation">
            <div class="card-header mb-3">Plcement List</div>
            <table id="myTable" class="table table-striped table-responsive">
                <thead>
                    <tr>
                        <th>Image</th>
                        <th>Headline</th>
                        <th>Detail</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @if(!empty($data)) @foreach($data as $item)
                    <tr>
                        <td style="width:10%"><img class="w-100" src="{{ asset('/public/upload/cultivation/webHomepage/').'/'.$item->avatar}}" alt="{!! $item->headLine !!}" style="max-height: 120px !important;" /></td>
                        <td>{{ $item->headLine }}</td>
                        <td>{{ $item->detail }}</td>
                        <td>
                            <a href="{{ route('editPlc',['id'=>$item->id]) }}"><i class="fa-solid fa-pen-to-square mx-2" style="color: #4125b1;"></i></a>
                            <a href="{{ route('delPlc',['id'=>$item->id]) }}" onclick="return confirm('Are you sure you want to delete this item?');" title="Get Id Card"><i class="fa-solid fa-trash mx-2" style="color: #c10b26;"></i></a>
                        </td>
                    </tr>
                    @endforeach @else
                    <tr>
                        <td>-</td>
                        <td>-</td>
                        <td>-</td>
                        <td>-</td>
                        <td>-</td>
                        <td>-</td>
                        <td>-</td>
                        <td>-</td>
                    </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>
    </div>
</div>
<!-- Dashboard summery End Here -->
@endsection