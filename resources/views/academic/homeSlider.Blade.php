@extends('academic.include')
@section('backTitle')
Institute Info
@endsection
@section('backIndex')
@php 
if(!empty($itemId)): 
    $homePage = \App\Models\HomeSlider::find($itemId); 
    if(!empty($homePage)): 
        $headLine              = $homePage->headLine;
        $detail           = $homePage->detail;
        $avatar           = $homePage->avatar;
        $pageId                 = $homePage->id;
    endif; 
else:
        $pageId                 = null;
        $headLine             = "";
        $detail              = "";
        $avatar              = "";
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
                        <input type="hidden" name="pageId" value="{{$pageId}}" />
                    <div class="mb-3">
                        <label for="headLine">Headline</label>
                        <input type="text" name="headLine" class="form-control" placeholder="Enter the headline" value="{{ $headLine }}">
                    </div>
                    <div class="mb-4">
                        <label for="detail">Details</label>
                        <textarea name="detail" class="form-control" placeholder="Enter description about institute">{{ $detail }}</textarea>
                    <div class="mt-4">
                    <label for="avatar">Avatar (150px X 150px)</label>
                         @if(!empty($avatar))
                            <div>
                                <img src="{{ asset('public/upload/image/webHomepage/').'/'.$avatar }}" class="w-50" >
                                <a href="{{ route('delSliderImg',['id'=>$itemId]) }}" class="fw-bold text-danger">Delete</a>
                            </div>
                            @else
                                <input type="file" name="avatar" class="form-control-file" />
                            @endif
                    </div>
                    <div class="mt-5 ">
                        <button class="btn btn-success btn-lg mx-2" type="submit">Save</button>
                        <a class="btn btn-primary btn-lg mx-2" href="{{ route('sliderInfo') }}">New Slider</a>
                    </div>
                </form>
            </div>
        </div>
    <div class="col-12 mt-5">
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
                        <td style="width:30%"><img class="w-100" src="{{ asset('/public/upload/image/webHomepage/').'/'.$item->avatar}}" alt="{!! $item->headLine !!}" style="max-height: 120px !important;" /></td>
                        <td>{{ $item->headLine }}</td>
                        <td>{{ $item->detail }}</td>
                        <td>
                            <a href="{{ route('editSlider',['id'=>$item->id]) }}"><i class="fa-solid fa-pen-to-square mx-2" style="color: #4125b1;"></i></a>
                            <a href="{{ route('delSlider',['id'=>$item->id]) }}" onclick="return confirm('Are you sure you want to delete this item?');" title="Get Id Card"><i class="fa-solid fa-trash mx-2" style="color: #c10b26;"></i></a>
                        </td>
                    </tr>
                    @endforeach 
                    @else
                    <tr>
                        <div>no data found</div>
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