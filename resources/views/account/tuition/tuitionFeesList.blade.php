@extends('account.include')
@section('backTitle')
Tuition Fee List
@endsection
@section('backIndex')
<div class="row gutters-20 mb-4">
    <div class="col-10 mx-auto">
        <div class="row">
            <div class="card card-body  border  ">
                <form method="POST" action="{{ route('bulkDeleteTuitionFees') }}" id="bulkDeleteForm">
                    @csrf
                    <div class="d-flex justify-content-between mb-2">
                        <div>
                            <button type="submit" class="btn btn-danger btn-sm" id="bulkDeleteBtn" disabled onclick="return confirm('Delete selected records?')">Delete Selected</button>
                        </div>
                        <div class="small text-muted">Select multiple fee records to delete in one action.</div>
                    </div>
                <table id="myTable" class="table table-striped table-hover shadow-lg p-3 rounded" >
                    <thead class="table-info">
                        <tr>
                        <th><input type="checkbox" id="selectAll"></th>
                        <th>Date</th>
                        <th>Student ID</th>
                        <th>Fees Type</th>
                        <th>Amount</th>
                        <th>Action</th>
                        </tr>
                    </thead>
                    <tbody class="">
                        @if(!empty($tfd) && count($tfd)>0)
                        @foreach($tfd as $tfdData)
                        @php
                            $feesData = \App\Models\feesManager::find($tfdData->feesType);
                            if(!empty($feesData)):
                                $feesName = $feesData->feesName;
                            else:
                                $feesName="-";
                            endif;
                        @endphp
                        <tr>
                            <td><input type="checkbox" class="row-check" name="feeIds[]" value="{{ $tfdData->id }}"></td>
                            <td>{{ $tfdData->created_at->format('Y-m-d') }}</td>
                            <td>{{ $tfdData->stdId }}</td>
                            <td>{{ $feesName }}</td>
                            <td>{{ $tfdData->amount }}</td>
                            <td>
                                <a href="{{route('tuitionReport',['id'=>$tfdData->id])}}"><i class="fa-duotone fa-solid fa-print mx-2" style="color:rgb(0 0 0 );"></i></a>
                                <a href="{{route('tuitionFeeView',['id'=>$tfdData->id])}}"><i class="fa-solid fa-eye mx-2" style="color:rgb(35 170 211);"></i></a>
                                <a href="{{route('editTuitionFee',['id'=>$tfdData->id])}}"><i class="fa-solid fa-pen-to-square mx-2" style="color: #4125b1;"></i></a>
                                <a onclick="confirm('are you sure')" href="{{route('dltTuitionFee',['id'=>$tfdData->id])}}"><i class="fa-solid fa-trash mx-2" style="color: #c10b26;"></i></a>
                            </td>
                        </tr>
                        @endforeach
                        @endif
                    </tbody>
                </table>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function(){
    const selectAll = document.getElementById('selectAll');
    const checks = document.querySelectorAll('.row-check');
    const btn = document.getElementById('bulkDeleteBtn');
    function updateBtn(){
        const any = Array.from(document.querySelectorAll('.row-check:checked')).length>0;
        btn.disabled = !any;
    }
    if(selectAll){
        selectAll.addEventListener('change', function(){
            const checked = this.checked;
            document.querySelectorAll('.row-check').forEach(c=>{c.checked=checked;});
            updateBtn();
        });
    }
    checks.forEach(c=> c.addEventListener('change', updateBtn));
});
</script>
@endsection