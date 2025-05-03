@extends('academic.include')
@section('backTitle')
Principal Speech
@endsection
@section('backIndex')
@php 
    $institute = \App\Models\PrincipalSpeech::orderBy('id','DESC')->first();
    if(!empty($institute)):
        $speechId           = $institute->id;
        $generalSpeech      = $institute->generalSpeech;
        $importantSpeech    = $institute->importantSpeech;
    else:
        $speechId           = null;
        $importantSpeech    = "";
        $generalSpeech      = "";
    endif;
@endphp
<!-- Dashboard summery Start Here -->
<div class="row gutters-20 mb-4">
    <div class="col-10 mx-auto">
        <div class="card">
            <div class="card-header">Principal Speech</div>
            <div class="card-body cultivation">
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
                <form action="{{ route('savePrincipalSpeech') }}" class="form" method="POST" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="speechId" value="{{ $speechId }}">
                    <div class="mb-3">
                        <label for="importantSpeech">Important Speech</label>
                        <input type="text" name="importantSpeech" class="form-control" placeholder="Enter important speech of principal" value="{{ $importantSpeech }}">
                    </div>
                    <div class="mb-3">
                        <label for="generalSpeech">General Speech</label>
                        <textarea name="generalSpeech" class="form-control" placeholder="Enter general speech of principal">{{ $generalSpeech }}</textarea>
                    </div>
                    <div class="mb-3">
                        <button class="btn btn-success btn-lg" type="submit">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<!-- Dashboard summery End Here -->
@endsection