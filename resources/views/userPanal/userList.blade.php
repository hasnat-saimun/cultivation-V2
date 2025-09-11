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
					<table class="table table-bordered table-striped">
						<thead class="thead-dark">
							<tr>
								<th>#</th>
								<th>Admin Name</th>
								<th>User Mobile</th>
								<th>User Email</th>
								<th>User Type</th>
								<th>Action</th>
							</tr>
						</thead>
						<tbody>
							@forelse($userList as $key => $user)
								<tr>
									<td>{{ $key+1 }}</td>
									<td>{{ $user->adminName }}</td>
									<td>{{ $user->adminMobile }}</td>
									<td>{{ $user->adminMail }}</td>
									<td>
										@if($user->userType == 1)
											Teacher Admin
										@elseif($user->userType == 2)
											Cash Admin
										@elseif($user->userType == 3)
											General Admin
										@else
											N/A
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
@endsection
