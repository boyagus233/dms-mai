@extends('layouts/contentNavbarLayout')

@section('title', 'Document Upload - Form')

@section('styles')
  @vite('resources/css/datatables.css')
@endsection

@section('content')
<?php
    use App\Enums\DocumentStatus;
?>
<div class="row">
  <div class="card">
    <h5 class="card-header mb-1">
      <div class="col-sm-12">Document List</div>
      <div class="d-flex justify-content-end">
      </div>
    </h5>
    <div class="table-responsive">
      <table id="datatable-responsive" class="table table-striped table-bordered datatable-responsive">
        <thead>
          <tr>
            <th width="5%">Action</th>
            <th>Document No</th>
            <th>Document Name</th>
            <th>Departement</th>
            <th>Departement Distribution</th>
            <th>Revisi</th>
            <th>Creator</th>
            <th>Checker</th>
            <th>Approver</th>
            <th>Document Status</th>
          </tr>
        </thead>
        <tbody class="table-border-bottom-0">
          @if(!empty($documentUploads))
            @foreach ( $documentUploads as $document )
            @php
                $statusLabel = DocumentStatus::from($document->document_status)->label();
              @endphp
            <tr>
                <td>
                  <a href="{{ route('document-acknowledge.show', $document->id) }}" class="btn-sm action-btn" data-bs-placement="top" data-bs-toggle="tooltip" title="Read Document"><i class="ri-eye-off-fill"></i></a>
                <td>
                  <a class="text-underline-hover" data-bs-placement="right" data-bs-toggle="tooltip" title="Read Document">{{ $document->document_no }}</a>
                </td>
                <td>{{ $document->document_name }}</td>
                <td>{{ $document->departement_name }}</td>
                <td>{{ $document->distribution_departement }}</td>
                <td>{{ $document->rev_no }}</td>
                <td>{{ $document->creator_name }}</td>
                <td>{{ $document->checker_name }}</td>
                <td>{{ $document->approver_name }}</td>
                <td>
                  @if ($document->document_status == 'APPROVED')
                    <span class="badge s-APPROVED">Active</span>
                  @else
                    <span>{{ $statusLabel }}</span>
                  @endif
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
