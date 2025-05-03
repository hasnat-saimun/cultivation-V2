@extends('account.include')
@section('backTitle')
Calculas View
@endsection
@section('backIndex')
<div class="row gutters-20 mb-4">
    <div class="col-10 mx-auto">
        <div class="row col-md-6 mx-auto">
            <div class="card card-body  border  ">
                
            <div class="mb-3 text-center">
            <u class="h3">Calculas View</u>
            </div>
                <table class=" table table-striped table-hover hadow-lg p-3 rounded" >
                    <tbody class="">
                        <tr>
                            <th scope="col">Date</th>
                            <td>{{$singleData->created_at->format('Y-m-d')}}</td>
                        </tr>
                        <tr>
                            <th scope="col">Source</th>
                            <td>{{$singleData->source}}</td>
                        </tr>
                        <tr>
                            <th scope="col">Amount</th>
                            <td>{{$singleData->amount}}</td>
                        </tr>
                        <tr>
                            <th scope="col">Type Of Transaction</th>
                            <td>{{$singleData->transaction}}</td>
                        </tr>
                    </tbody>
                </table>
                <div class="mt-3">
                    <a href="{{route('reportListView')}}"class="btn-fill-lg btn-gradient-yellow btn-hover-bluedark">Back</a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection