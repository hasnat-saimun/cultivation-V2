@extends('cultivation.include')
@section('backTitle')
User List
@endsection
@section('backIndex')
@push('styles')
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.dataTables.min.css" />
<style>
	#registeredUsersTable thead th {
		background: #1f3c88;
		color: #ffffff;
	}

	#registeredUsersTable.dataTable thead .sorting:before,
	#registeredUsersTable.dataTable thead .sorting:after,
	#registeredUsersTable.dataTable thead .sorting_asc:before,
	#registeredUsersTable.dataTable thead .sorting_asc:after,
	#registeredUsersTable.dataTable thead .sorting_desc:before,
	#registeredUsersTable.dataTable thead .sorting_desc:after {
		color: #ffffff;
		opacity: 1;
	}

	.assignment-item {
		margin-bottom: 0.25rem;
	}

	.assignment-item:last-child {
		margin-bottom: 0;
	}

	.assignment-label {
		font-weight: 700;
	}

	.assignment-subject {
		display: inline;
	}
</style>
@endpush
<!-- Dashboard summary Start Here -->
<div class="row gutters-20 mb-4">
	<div class="col-md-11 col-12 mx-auto">
		<div class="card">
			<div class="card-header bg-light">
				<a href="{{ route('userType') }}" class="btn btn-primary"> Add New User</a>
			</div>
			<div class="card-header">
				<i class="fa-duotone fa-users"></i> Registered Users
			</div>
			<div class="card-body cultivation">
				@if(session()->has('success'))
					<div class="alert alert-success w-100">
						{{ session()->get('success') }}
					</div>
				@endif
				@if(session()->has('error'))
					<div class="alert alert-warning w-100">
						{{ session()->get('error') }}
					</div>
				@endif
				<div class="table-responsive">
					<table class="table table-bordered table-striped" id="registeredUsersTable">
						<thead class="thead-dark">
							<tr>
								<th>#</th>
								<th>Name</th>
								<th>Username</th>
								<th>Type of User</th>
								<th>Assigned Subjects by Class</th>
								<th>Assigned Attendance Class with Section</th>
								<th>Action</th>
							</tr>
						</thead>
						<tbody>
							@forelse($userList as $key => $user)
								@php
									$subjectAssignments = collect($user->subject_assignment_summary ?? []);
									$attendanceClass = $user->primaryClass
										? trim($user->primaryClass->className . ($user->primarySection ? ' / ' . $user->primarySection->section : ''))
										: 'None';
									$assignmentExportText = $subjectAssignments
										->map(function ($item) {
											$subjectItems = collect((array) ($item['subject_items'] ?? []));
											$subjects = $subjectItems->isNotEmpty()
												? $subjectItems->pluck('name')->filter()->implode(', ')
												: implode(', ', (array) ($item['subjects'] ?? []));
											return trim((string) ($item['label'] ?? '')) . ': ' . $subjects;
										})
										->filter()
										->implode(' | ');
								@endphp
								<tr>
									<td>{{ $key+1 }}</td>
									<td>{{ $user->adminName }}</td>
									<td>{{ $user->adminUser }}</td>
									<td>{{ $user->user_type_label }}</td>
									<td data-export="{{ $assignmentExportText !== '' ? $assignmentExportText : 'None' }}">
										@if($subjectAssignments->isNotEmpty())
											@foreach($subjectAssignments as $assignment)
												@php
													$subjectItems = collect((array) ($assignment['subject_items'] ?? []));
													$subjectDisplay = $subjectItems->isNotEmpty()
														? $subjectItems->map(function ($subject) {
															return [
																'id' => (int) ($subject['id'] ?? 0),
																'name' => trim((string) ($subject['name'] ?? '')),
																'sources' => implode(',', (array) ($subject['sources'] ?? [])),
															];
														})
														->filter(fn ($subject) => $subject['id'] > 0 && $subject['name'] !== '')
														->values()
														: collect((array) ($assignment['subjects'] ?? []))->map(function ($name) {
															return [
																'id' => 0,
																'name' => trim((string) $name),
																'sources' => '',
															];
														})->filter(fn ($subject) => $subject['name'] !== '')->values();
												@endphp
												<div class="assignment-item">
													<span class="assignment-label">{{ $assignment['label'] ?? 'Assignment' }}:</span>
													@foreach($subjectDisplay as $index => $subject)
														<span
															class="assignment-subject"
															@if(($subject['id'] ?? 0) > 0)
																data-subject-id="{{ $subject['id'] }}"
															@endif
															@if(($subject['sources'] ?? '') !== '')
																data-subject-sources="{{ $subject['sources'] }}"
															@endif
														>{{ $subject['name'] }}</span>@if($index < $subjectDisplay->count() - 1), @endif
													@endforeach
												</div>
											@endforeach
										@else
											None
										@endif
									</td>
									<td>{{ $attendanceClass !== '' ? $attendanceClass : 'None' }}</td>
									<td>
										<a href="{{ route('editUser', $user->id) }}" class="btn btn-sm btn-warning">Edit</a>
										<form action="{{ route('deleteUser', $user->id) }}" method="POST" style="display:inline-block;">
											@csrf
											@method('DELETE')
											@csrf
											@method('DELETE')
											<button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">Delete</button>
										</form>
									</td>
								</tr>
							@empty
								<tr>
									<td colspan="7" class="text-center">No users found.</td>
								</tr>
							@endforelse
						</tbody>
					</table>
				</div>
			</div>
		</div>
	</div>
</div>
<!-- Dashboard summary End Here -->
@push('scripts')
<script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>
<script>
(function(){
	function initRegisteredUsersTable(){
		if (!window.jQuery || !jQuery.fn || !jQuery.fn.DataTable) { return false; }
		var $ = jQuery;
		var $table = $('#registeredUsersTable');
		if (!$table.length || $.fn.dataTable.isDataTable($table)) {
			return true;
		}

		var table = $table.DataTable({
			dom: 'Bfrtip',
			buttons: [
				{
					extend: 'print',
					text: 'Print',
					exportOptions: {
						columns: [0,1,2,3,4,5],
						format: {
							body: function (data, row, column, node) {
								if (node && node.dataset && node.dataset.export) {
									return node.dataset.export;
								}
								return typeof data === 'string' ? data.replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim() : data;
							}
						}
					}
				},
				{
					extend: 'pdfHtml5',
					text: 'PDF',
					exportOptions: {
						columns: [0,1,2,3,4,5],
						format: {
							body: function (data, row, column, node) {
								if (node && node.dataset && node.dataset.export) {
									return node.dataset.export;
								}
								return typeof data === 'string' ? data.replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim() : data;
							}
						}
					}
				},
				{
					extend: 'excelHtml5',
					text: 'Excel',
					exportOptions: {
						columns: [0,1,2,3,4,5],
						format: {
							body: function (data, row, column, node) {
								if (node && node.dataset && node.dataset.export) {
									return node.dataset.export;
								}
								return typeof data === 'string' ? data.replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim() : data;
							}
						}
					}
				}
			],
			pageLength: 10,
			lengthMenu: [10, 25, 50, 100],
			order: [[1, 'asc']],
			columnDefs: [
				{ targets: 0, orderable: false, searchable: false },
				{ targets: 6, orderable: false, searchable: false }
			],
			language: {
				emptyTable: 'No users found.',
				search: 'Search:',
				lengthMenu: 'Show _MENU_ entries'
			},
			drawCallback: function(){
				var api = this.api();
				var pageInfo = api.page.info();
				api.column(0, { page: 'current' }).nodes().each(function(cell, index){
					cell.innerHTML = pageInfo.start + index + 1;
				});
			}
		});

		table.draw(false);
		return true;
	}

	if (!initRegisteredUsersTable()) {
		document.addEventListener('DOMContentLoaded', initRegisteredUsersTable);
	}
})();
</script>
@endpush
@endsection
