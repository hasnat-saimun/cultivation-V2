<h5>Failure Subject Count Summary</h5>
<div class="mb-2"><strong>{{ $summaryView['failureSummaryLine'] }}</strong></div>
<table class="summary-table"><thead><tr><th>Category</th><th>Students</th></tr></thead><tbody>
@forelse($failureBuckets as $failed => $students)<tr><td>{{ $failed }} Subject{{ $failed === 1 ? '' : 's' }}</td><td>{{ $students }}</td></tr>
@empty<tr><td colspan="2">No failed-subject bucket found.</td></tr>@endforelse
</tbody></table>
<h5>GPA Distribution</h5>
<table class="summary-table"><thead><tr>@foreach($gpaDistribution as $label => $count)<th>{{ $label }}</th>@endforeach</tr></thead><tbody><tr>@foreach($gpaDistribution as $count)<td>{{ $count }}</td>@endforeach</tr></tbody></table>
<h5>Grade Distribution</h5>
<table class="summary-table"><thead><tr>@forelse($gradeDistribution as $label => $count)<th>{{ $label }}</th>@empty<th>No grades</th>@endforelse</tr></thead><tbody><tr>@forelse($gradeDistribution as $count)<td>{{ $count }}</td>@empty<td>0</td>@endforelse</tr></tbody></table>
