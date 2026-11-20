@extends('layouts/contentNavbarLayout')

@section('title', 'Employee - Form')

@section('styles')
  @vite('resources/css/datatables.css')
@endsection

@section('content')
<div class="row">
  <div class="card">
    <h5 class="card-header mb-1">
      <div class="col-sm-12">Employee List</div>
      <div class="d-flex justify-content-end">
        <a href="{{ route('employee.create') }}" class="btn btn-primary">Add Employee</a>
      </div>
    </h5>
    <div class="table-responsive">
      <table id="datatable-responsive" class="table table-striped table-bordered datatable-responsive">
        <thead>
          <tr>
            <th width="5%">Action</th>
            <th width="10%">NIK</th>
            <th width="15%">Name</th>
            <th width="15%">Phone Number</th>
            <th>Email</th>
            <th>Departement</th>
            <th>Departement Tags</th>
            <th>Position</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody class="table-border-bottom-0">
          @if(!empty($employees))
            @foreach ( $employees as $employee )
            <tr>
                <td>
                  <a href="{{ route('employee.edit', $employee->id) }}" class="btn-sm action-btn" data-bs-placement="top" data-bs-toggle="tooltip" title="Edit"><i class="ri-pencil-line me-1"></i></a>
                  <a href="{{ route('employee.destroy', $employee->id) }}" class="btn-sm action-btn" data-bs-placement="top" data-bs-toggle="tooltip" title="Delete" data-confirm-delete="true"><i class="ri-delete-bin-6-line me-1" style="color: red;"></i></a>
                <td>
                    <a href="{{ route('employee.show', $employee->id) }}" class="text-underline-hover" data-bs-placement="right" data-bs-toggle="tooltip" title="View">{{ $employee->nik }}</a>
                </td>
                <td>{{ $employee->name }}</td>
                <td>{{ $employee->phone_number }}</td>
                <td>{{ $employee->email }}</td>
                <td>{{ optional($employee->departements)->name ?? '-' }}</td>
                <td>{{ $employee->department_names }}</td>
                <td>{{ optional($employee->positions)->name ?? '-' }}</td>
                <td>{{ $employee->status ? 'Active' : 'In active' }}</td>
            </tr>
            @endforeach
          @endif
        </tbody>
      </table>

      <!-- /upload modal -->

    </div>
  </div>
</div>

@endsection

@section('scripts')
  @vite('resources/js/datatables.js')
  <script>
    // Wait for DOM to be ready
    document.addEventListener('DOMContentLoaded', function () {
        // Attach event listener to modal
        const myModal = document.getElementById('myModal');

        if (myModal) {
            myModal.addEventListener('shown.bs.modal', function () {
                // Call your function here
                myCustomFunction();
            });
        }

        // Your custom function
        function myCustomFunction() {
          alert('dfdfdfd')
          console.log('Modal is now shown!');
            // You can do AJAX calls, DOM updates, etc.
        }
    });
  </script>

  <!-- delete modal -->
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      // Attach event listener to modal
      const myModal = document.getElementById('deleteConfirmModal');
      if (myModal) {
          myModal.addEventListener('show.bs.modal', function () {
            const button = event.relatedTarget;
            const id = button.getAttribute('data-bs-id');

            var modal = $(this)
            modal.find('.modal-footer a').attr('href', '/employee/delete/' + id);
          });
      }
    });
  </script>
  <!-- /delete modal -->
@endsection
