@extends('account.include')
@section('backTitle')
Report
@endsection
@section('backIndex')
<div class="row gutters-20 mb-4">
    <div class="col-10 mx-auto">
        <div class="row" id="report">
            @if(!empty($singleView))
                @php
                    $stdData = \App\Models\newAdmission::where(['stdId'=>$singleView->stdId])->first();
                    if(!empty($stdData)):
                        $sessionData= \App\Models\sessionManage::find($stdData->sessName);
                        $classData = \App\Models\classManage::find($stdData->className);
                        $sectionData = \App\Models\sectionManage::find($stdData->sectionName);
                    endif;
                @endphp
                @if(!empty($stdData))
                <div class="receipt-main col-8 mx-auto">
                    <div class="receipt-header row">
                        <div class="col-xs-12 col-sm-12 col-md-12 text-center mb-3">
                            <div class="receipt-right">
                                <h5>Sonar Bangla College</h5>
                                <p>Gubinathpur,Burichong,Comilla</p>
                                <p> <i class="fa fa-phone"></i> +800 17550-48017</p>
                                <p> <i class="fa fa-envelope-o"></i> sonarbangla003@gmail.com</p>
                            </div>
                        </div>
                    </div>
                    <div class="receipt-header receipt-header-mid row">
                        <div class="col-xs-8 col-sm-8 col-md-8 text-left">
                            <div class="receipt-right">
                                <h5>{{$stdData->fullName}}</h5>

                                <p><b>Student ID :</b> {{ $stdData->stdId }}</p>

                                <p><b>Roll : </b> {{$stdData->rollNumber}}</p>

                                @if(!empty($classData))
                                <p><b>Class : </b> {{$classData->className}}</p>
                                @else
                                <p><b>Class : </b> -</p>
                                @endif

                                @if(!empty($sectionData))
                                <p><b>Section : </b> {{$sectionData->section}}</p>
                                @else
                                <p><b>Section : </b> -</p>
                                @endif

                                @if(!empty($sessionData))
                                <p><b>Session : </b> {{$sessionData->session}}</p>
                                @else
                                <p><b>Session : </b> -</p>
                                @endif
                            </div>
                        </div>
                        <div class="col-xs-4 col-sm-4 col-md-4">
                            <div class="receipt-left">
                                <h3>INVOICE # {{ $singleView->id }}</h3>
                                <p><b>Date : </b> {{$singleView->created_at->format('d-M-Y')}}</p>
                                <p><b>Mobile : </b> {{ $stdData->phone }}</p>
                                <p><b>Email : </b> {{ $stdData->mail }}</p>
                            </div>
                        </div>
                    </div>
                    @php
                        $amount = $singleView->amount;
                        $vat = ($singleView->amount*10)/100;
                        $totalAmount = $amount+$vat;
                    @endphp
                    <div class="mb-4">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Description</th>
                                    <th>Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                            @php
                                $feesData = \App\Models\feesManager::find($singleView->feesType);
                                if(!empty($feesData)):
                                    $feesName = $feesData->feesName;
                                else:
                                    $feesName="-";
                                endif;
                            @endphp
                                <tr>
                                    <td class="col-md-9">{{ $feesName }}</td>
                                    <td class="col-md-3"> {{ $amount }}/-</td>
                                </tr>
                                <tr>
                                    <td class="text-end">
                                        <p>
                                            <strong>Amount: </strong>
                                        </p>
                                        <p>
                                            <strong>Vat(10%): </strong>
                                        </p>
                                    </td>
                                    <td>
                                        <p>
                                            <strong> {{ $amount }}/-</strong>
                                        </p>
                                        <p>
                                            <strong> {{ $vat }}/-</strong>
                                        </p>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="text-right">
                                        <h2><strong>Total: </strong></h2>
                                    </td>
                                    <td class="text-left text-danger">
                                        <h2>
                                            <strong> {{ $totalAmount }}/-</strong>
                                        </h2>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="receipt-header receipt-header-mid receipt-footer row ">
                            <div class="col-xs-6 col-sm-6 col-md-6 text-start mt-5">
                                    <p><u>Gurdian Sign</u></p>
                            </div>
                            <div class="col-xs-6 col-sm-6 col-md-6 text-end mt-5">
                                    <p><u>Cash Incharge</u></p>
                            </div>
                    </div>
                    <div class="row text-center">
                        <div class=" col-2  d-grid gap-2 mt-5">
                        <button class="btn btn-success btn-lg my-4 d-print-none" onclick="printDiv('report')"><i class="fa-regular fa-print"></i> Print</button>
                        </div>
                    </div>
                </div>
                @else
                <div class="alert alert-info">
                    Sorry! No student data found with your query
                </div>
                @endif
            @else
            <div class="alert alert-info">
                Sorry! No data found
            </div>
            @endif
            
        </div>
    </div>
</div>
<script type="text/javascript">
    function printDiv(e){
        var printContents = document.getElementById(e).innerHTML;
        var originalContents = document.body.innerHTML;
        document.body.innerHTML = printContents;
        window.print();
        document.body.innerHTML = originalContents;
    }
</script>
@endsection
