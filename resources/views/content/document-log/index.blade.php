@extends('layouts/contentNavbarLayout')

@section('title', 'Document History - Form')

@section('styles')
  @vite('resources/css/datatables.css')
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
      background-color: #673ab7; /* Green */
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
      background-color: #FFC107; /* Blue */
    }
    .s-REJECT-BY-DC {
      background-color: #dc3545; /* Blue */
    }
    .s-PASS-BY-DC {
      background-color: #78f634; /* Blue */
      color: black;
    }
    .s-CHECKED {
      background-color: #00BCD4; /* Blue */
    }
    .s-OBSOLETED {
      background-color: #795548; /* Blue */
    }
    .s-NEW-REGISTRASI {
      background-color: #2BA745; /* Blue */
    }
    .s-NEW-REVISE {
      background-color: #ff9800; /* Blue */
    }
    .s-NEW-OBSOLETE {
      background-color: #607d8b; /* Blue */
    }
    .s-ACTIVE {
      background-color: #4caf50; /* Blue */
    }
    .s-ARCHIVED {
      background-color: #9e9e9e; /* Blue */
    }
    .s-AVAILABLE {
      background-color: #28a745; /* Gray */
    }
    .s-CREATED{background:#CCCCCC; color: black;}
    .s-REQUESTED{background:#fff7e6}
  </style>
  <style>
    .custom-modal-width {
        max-width: 80% !important; /* or whatever width you want */
    }
  </style>
  @endsection

@section('content')
<?php
    use App\Enums\DocumentStatus;
?>
<div class="row">
  <div class="card">
    <h5 class="card-header mb-1">
      <div class="d-flex justify-content-between align-items-center">
        <div>Document History</div>
        <div class="d-flex align-items-center gap-3">
          <div class="form-check form-switch mb-0">
            <input class="form-check-input" type="checkbox" id="exactMatchToggle">
            <label class="form-check-label" for="exactMatchToggle">Exact Match (Doc No)</label>
          </div>
          <a href="{{ route('document-log.index', ['export' => 'all']) }}" class="btn btn-success btn-sm" id="exportAllBtn">
            <i class="ri-file-excel-2-line"></i> Export All to CSV
          </a>
        </div>
      </div>
    </h5>
    <div class="table-responsive">
      <table id="audit-trail-datatable" class="table table-striped table-bordered datatable-responsive">
        <thead>
          <tr>
            <th width="10%">Action</th>
            <th>Document No</th>
            <th>Document Name</th>
            <th>Departement</th>
            <th>Rev No</th>
            <th>Creator</th>
            <th>Checker</th>
            <th>Approver</th>
            <th>Created Date</th>
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
                  <button class="btn btn-sm btn-info"
                    data-id="{{ $document->id }}"
                    data-bs-toggle="modal"
                    data-bs-target="#logModal">
                      View Logs
                  </button>
                </td>
                <td>
                  <a href="#" class="text-underline-hover" data-bs-placement="right" data-bs-toggle="tooltip" title="">{{ $document->document_no }}</a>
                </td>
                <td>{{ $document->document_name }}</td>
                <td>{{ $document->departement_name }}</td>
                <td>{{ $document->rev_no }}</td>
                <td>{{ $document->creator_name }}</td>
                <td>{{ $document->checker_name }}</td>
                <td>{{ $document->approver_name }}</td>
                <td data-order="{{ $document->created_at }}">{{ \Carbon\Carbon::parse($document->created_at)->format('d/m/Y H:i:s') }}</td>
                <td>
                  @if ($document->is_obsolete == 1)
                    <span class="badge s-OBSOLETED">Obsolete</span>
                  @elseif ($document->document_status == 'APPROVED')
                    <span class="badge s-ACTIVE">Active</span>
                  @elseif ($document->document_status == 'PASS_BY_DC')
                    <span class="badge s-PASS-BY-DC">{{ $statusLabel }}</span>
                  @elseif ($document->document_status == 'REJECT_BY_DC' || $document->document_status == 'REJECT_BY_CHECKER' || $document->document_status == 'REJECT_BY_APPROVER')
                    <span class="badge s-REJECTED">{{ $statusLabel }}</span>
                  @elseif ($document->document_status == 'EDIT')
                    <span class="badge s-CREATED">{{ $statusLabel }}</span>
                  @elseif ($document->document_status == 'OBSOLETE')
                    <span class="badge s-OBSOLETED">{{ $statusLabel }}</span>
                  @else
                    <span class="badge s-{{ $document->document_status }}">{{ $statusLabel }}</span>
                  @endif
                </td>

            </tr>
            @endforeach
          @endif
        </tbody>
      </table>

      <!-- add modal -->
      <div class="modal fade" id="logModal" tabindex="-1" aria-labelledby="logModalLabel" aria-hidden="true">
        <div class="modal-dialog custom-modal-width">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title">Transaction Logs</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
              <div class="table-responsive">
                <div class="d-flex justify-content-end mb-2">
                  <input type="hidden" id="id" name="id" value="0">
                  <a id="exportBtn" class="btn btn-success"><i class="ri-download-2-fill"></i>
                    &nbsp;<span>Download CSV</span>
                  </a>
                </div>
                <table class="table table-bordered" id="logTable">
                  <thead>
                      <tr>
                          <th>Document Name</th>
                          <th>Created At</th>
                          <th>Created By</th>
                          <th>Action</th>
                          <th>Notes</th>
                      </tr>
                  </thead>
                  <tbody>
                  </tbody>
                </table>
              </div>

            </div>
          </div>
        </div>
        </div>
      <!-- /add modal -->

    </div>
  </div>
</div>

@endsection

@section('scripts')
  @vite('resources/js/datatables.js')
  <script type="module">
    (function($) {
      $(document).ready(function () {
        const table = $('#audit-trail-datatable').DataTable({
          responsive: true,
          scroller: false,
          autoWidth: false,
          order: [[8, 'desc']] // Sort by Created Date (index 8) descending by default
        });

        // Add custom search logic
        $.fn.dataTable.ext.search.push(
          function(settings, data, dataIndex) {
            const isExact = $('#exactMatchToggle').is(':checked');
            if (!isExact) return true; // Standard search behavior

            // Get global search value
            const searchTerm = table.search().toLowerCase().trim();
            if (!searchTerm) return true; // No search term, show everything

            // Get document number from column 1 (DataTables provides text content in 'data')
            const docNo = data[1].toLowerCase().trim();

            // Return true if exact match
            return docNo === searchTerm;
          }
        );

        // Re-draw when toggle changes
        $('#exactMatchToggle').on('change', function() {
          table.draw();
        });

        // Re-draw when global search changes
        table.on('search.dt', function() {
          if ($('#exactMatchToggle').is(':checked')) {
            // Note: Custom filters are applied during .draw() which is triggered by search
          }
        });

        $('#exportAllBtn').on('click', function(e) {
          Swal.fire({
            title: 'Processing...',
            text: 'Preparing your CSV export. This should be much faster.',
            allowOutsideClick: false,
            showConfirmButton: false,
            didOpen: () => {
              Swal.showLoading();
            },
            timer: 3000,
            timerProgressBar: true
          });
        });
      });
    })(window.jQuery);
  </script>
  <script>
    function formatDate(isoString) {
      const date = new Date(isoString);
      return date.getFullYear() + '-' +
          String(date.getMonth() + 1).padStart(2, '0') + '-' +
          String(date.getDate()).padStart(2, '0') + ' ' +
          String(date.getHours()).padStart(2, '0') + ':' +
          String(date.getMinutes()).padStart(2, '0') + ':' +
          String(date.getSeconds()).padStart(2, '0');
    }

    document.addEventListener('DOMContentLoaded', function () {
        const logModal = document.getElementById('logModal');

        logModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const id = button.getAttribute('data-id');
            document.getElementById('id').value = id;
            document.getElementById("exportBtn").href = `/document-log/${id}/export`;

            // Clear existing rows
            const tbody = document.querySelector('#logTable tbody');
            tbody.innerHTML = '<tr><td colspan="3">Loading...</td></tr>';

            // Fetch log data from server
            fetch(`/document-log/${id}/logs`)
                .then(response => response.json())
                .then(data => {
                    tbody.innerHTML = ''; // Clear loading row
                     console.log("Logs received:", data);
                    if (data.length > 0) {
                       data.forEach(log => {
                          const userName = log.name  ?? 'Unknown';
                           let row = `
                               <tr>
                                   <td>${log.document_no} - ${log.document_name}</td>
                                   <td>${formatDate(log.created_at)}</td>
                                   <td>${userName}</td>
                                   <td>${log.action}</td>
                                   <td>${log.notes ?? ''}</td>
                               </tr>`;
                           tbody.innerHTML += row;
                       });
                    } else {
                       tbody.innerHTML = '<tr><td colspan="3">No logs found.</td></tr>';
                    }
                })
                .catch(err => {
                    console.error('Error loading logs:', err);
                    tbody.innerHTML = '<tr><td colspan="3">Failed to load logs.</td></tr>';
                });
        });
    });
    </script>
@endsection
