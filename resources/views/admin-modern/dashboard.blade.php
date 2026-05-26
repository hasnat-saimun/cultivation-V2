@extends('admin-modern.layouts.app')

@section('title', 'Dashboard')

@section('content')
    @php
        $present = (int)($summary['present'] ?? 0);
        $absent = (int)($summary['absent'] ?? 0);
        $students = (int)($metrics['students'] ?? 0);
        $teachers = (int)($metrics['teachers'] ?? 0);
        $attendancePercent = (int)($attendanceRate ?? 0);
        $profitLoss = (float)($metrics['monthlyProfitLoss'] ?? 0);
    @endphp

    <x-admin-modern.page-header
        title="Dashboard"
        subtitle="Modern parallel shell using existing dashboard data"
        :breadcrumb="['Home', 'Admin Modern']"
    />

    <div class="am-grid am-grid-4">
        <x-admin-modern.stat-card
            label="Present"
            :value="number_format($present)"
            :hint="$attendancePercent . '% attendance rate'"
            tone="positive"
        />
        <x-admin-modern.stat-card
            label="Absent"
            :value="number_format($absent)"
            :hint="max(0, 100 - $attendancePercent) . '% absent rate'"
            tone="danger"
        />
        <x-admin-modern.stat-card
            label="Students"
            :value="number_format($students)"
            hint="Current total enrolled"
            tone="primary"
        />
        <x-admin-modern.stat-card
            label="Teachers"
            :value="number_format($teachers)"
            hint="Active records"
            tone="primary"
        />
    </div>

    <div class="am-grid am-grid-2">
        <section class="am-panel">
            <div class="am-panel-head">
                <h2>Quick Actions</h2>
            </div>
            <div class="am-btn-row" style="justify-content:flex-start; gap:0.5rem; flex-wrap:wrap;" aria-label="Quick action buttons">
                <a href="{{ route('attendanceIndex') }}" class="am-btn-primary" title="Mark today's attendance">Mark Attendance</a>
                <a href="{{ route('attendanceReport') }}" class="am-btn-outline" title="View attendance report">Attendance Report</a>
                <a href="{{ route('studentList') }}" class="am-btn-outline" title="View student list">Student List</a>
            </div>
            @if(!empty($isTeacher))
                <p class="am-empty-note">Teacher scope active: data reflects your assigned classes.</p>
            @endif
        </section>

        <section class="am-panel">
            <div class="am-panel-head">
                <h2>Monthly Profit/Loss</h2>
            </div>
            <x-admin-modern.stat-card
                label="Current Month"
                :value="'BDT ' . number_format(abs($profitLoss), 2)"
                :hint="$profitLoss < 0 ? 'Negative trend' : 'Positive trend'"
                :tone="$profitLoss < 0 ? 'danger' : 'positive'"
            />
        </section>
    </div>

    <x-admin-modern.table-shell title="Attendance Snapshot">
        <table class="am-table">
            <thead>
                <tr>
                    <th>Metric</th>
                    <th>Value</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Date</td>
                    <td>{{ $today ?? date('Y-m-d') }}</td>
                </tr>
                <tr>
                    <td>Total Attendance Rows</td>
                    <td>{{ number_format((int)($summary['total'] ?? 0)) }}</td>
                </tr>
                <tr>
                    <td>Present</td>
                    <td>{{ number_format($present) }}</td>
                </tr>
                <tr>
                    <td>Absent</td>
                    <td>{{ number_format($absent) }}</td>
                </tr>
            </tbody>
        </table>
    </x-admin-modern.table-shell>
@endsection
