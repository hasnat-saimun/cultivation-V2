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
                            <th scope="col">Fee Month</th>
                            <td>{{ !empty($singleView->fee_month) ? \Carbon\Carbon::parse($singleView->fee_month)->format('F Y') : '-' }}</td>
                        </tr>
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
                            <th scope="col">Setup Amount</th>
                            <td>{{ number_format((float)($singleView->due_amount ?? $singleView->amount ?? 0), 2) }}</td>
                        </tr>
                        <tr>
                            <th scope="col">Collected Amount</th>
                            <td>{{ number_format((float)($singleView->paid_amount ?? $singleView->amount ?? 0), 2) }}</td>
                        </tr>
                        <tr>
                            <th scope="col">Due Amount</th>
                            <td>{{ number_format(max(0, (float)($singleView->due_amount ?? $singleView->amount ?? 0) - (float)($singleView->paid_amount ?? $singleView->amount ?? 0)), 2) }}</td>
                        </tr>
                        <tr>
                            <th scope="col">Status</th>
                            <td>{{ ucfirst($singleView->payment_status ?? 'unpaid') }}</td>
                        </tr>
                        
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