@extends('account.include')
@section('backTitle')
Report Into Date
@endsection
@section('backIndex')
<div class="row gutters-20 mb-4">
    <div class="col-10 mx-auto">
        <div class="row ">
            <div class="col-8 mx-auto  ">
                <div class="card shadow  p-2 border-0 ">
                @if(session()->has('error'))
                    <div class="alert alert-danger">
                        {{session()->get('error')}}
                    </div>
                @endif
                @if(session()->has('success'))
                    <div class="alert alert-success">
                        {{session()->get('success')}}
                    </div>
                @endif
                    <form method="POST" class="card-body form form-group" action="{{route('getFeesReport')}}">
                        @csrf
                        <div class="mb-2">
                            <label for="stdId" class="form-label ">Student ID</label>
                            <input type="number" class="form-control form-control-sm" id="stdId" name="stdId" placeholder="Enter student ID to get report" required>
                            </select>
                        </div>
                        <div class="mb-2">
                            <label for="fromDate" class="form-label ">Form Date</label>
                            <input type="date" class="form-control form-control-sm" id="fromDate" name="fromDate" placeholder="" required>
                            </select>
                        </div>
                        <div class="mb-2">
                            <label for="toDate" class="form-label ">To Date</label>
                            <input type="date" class="form-control form-control-sm" id="toDate" name="toDate" placeholder="" required>
                            </select>
                        </div>
                        <div class=" row   mt-5">
                            <div class="col-6">
                                <button class="btn-fill-lg btn-gradient-yellow btn-hover-bluedark" type="submit">Generate Report</button></div>
                            <div class="col-2">
                                <button class="btn-fill-lg bg-blue-dark btn-hover-bluedark" type="reset">Reset</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>  
@endsection