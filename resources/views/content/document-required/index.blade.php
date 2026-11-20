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
      <div class="col-sm-12">On Progress Document List</div>
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
            <th>Revisi</th>
            <th>1st Assignment</th>
            <th>2nd Assignment</th>
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
                  @if ($document->document_status == 'APPROVED')
                    <span></span>
                  @else
                    <a href="{{ route('document-required.check', $document->id) }}" class="btn-sm action-btn" data-bs-placement="top" data-bs-toggle="tooltip" title="Check Document"><i class="ri-file-check-fill"></i></a>
                    <a href="{{ route('document-required.approve', $document->id) }}" class="btn-sm action-btn" data-bs-placement="top" data-bs-toggle="tooltip" title="Approve Document"><i class="ri-checkbox-circle-line"></i></a>
                  @endif
                </td>
                <td>
                  <a href="{{ route('document-required.show', $document->id) }}" class="text-underline-hover" data-bs-placement="right" data-bs-toggle="tooltip" title="View Document">{{ $document->document_no }}</a>
                </td>
                <td>{{ $document->document_name }}</td>
                <td>{{ $document->departement_name }}</td>
                <td>
                  @if ($document->is_obsolete == 1)
                    <span>Obsolete</span>
                  @else
                    {{ $document->rev_no }}
                  @endif
                </td>
                <td>{{ $document->employee_name }}</td>
                <td>{{ $document->employee_name_2 }}</td>
                <td>
                  @if ($document->document_status == 'APPROVED')
                    <span class="badge s-APPROVED">{{ $statusLabel }}</span>
                  @elseif ($document->document_status == 'BOOKED')
                    <span class="badge s-BOOKED">{{ $statusLabel }}</span>
                  @elseif ($document->document_status == 'CREATED')
                    <span class="badge s-CREATED">{{ $statusLabel }}</span>
                  @elseif ($document->document_status == 'PASS_BY_DC')
                    <span class="badge s-PASS-BY-DC">{{ $statusLabel }}</span>
                  @elseif ($document->document_status == 'CHECKED')
                    <span class="badge s-CHECKED">{{ $statusLabel }}</span>
                  @elseif ($document->document_status == 'OBSOLETE')
                    <span class="badge s-OBSOLETED">{{ $statusLabel }}</span>
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
