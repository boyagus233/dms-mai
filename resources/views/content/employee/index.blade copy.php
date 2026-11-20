@extends('layouts/contentNavbarLayout')

@section('title', 'Employee - Form')

@section('styles')
  <!-- Datatables -->
  <link href="https://cdn.datatables.net/1.10.20/css/jquery.dataTables.min.css" rel="stylesheet">
  <link href="/vendor/datatables.net-bs/css/dataTables.bootstrap.min.css" rel="stylesheet">
  <link href="/vendor/datatables.net-responsive-bs/css/responsive.bootstrap.min.css" rel="stylesheet">
  <link href="/vendor/datatables.net-scroller-bs/css/scroller.bootstrap.min.css" rel="stylesheet">
@endsection

@section('content')

<div class="row">
  <div class="card">
    <h5 class="card-header mb-1">
      <div class="col-sm-12">Employee List</div>
      <div class="d-flex justify-content-end">
        <a href="{{ route('employee.add') }}" class="btn btn-primary">Add Employee</a>
      </div>
    </h5>
    <div class="table-responsive">
      <table id="datatable-responsive" class="table table-striped table-bordered datatable-responsive">
        <thead>
          <tr>
            <th width="5%">Action</th>
            <th>NIK</th>
            <th>Name</th>
            <th>Phone Number</th>
            <th>Departement</th>
            <th>Position</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody class="table-border-bottom-0">
          @if(!empty($employees))
            @foreach ( $employees as $emp )
            <tr>
                <td>
                  <a href="{{ route('employee.edit', ['id' => $emp->id]) }}" class="btn-sm action-btn" data-bs-placement="top" data-bs-toggle="tooltip" title="Edit"><i class="ri-pencil-line me-1"></i></a>
                  <span data-bs-placement="top" data-bs-toggle="tooltip" title="Delete">
                      <a class="btn-sm action-btn" href="#" data-bs-toggle="modal" data-bs-target="#deleteConfirmModal" data-bs-id="{{ $emp->id }}"><i class="ri-delete-bin-6-line me-1" style="color: red;"></i></a>
                  </span>
                <td>
                    <a href="{{ route('employee.view', ['id' => $emp->id]) }}" class="text-underline-hover" data-placement="right" data-toggle="tooltip" title="View">{{ $emp->nik }}</a>
                </td>
                <td>{{ $emp->name }}</td>
                <td>{{ $emp->phone_number }}</td>
                <td>{{ optional($emp->departements)->name ?? '-' }}</td>
                <td>{{ optional($emp->positions)->name ?? '-' }}</td>
                <td>{{ $emp->status ? 'Active' : 'In active' }}</td>
            </tr>
            @endforeach
          @endif
        </tbody>
      </table>

      <!-- /delete modal -->
      <div id="deleteConfirmModal" name="deleteConfirmModal" class="modal fade bs-example-modal-sm" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-sm">
          <div class="modal-content">
            <div class="modal-header">
              <h4 class="modal-title" id="myModalLabel2"><i class="fa fa-times-circle fa-lg red"></i> <span>Delete</span></h4>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
              </button>
            </div>
            <div class="modal-body">
              <p>Are you sure you want to delete this item?</p>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">Cancel</button>
              <a href="#" type="button" class="btn btn-sm btn-danger">Delete</a>
            </div>
          </div>
        </div>
      </div>
      <!-- /delete modal -->

      <!-- /upload modal -->

    </div>
  </div>
</div>

@endsection

@section('scripts')
  <!-- Datatables -->
  <script src="/vendor/datatables.net/js/jquery.dataTables.min.js"></script>
  <script src="/vendor/datatables.net-bs/js/dataTables.bootstrap.min.js"></script>
  <script src="/vendor/datatables.net-responsive/js/dataTables.responsive.min.js"></script>
  <script src="/vendor/datatables.net-responsive-bs/js/responsive.bootstrap.js"></script>
  <script src="/vendor/datatables.net-scroller/js/dataTables.scroller.min.js"></script>

  <!-- data tables -->
  <script>
    var table = $('#datatable-responsive').DataTable({
      responsive: true,
      paging: true,
      searching: true,
      ordering: true
    });
  </script>

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
