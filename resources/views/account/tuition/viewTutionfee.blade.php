@extends('account.include')
@section('backTitle')
Institute Info
@endsection
@section('backIndex')
<div class="row gutters-20 mb-4">
    <div class="col-10 mx-auto">
        <div class="row col-md-6 mx-auto">
            <div class="card card-body  border  ">
                
            <div class="mb-3 text-center">
            <u class="h3">View Student Profile</u>
            </div>
                <table class=" table table-striped table-hover hadow-lg p-3 rounded" >
                @php
                    $stdData = \App\Models\newAdmission::where(['stdId'=>$singleView->stdId])->first();
                    $sessionData= \App\Models\sessionManage::find($stdData->sessName);
                    $classData = \App\Models\classManage::find($stdData->className);
                    $sectionData = \App\Models\sectionManage::find($stdData->sectionName);
                @endphp
                
                    <tbody class="">
                        <tr>
                            <th scope="col">Date</th>
                            <td>{{$singleView->created_at}}</td>
                        </tr>
                        
                        <tr>
                            <th scope="col">Session</th>
                            @if(!empty($sessionData))
                            <td>{{$sessionData->session}}</td>
                            @else
                            <td>-</td>
                            @endif
                        
                        </tr>
                        <tr>
                            <th scope="col">Student Name</th>
                            <td>{{$stdData->fullName}}</td>
                        </tr>
                        <tr>
                            <th scope="col">Class</th>
                            @if(!empty($sectionData))
                            <td>{{$classData->className}}</td>
                            @else
                            <td>-</td>
                            @endif
                        </tr>
                        <tr>
                            <th scope="col">Section</th>
                            @if(!empty($sectionData))
                            <td>{{$sectionData->section}}</td>
                            @else
                            <td>-</td>
                            @endif
                        </tr>
                        <tr>
                            <th scope="col">Roll Number</th>
                            <td>{{$stdData->rollNumber}}</td>
                        </tr>
                        <tr>
                            <th scope="col">Amount</th>
                            <td>{{$singleView->amount}}
                        
                    </tbody>
                </table>
                <div class="mt-3">
                    <a href="{{route('tuitionFeeList')}}"class="btn-fill-lg btn-gradient-yellow btn-hover-bluedark">Back</a>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection