@extends('cultivation.include')
@section('backTitle')
User List
@endsection
@section('backIndex')
<!-- Dashboard summary Start Here -->
<div class="row gutters-20 mb-4">
	<div class="col-md-10 col-12 mx-auto">
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
								<th>Admin Name</th>
								<th>User Mobile</th>
								<th>User Email</th>
								<th>Assigned Subjects</th>
								<th>Attendance Class</th>
								<th>User Type</th>
								<th>Action</th>
							</tr>
						</thead>
						<tbody>
							@forelse($userList as $key => $user)
								@php
									$subjectNames = $user->subjects->pluck('subjectName')->filter()->implode(', ');
									$attendanceClass = $user->primaryClass
										? trim($user->primaryClass->className . ($user->primarySection ? ' / ' . $user->primarySection->section : ''))
										: 'None';
								@endphp
								<tr>
									<td>{{ $key+1 }}</td>
									<td>{{ $user->adminName }}</td>
									<td>{{ $user->adminMobile }}</td>
									<td>{{ $user->adminMail }}</td>
									<td>{{ $subjectNames !== '' ? $subjectNames : 'None' }}</td>
									<td>{{ $attendanceClass !== '' ? $attendanceClass : 'None' }}</td>
									<td>
										@if($user->userType == 1)
											Teacher Admin
										@elseif($user->userType == 2)
											Cash Admin
										@elseif($user->userType == 3)
											General Admin
										@else
											Super Admin
										@endif
									</td>
									<td>
										<a href="{{ route('editUser', $user->id) }}" class="btn btn-sm btn-warning">Edit</a>
										<form action="{{ route('deleteUser', $user->id) }}" method="get" style="display:inline-block;">
											@csrf
											@method('DELETE')
											<button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">Delete</button>
										</form>
									</td>
								</tr>
							@empty
								<tr>
									<td colspan="8" class="text-center">No users found.</td>
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
			pageLength: 10,
			lengthMenu: [10, 25, 50, 100],
			order: [[1, 'asc']],
			columnDefs: [
				{ targets: 0, orderable: false, searchable: false },
				{ targets: 7, orderable: false, searchable: false }
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
