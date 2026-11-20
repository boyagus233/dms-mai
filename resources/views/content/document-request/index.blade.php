@extends('layouts/contentNavbarLayout')

@section('title', 'Document Request - Form')

@section('styles')
  <!-- Datatables -->
  <link href="https://cdn.datatables.net/1.10.20/css/jquery.dataTables.min.css" rel="stylesheet">
  <link href="/vendor/datatables.net-bs/css/dataTables.bootstrap.min.css" rel="stylesheet">
  <link href="/vendor/datatables.net-responsive-bs/css/responsive.bootstrap.min.css" rel="stylesheet">
  <link href="/vendor/datatables.net-scroller-bs/css/scroller.bootstrap.min.css" rel="stylesheet">

  <style>
    .badge{display:inline-block;padding:.2rem .5rem;border-radius:999px;font-size:12px;border:1px solid #ddd}

    /* Status-specific styles */
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
    .s-REQUESTED{background:#fff7e6}
  </style>
@endsection

@section('content')

<div class="row">
  <div class="card">
    <h5 class="card-header mb-1">
      <div class="col-sm-12">Document Request List</div>
      <div class="d-flex justify-content-end">
        @if ($issadddocument === true)
          <a href="{{ route('document-request.create') }}" class="btn btn-primary">New Request</a>
        @endif
        </div>
    </h5>
    <div class="table-responsive">
      <table id="datatable-responsive" class="table table-striped table-bordered datatable-responsive">
        <thead>
          <tr>
            <th>Document Number</th>
            <th>Document Type</th>
            <th>Proc</th>
            <th>Func</th>
            <th>Document Name</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody class="table-border-bottom-0">
          @if(!empty($documentRequests))
            @foreach ( $documentRequests as $documentRequest )
            <tr>
                <td>
                  <a href="{{ route('document-request.show', $documentRequest->id) }}" class="text-underline-hover" data-bs-placement="right" data-bs-toggle="tooltip" title="View">{{ $documentRequest->document_no }}</a>
                </td>
                <td>{{ $documentRequest->document_type_code }}</td>
                <td>{{ $documentRequest->document_classification_code }}</td>
                <td>{{ $documentRequest->document_function_code }}</td>
                <td>{{ $documentRequest->document_name }}</td>
                <td>
                  @if ($documentRequest->document_status == 'APPROVED')
                    <span class="badge s-APPROVED">{{ $document->document_status }}</span>
                  @elseif ($documentRequest->document_status == 'BOOKED')
                    <span class="badge s-BOOKED">{{ $documentRequest->document_status }}</span>
                  @else
                    <span>{{ $documentRequest->document_status }}</span>
                  @endif
                </td>
                {{--
                <td>
                  @if ($documentRequest->document_status == 'REQUESTED')
                    <form action="{{ route('document-request.terbitkannomor', $documentRequest->id) }}" method="POST" style="display:inline;">
                      @csrf
                      @method('PUT')
                      <button type="submit" class="btn btn-outline-primary">
                        Terbitkan Nomor
                      </button>
                    </form>
                  @else
                    <span class="text-success">-</span>
                  @endif
                </td>
                --}}
            </tr>
            @endforeach
          @endif
        </tbody>
      </table>

      <!-- /delete modal -->
        <div id="deleteConfirmModal" class="modal fade bs-example-modal-sm" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-sm">
            <div class="modal-content">
                <div class="modal-header">
                <h4 class="modal-title" id="myModalLabel2"><i class="fa fa-times-circle fa-lg red"></i> <span>Delete</span></h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span>
                </button>
                </div>
                <div class="modal-body">
                <p>Are you sure you want to delete this item?</p>
                </div>
                <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-light" data-dismiss="modal">Cancel</button>
                <a href="#" type="button" class="btn btn-sm btn-danger">Delete</a>
                </div>
            </div>
            </div>
        </div>
        <!-- /delete modal -->

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
@endsection
