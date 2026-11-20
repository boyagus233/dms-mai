@extends('layouts/contentNavbarLayout')

@section('title', 'Document Upload - Form')

@section('styles')
  <!-- Datatables -->
  <link href="https://cdn.datatables.net/1.10.20/css/jquery.dataTables.min.css" rel="stylesheet">
  <link href="/vendor/datatables.net-bs/css/dataTables.bootstrap.min.css" rel="stylesheet">
  <link href="/vendor/datatables.net-responsive-bs/css/responsive.bootstrap.min.css" rel="stylesheet">
  <link href="/vendor/datatables.net-scroller-bs/css/scroller.bootstrap.min.css" rel="stylesheet">
  <style>
    .badge{display:inline-block;padding:.2rem .5rem;border-radius:999px;font-size:12px;border:1px solid #ddd}

    /* Status-specific styles */
    .s-CANCELLED {
      background-color: #007bff; /* Blue */
    }
    .s-BOOKED {
      background-color: #6c757d; /* Gray */
    }
    .s-APPROVED {
      background-color: #28a745; /* Green */
    }
    .s-REJECTED {
      background-color: #dc3545; /* Red */
    }
    .s-PENDING {
      background-color: #ffc107; /* Yellow */
      color: #212529;
    }
    .s-CANCELLED {
      background-color: #007bff; /* Blue */
    }
    .s-CHECK-BY-DC {
      background-color: #007bff; /* Blue */
    }
    .s-REJECT-BY-DC {
      background-color: #f63434; /* Blue */
    }
    .s-PASS-BY-DC {
      background-color: #78f634; /* Blue */
      color: black;
    }
    .s-CHECKED {
      background-color: #0152a8; /* Blue */
    }
    .s-OBSOLETED {
      background-color: #4f0b10; /* Blue */
    }
    .s-CREATED{background:#e8e7e6; color: black;}
    .s-REQUESTED{background:#fff7e6}
  </style>
@endsection

@section('content')
<?php
    use App\Enums\DocumentStatus;
?>
<div class="row">
  <div class="card">
    <h5 class="card-header mb-1">
      @if ($viewtype == 'create')
          <div class="col-sm-12">Document List</div>
      @else
          <div class="col-sm-12">Required Review Document List</div>
      @endif
      <div class="d-flex justify-content-end">
        @if ($viewtype == 'create' && $issadddocument === true)
          <a href="{{ route('document-upload.create',['viewtype' => 'create']) }}" class="btn btn-primary">Add Document</a>
        @endif
      </div>
    </h5>
    <div class="table-responsive">
      <table id="datatable-responsive" class="table table-striped table-bordered datatable-responsive">
        <thead>
          <tr>
            <th width="8%"> Action </th>
            <th>Document No</th>
            <th>Document Name</th>
            <th>Departement</th>
            <th>Departement Distibution</th>
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
                if ($document->is_obsolete===1 && $document->document_status != 'APPROVED') {
                  $statusLabel = 'Obsolete-' . $statusLabel;
                }
              @endphp
            <tr>
                <td width="10%">
                  @if ($document->document_status != 'APPROVED' and $viewtype == 'created')
                    <a href="{{ route('document-upload.destroy', $document->id) }}" class="btn-sm action-btn" data-bs-placement="top" data-bs-toggle="tooltip" title="Delete" data-confirm-delete="true"><i class="ri-delete-bin-6-line me-1" style="color: red;"></i></a>
                  @endif
                  @if ($document->document_status == 'APPROVED' && $document->is_obsolete == 0 && $document->is_processing_revise_obsolete == 0)
                    <a href="{{ route('document-upload.edit', [$document->id, 'action' => 'revise', 'viewtype' => $viewtype]) }}" class="btn-sm action-btn" data-bs-placement="top" data-bs-toggle="tooltip" title="Revise Document"><i class="ri-edit-2-fill me-1" style="color: rgb(154, 43, 43);"></i></a>
                    <a href="{{ route('document-upload.edit', [$document->id, 'action' => 'obsolete', 'viewtype' => $viewtype]) }}" class="btn-sm action-btn" data-bs-placement="top" data-bs-toggle="tooltip" title="Obsolete Document"><i class="ri-archive-2-fill" style="color: rgb(154, 43, 43);"></i></a>
                  @endif
                  @if (($document->document_status == 'CREATED' || $document->document_status == 'EDIT' || $document->document_status == 'REVISE' || $document->document_status == 'REJECT_BY_DC' || $document->document_status == 'REJECT_BY_CHECKER' || $document->document_status == 'REJECT_BY_APPROVER') && $document->is_stamp_by_creator == false)
                    <a href="{{ route('document-upload.edit', [$document->id, 'action' => 'edit', 'viewtype' => $viewtype]) }}" class="btn-sm action-btn" data-bs-placement="top" data-bs-toggle="tooltip" title="Edit Document"><i class="ri-pencil-line me-1" style="color: rgb(64, 0, 255);"></i></a>
                  @endif
                  @if (($document->document_status == 'CREATED' || $document->document_status == 'EDIT' || $document->document_status == 'REVISE') && $document->is_stamp_by_creator == false)
                    <a href="{{ route('document-upload.edit', [$document->id, 'action' => 'stamp', 'viewtype' => $viewtype]) }}" class="btn-sm action-btn" data-bs-placement="top" data-bs-toggle="tooltip" title="Stamp Document"><i class="ri-album-fill"></i></a>
                  @endif
                  @if (($document->document_status == 'CREATED' || $document->document_status == 'OBSOLETE') && $document->is_stamp_by_creator == true && $isdoccontrol === true)
                    <a href="{{ route('document-upload.edit', [$document->id, 'action' => 'doccontrol', 'viewtype' => $viewtype]) }}" class="btn-sm action-btn" data-bs-placement="top" data-bs-toggle="tooltip" title="Check Document"><i class="ri-file-check-fill"></i></a>
                  @endif
                  @if ($document->document_status == 'PASS_BY_DC' && $ischecker === true)
                    <a href="{{ route('document-upload.edit', [$document->id, 'action' => 'check', 'viewtype' => $viewtype]) }}" class="btn-sm action-btn" data-bs-placement="top" data-bs-toggle="tooltip" title="Approve Document"><i class="ri-checkbox-circle-line"></i></a>
                  @endif
                  @if ($document->document_status == 'CHECKED' && $isapprover === true)
                    <a href="{{ route('document-upload.edit', [$document->id, 'action' => 'approve', 'viewtype' => $viewtype]) }}" class="btn-sm action-btn" data-bs-placement="top" data-bs-toggle="tooltip" title="Approve Document"><i class="ri-checkbox-circle-line"></i></a>
                  @endif
                </td>
                <td>
                  <a href="{{ route('document-upload.view', [$document->id, 'viewtype' => $viewtype]) }}" class="text-underline-hover" data-bs-placement="right" data-bs-toggle="tooltip" title="View">{{ $document->document_no }}</a>
                </td>
                <td>{{ $document->document_name }}</td>
                <td>{{ $document->departement_name }}</td>
                <td>{{ $document->distribution_departement }}</td>
                <td>
                    {{ $document->rev_no }}
                </td>
                <td>{{ $document->creator_name }}</td>
                <td>{{ $document->checker_name }}</td>
                <td>{{ $document->approver_name }}</td>
                <td>
                  @if ($document->document_status == 'APPROVED')
                    <span class="badge s-APPROVED">{{ $statusLabel }}</span>
                  @elseif ($document->document_status == 'REJECT_BY_DC')
                    <span class="badge s-REJECT-BY-DC">{{ $statusLabel  }}</span>
                  @elseif ($document->document_status == 'PASS_BY_DC')
                    <span class="badge s-PASS-BY-DC">{{ $statusLabel  }}</span>
                  @elseif ($document->document_status== 'CHECKED')
                    <span class="badge s-CHECKED">{{ $statusLabel }}</span>
                  @elseif ($document->document_status == 'MARK_AS_READ')
                    <span class="badge s-APPROVED">{{ $statusLabel  }}</span>
                  @elseif ($document->document_status == 'REJECT_BY_DC' || $document->document_status == 'REJECT_BY_CHECKER' || $document->document_status == 'REJECT_BY_APPROVER')
                    <span class="badge s-REJECTED">{{ $statusLabel  }}</span>
                  @elseif ($document->document_status == 'CREATED')
                    <span class="badge s-CREATED">{{ $statusLabel  }}</span>
                  @elseif ($document->document_status == 'OBSOLETE')
                    <span class="badge s-OBSOLETED">{{ $statusLabel  }}</span>
                  @else
                    <span>{{ $document->document_status }}</span>
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
      ordering: true,
      order: [[1, 'asc']]
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
