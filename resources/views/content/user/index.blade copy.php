@extends('layouts/contentNavbarLayout')

@section('title', 'User - Form')

  <!-- Datatables -->
  <link href="cdn.datatables.net/1.10.20/css/jquery.dataTables.min.css">
  <link href="/vendor/datatables.net-bs/css/dataTables.bootstrap.min.css" rel="stylesheet">

@section('page-script')
{{-- @vite('resources/assets/js/form-basic-inputs.js') --}}


@endsection

@section('content')

<!-- Basic Bootstrap Table -->
<div class="card">
  <h5 class="card-header">User List</h5>
  <div class="d-flex justify-content-end px-3 pt-3">
    <a href="{{ route('users.add') }}" class="btn btn-primary">Add User</a>
  </div>
  <div class="table-responsive">
    <table id="datatable-responsive" class="table table-striped table-bordered dt-responsive nowrap">
      <thead>
        <tr>
          <th width="5%">action</th>
          <th>User Name</th>
          <th>Name</th>
          <th>Email</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody class="table-border-bottom-0">
        @if(!empty($users))
          @foreach ( $users as $us )
          <tr>
              <td>
                  <a href="{{ route('users.edit', ['id' => $us->id]) }}" class="btn-sm action-btn" data-placement="top" data-toggle="tooltip" title="Edit"><i class="ri-pencil-line me-1"></i></a>
                  <span data-placement="top" data-toggle="tooltip" title="Delete">
                      <a class="btn-sm action-btn" href="#" data-toggle="modal" data-target="#deleteConfirmModal" data-userid="{{ $us->id }}" data-username="{{ $us->user_name }}"><i class="ri-delete-bin-6-line me-1" style="color: red;"></i></a>
                  </span>
                </td>
              <td>
                  <a href="{{ route('users.view', ['id' => $us->id]) }}" class="text-underline-hover" data-placement="right" data-toggle="tooltip" title="View">{{ $us->user_name }}</a>
              </td>
              <td>{{ $us->name }}</td>
              <td>{{ $us->email }}</td>
              <td>{{ $us->is_active ? 'Active' : 'In active' }}</td>
          </tr>
          @endforeach
        @endif
      </tbody>
    </table>
  </div>
</div>
<!--/ Basic Bootstrap Table -->
@endsection

@push('scripts')
  <!-- Datatables -->
  <script src="/vendor/datatables.net/js/jquery.dataTables.min.js"></script>
  <script src="/vendor/datatables.net-bs/js/dataTables.bootstrap.min.js"></script>

  <!-- data tables -->
  <script>
    var table = $('#datatable-responsive').DataTable({
    });

  </script>
@endpush
