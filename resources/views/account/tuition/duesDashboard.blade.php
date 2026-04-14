@extends('account.include')
@section('backTitle')
Student Dues Dashboard
@endsection
@section('backIndex')
<div class="row gutters-20 mb-4">
    <div class="col-11 mx-auto">
        <div class="card card-body border">
            <h4 class="mb-3">Month-wise Student Dues Dashboard</h4>
            @if(!empty($isTeacher) && $isTeacher)
            <div class="p-2 alert-info">
                Class teacher mode is active. You are seeing only your assigned class/section students.
                <div class="mt-2">
                    <strong>Assigned Classes:</strong>
                    {{ !empty($teacherScope['classNames']) ? implode(', ', $teacherScope['classNames']) : 'Not assigned' }}
                </div>
                <div>
                    <strong>Assigned Sections:</strong>
                    @if(!empty($teacherScope['hasSectionRestriction']))
                        {{ !empty($teacherScope['sectionNames']) ? implode(', ', $teacherScope['sectionNames']) : 'Not assigned' }}
                    @else
                        All sections of assigned classes
                    @endif
                </div>
            </div>
            @endif

            <form method="GET" action="{{ route('duesDashboard') }}" class="mb-3">
                <div class="row">
                    <div class="col-md-2 mb-2">
                        <label class="form-label">Class</label>
                        <select class="form-control" name="classId">
                            <option value="">All</option>
                            @foreach($classData as $cls)
                                <option value="{{ $cls->id }}" {{ (string)($filters['classId'] ?? '') === (string)$cls->id ? 'selected' : '' }}>{{ $cls->className }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2 mb-2">
                        <label class="form-label">Session</label>
                        <select class="form-control" name="sessionId">
                            <option value="">All</option>
                            @foreach($sessionData as $sess)
                                <option value="{{ $sess->id }}" {{ (string)($filters['sessionId'] ?? '') === (string)$sess->id ? 'selected' : '' }}>{{ $sess->session }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2 mb-2">
                        <label class="form-label">Section</label>
                        <select class="form-control" name="sectionId">
                            <option value="">All</option>
                            @foreach($sectionData as $sec)
                                <option value="{{ $sec->id }}" {{ (string)($filters['sectionId'] ?? '') === (string)$sec->id ? 'selected' : '' }}>{{ $sec->section }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2 mb-2">
                        <label class="form-label">Fee Month</label>
                        <input type="month" class="form-control" name="feeMonth" value="{{ $filters['feeMonth'] ?? '' }}">
                    </div>
                    <div class="col-md-2 mb-2">
                        <label class="form-label">Search</label>
                        <input type="text" class="form-control" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="ID, name, roll">
                    </div>
                </div>
                <div class="mt-2 d-flex" style="gap:8px;">
                    <button class="btn btn-primary" type="submit">Filter</button>
                    <a class="btn btn-secondary" href="{{ route('duesDashboard') }}">Reset</a>
                </div>
            </form>

            <div class="row mb-3">
                <div class="col-md-4 mb-2">
                    <div class="p-2 alert-light border mb-0"><strong>Total Setup Amount:</strong> {{ number_format($summary['total_due'] ?? 0, 2) }}</div>
                </div>
                <div class="col-md-4 mb-2">
                    <div class="p-2 alert-light border mb-0"><strong>Total Collected Amount:</strong> {{ number_format($summary['total_paid'] ?? 0, 2) }}</div>
                </div>
                <div class="col-md-4 mb-2">
                    <div class="p-2 alert-warning border mb-0"><strong>Total Due Amount:</strong> {{ number_format($summary['total_balance'] ?? 0, 2) }}</div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered table-striped table-sm">
                    <thead class="table-info">
                        <tr>
                            <th>Month</th>
                            <th>Student ID</th>
                            <th>Name</th>
                            <th>Roll</th>
                            <th>Class</th>
                            <th>Session</th>
                            <th>Section</th>
                            <th>Fee Type</th>
                            <th>Setup Amount</th>
                            <th>Collected Amount</th>
                            <th>Due Amount</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rows as $row)
                            @php
                                $status = $row->payment_status;
                                if(empty($status)){
                                    if((float)$row->paid_amount <= 0){
                                        $status = 'unpaid';
                                    } elseif((float)$row->paid_amount < (float)$row->due_amount){
                                        $status = 'partial';
                                    } else {
                                        $status = 'paid';
                                    }
                                }
                            @endphp
                            <tr>
                                <td>{{ !empty($row->fee_month) ? \Carbon\Carbon::parse($row->fee_month)->format('M Y') : '-' }}</td>
                                <td>{{ $row->stdId }}</td>
                                <td>{{ trim(($row->fullName ?? '').' '.($row->sureName ?? '')) }}</td>
                                <td>{{ $row->rollNumber }}</td>
                                <td>{{ $row->class_name ?? '-' }}</td>
                                <td>{{ $row->session_name ?? '-' }}</td>
                                <td>{{ $row->section_name ?? '-' }}</td>
                                <td>{{ $row->fee_name ?? ('Fee #'.$row->feesType) }}</td>
                                <td>{{ number_format((float)$row->due_amount, 2) }}</td>
                                <td>{{ number_format((float)$row->paid_amount, 2) }}</td>
                                <td>{{ number_format(max(0, (float)$row->balance_amount), 2) }}</td>
                                <td><span class="badge bg-{{ $status === 'paid' ? 'success' : ($status === 'partial' ? 'warning' : 'secondary') }}">{{ ucfirst($status) }}</span></td>
                                <td>
                                    <a href="{{ route('collectDueForm', ['id' => $row->id]) }}" class="btn btn-sm btn-outline-primary">Collect Due</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="13" class="text-center text-muted">No fee records found for current filters.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
