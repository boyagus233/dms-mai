@extends('layouts/contentNavbarLayout')

@section('title', 'Document Upload - Form')

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
      <div class="col-sm-12">Document List</div>
      <div class="d-flex justify-content-end">
        <a href="{{ route('document-upload.create') }}" class="btn btn-primary">Add Document</a>
      </div>
    </h5>
    <div class="table-responsive">
      <table id="datatable-responsive" class="table table-striped table-bordered datatable-responsive">
        <thead>
          <tr>
            <th width="5%">Action</th>
            <th>Document No</th>
            <th>Document Name</th>
            <th>Document Level</th>
            <th>Management Process</th>
            <th>1st Assignment</th>
            <th>2nd Assignment</th>
            <th>Status</th>
            <th>Download</th>
          </tr>
        </thead>
        <tbody class="table-border-bottom-0">
          @if(!empty($documentUploads))
            @foreach ( $documentUploads as $document )
            <tr>
                <td>
                  <a href="{{ route('document-upload.edit', $document->id) }}" class="btn-sm action-btn" data-bs-placement="top" data-bs-toggle="tooltip" title="Edit"><i class="ri-pencil-line me-1"></i></a>
                  <a href="{{ route('document-upload.destroy', $document->id) }}" class="btn-sm action-btn" data-bs-placement="top" data-bs-toggle="tooltip" title="Delete" data-confirm-delete="true"><i class="ri-delete-bin-6-line me-1" style="color: red;"></i></a>
                <td>
                  <a href="{{ route('document-upload.show', $document->id) }}" class="text-underline-hover" data-bs-placement="right" data-bs-toggle="tooltip" title="View">{{ $document->document_no }}</a>
                </td>
                <td>{{ $document->document_name }}</td>
                <td>{{ optional($document->documentLevel)->code ?? '-' }}</td>
                <td>{{ optional($document->managementProcess)->code ?? '-' }}</td>
                <td>{{ optional($document->employee)->name ?? '-' }}</td>
                <td>{{ optional($document->employee2nd)->name ?? '-' }}</td>
                <td>{{ $document->document_status }}</td>
                <td>
                    {!! $document->file_name ? '<a href="' . asset('storage/documents/' . $document->file_name) . '" download="' . $document->document_name . '">Download</a>' : '' !!}
                </td>
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
