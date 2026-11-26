@extends('layouts/contentNavbarLayout')

@section('title', 'Document History - Audit Trail')

@section('styles')
  @vite('resources/css/datatables.css')
  <style>
    .badge{display:inline-block;padding:.2rem .5rem;border-radius:999px;font-size:12px;border:1px solid #ddd}

    /* Status-specific styles */
    .s-CANCELLED {
      background-color: #007bff;
    }
    .s-BOOKED {
      background-color: #6c757d;
    }
    .s-APPROVED {
      background-color: #673ab7;
    }
    .s-REJECTED {
      background-color: #dc3545;
    }
    .s-PENDING {
      background-color: #ffc107;
      color: #212529;
    }
    .s-CHECK-BY-DC {
      background-color: #FFC107;
    }
    .s-REJECT-BY-DC {
      background-color: #dc3545;
    }
    .s-PASS-BY-DC {
      background-color: #78f634;
      color: black;
    }
    .s-CHECKED {
      background-color: #00BCD4;
    }
    .s-OBSOLETED {
      background-color: #795548;
    }
    .s-NEW-REGISTRASI {
      background-color: #2BA745;
    }
    .s-NEW-REVISE {
      background-color: #ff9800;
    }
    .s-NEW-OBSOLETE {
      background-color: #607d8b;
    }
    .s-ACTIVE {
      background-color: #4caf50;
    }
    .s-ARCHIVED {
      background-color: #9e9e9e;
    }
    .s-AVAILABLE {
      background-color: #28a745;
    }
    .s-CREATED{background:#CCCCCC; color: black;}
    .s-REQUESTED{background:#fff7e6}
  </style>
  <style>
    .custom-modal-width {
        max-width: 80% !important;
    }
  </style>
@endsection

@section('content')
<?php
    use App\Enums\DocumentStatus;
    $activeTab = ($tab ?? 'doc_control') === 'adhoc' ? 'adhoc' : 'doc_control';
?>
<div class="row">
  <div class="col-12">
    <div class="card">
      <div class="card-header p-0 border-bottom">
        <ul class="nav nav-tabs card-header-tabs m-0" id="auditTrailTabs" role="tablist">
          <li class="nav-item" role="presentation">
            <button class="nav-link {{ $activeTab === 'doc_control' ? 'active' : '' }}" id="tab-doc-control" data-bs-toggle="tab" data-bs-target="#pane-doc-control" type="button" role="tab">
              <i class="ri-file-list-3-line me-1"></i> Doc Control
              <span class="badge bg-label-secondary ms-1">{{ count($documentUploads ?? []) }}</span>
            </button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link {{ $activeTab === 'adhoc' ? 'active' : '' }}" id="tab-adhoc" data-bs-toggle="tab" data-bs-target="#pane-adhoc" type="button" role="tab">
              <i class="ri-quill-pen-line me-1"></i> All Document Approval
              <span class="badge bg-label-primary ms-1">{{ count($adhocDocuments ?? []) }}</span>
              @if(!empty($isAdmin))
                <span class="badge bg-label-dark ms-1">All Users</span>
              @endif
            </button>
          </li>
        </ul>
      </div>

      <div class="tab-content p-0">
        {{-- TAB 1: DOC CONTROL (EXISTING) --}}
        <div class="tab-pane fade {{ $activeTab === 'doc_control' ? 'show active' : '' }}" id="pane-doc-control" role="tabpanel">
          <div class="card-header border-bottom py-3">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
              <h5 class="mb-0">Document History &mdash; Doc Control</h5>
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
          </div>

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
                  @foreach ($documentUploads as $document)
                    @php
                      $statusLabel = DocumentStatus::from($document->document_status)->label();
                    @endphp
                    <tr>
                      <td>
                        <button class="btn btn-sm btn-info"
                          data-source="doc_control"
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
          </div>
        </div>

        {{-- TAB 2: ALL DOCUMENT APPROVAL (AD-HOC E-SIGN) --}}
        <div class="tab-pane fade {{ $activeTab === 'adhoc' ? 'show active' : '' }}" id="pane-adhoc" role="tabpanel">
          <div class="card-header border-bottom py-3">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
              <div>
                <h5 class="mb-0">Document History &mdash; All Document Approval</h5>
                <small class="text-muted">
                  @if(!empty($isAdmin))
                    Menampilkan jejak audit seluruh dokumen All Document Approval lintas akun (Admin).
                  @else
                    Menampilkan jejak audit dokumen All Document Approval yang Anda buat atau tandatangani.
                  @endif
                </small>
              </div>
              <a href="{{ route('document-log.adhoc-export-all') }}" class="btn btn-success btn-sm" id="exportAllAdhocBtn">
                <i class="ri-file-excel-2-line"></i> Export All to CSV
              </a>
            </div>
          </div>

          <div class="table-responsive">
            <table id="adhoc-audit-datatable" class="table table-striped table-bordered datatable-responsive">
              <thead>
                <tr>
                  <th width="14%">Action</th>
                  <th>Judul Dokumen</th>
                  <th>Pengunggah</th>
                  <th>Alur</th>
                  <th>Signers</th>
                  <th>Jumlah Log</th>
                  <th>Created Date</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody class="table-border-bottom-0">
                @forelse(($adhocDocuments ?? []) as $doc)
                  @php
                    $totalSigners = $doc->signers->count();
                    $signedCount = $doc->signers->where('status', 'SIGNED')->count();
                    $signerNames = $doc->signers->map(fn($s) => $s->user->name ?? 'User')->implode(', ');
                  @endphp
                  <tr>
                    <td>
                      <div class="d-flex gap-1">
                        <button class="btn btn-sm btn-info"
                          data-source="adhoc"
                          data-id="{{ $doc->id }}"
                          data-bs-toggle="modal"
                          data-bs-target="#logModal">
                            View Logs
                        </button>
                        <a href="{{ route('adhoc-document.show', ['uuid' => $doc->uuid]) }}" class="btn btn-sm btn-outline-primary" title="Buka Dokumen">
                          <i class="ri-eye-line"></i>
                        </a>
                      </div>
                    </td>
                    <td>
                      <strong>{{ $doc->title }}</strong><br>
                      <small class="text-muted">{{ $doc->original_file_name }}</small>
                    </td>
                    <td>{{ $doc->creator->name ?? 'Internal User' }}</td>
                    <td>
                      @if($doc->workflow_type === 'SEQUENTIAL')
                        <span class="badge bg-label-info border-0">Sequential</span>
                      @else
                        <span class="badge bg-label-warning border-0">Parallel</span>
                      @endif
                    </td>
                    <td>
                      <small class="fw-semibold">{{ $signedCount }}/{{ $totalSigners }}</small>
                      @if($signerNames)
                        <br><small class="text-muted">{{ $signerNames }}</small>
                      @endif
                    </td>
                    <td>
                      <span class="badge bg-label-secondary border-0">{{ $doc->audit_logs_count }} event</span>
                    </td>
                    <td data-order="{{ $doc->created_at }}">{{ $doc->created_at ? $doc->created_at->format('d/m/Y H:i:s') : '-' }}</td>
                    <td>
                      @if($doc->status === 'COMPLETED')
                        <span class="badge bg-success border-0 text-white">Completed</span>
                      @elseif($doc->status === 'IN_PROGRESS')
                        <span class="badge bg-primary border-0 text-white">In Progress</span>
                      @elseif($doc->status === 'REJECTED')
                        <span class="badge bg-danger border-0 text-white">Rejected</span>
                      @else
                        <span class="badge bg-secondary border-0 text-white">Draft</span>
                      @endif
                    </td>
                  </tr>
                @empty
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Shared Modal: Transaction Logs -->
      <div class="modal fade" id="logModal" tabindex="-1" aria-labelledby="logModalLabel" aria-hidden="true">
        <div class="modal-dialog custom-modal-width">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title" id="logModalTitle">Transaction Logs</h5>
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
                      <th>IP Address</th>
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
      <!-- /Modal -->

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
          order: [[8, 'desc']]
        });

        const adhocTable = $('#adhoc-audit-datatable').DataTable({
          responsive: true,
          scroller: false,
          autoWidth: false,
          order: [[6, 'desc']],
          language: {
            emptyTable: 'Belum ada dokumen All Document Approval yang terkait dengan akun Anda.'
          }
        });

        $('button[data-bs-toggle="tab"]').on('shown.bs.tab', function () {
          table.columns.adjust().responsive.recalc();
          adhocTable.columns.adjust().responsive.recalc();
        });

        // Custom exact match search logic for Doc Control table
        $.fn.dataTable.ext.search.push(
          function(settings, data, dataIndex) {
            if (settings.nTable.id !== 'audit-trail-datatable') return true;
            const isExact = $('#exactMatchToggle').is(':checked');
            if (!isExact) return true;

            const searchTerm = table.search().toLowerCase().trim();
            if (!searchTerm) return true;

            const docNo = data[1].toLowerCase().trim();
            return docNo === searchTerm;
          }
        );

        $('#exactMatchToggle').on('change', function() {
          table.draw();
        });

        $('#exportAllBtn, #exportAllAdhocBtn').on('click', function() {
          Swal.fire({
            title: 'Processing...',
            text: 'Preparing your CSV export.',
            allowOutsideClick: false,
            showConfirmButton: false,
            didOpen: () => {
              Swal.showLoading();
            },
            timer: 2500,
            timerProgressBar: true
          });
        });
      });
    })(window.jQuery);
  </script>
  <script>
    function escapeHtml(value) {
      if (value === null || value === undefined) return '';
      return String(value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
    }

    function formatDate(isoString) {
      if (!isoString) return '-';
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
            const source = button.getAttribute('data-source') || 'doc_control';
            const modalTitle = document.getElementById('logModalTitle');
            const exportBtn = document.getElementById('exportBtn');
            const tbody = document.querySelector('#logTable tbody');

            document.getElementById('id').value = id;
            tbody.innerHTML = '<tr><td colspan="6" class="text-center">Loading...</td></tr>';

            if (source === 'adhoc') {
                modalTitle.textContent = 'Transaction Logs — All Document Approval';
                exportBtn.href = '#';

                fetch(`/document-log/adhoc/${id}/logs`)
                    .then(response => {
                        if (!response.ok) throw new Error('Unauthorized or failed to load');
                        return response.json();
                    })
                    .then(payload => {
                        tbody.innerHTML = '';
                        if (payload.document && payload.document.uuid) {
                            exportBtn.href = `/adhoc-document/${payload.document.uuid}/export-audit-csv`;
                        }
                        const logs = payload.logs || [];
                        if (logs.length > 0) {
                            logs.forEach(log => {
                                const actorInfo = escapeHtml(log.actor_name) + (log.actor_email ? `<br><small class="text-muted">${escapeHtml(log.actor_email)}</small>` : '');
                                tbody.innerHTML += `
                                    <tr>
                                        <td>${escapeHtml(log.document_name)}</td>
                                        <td>${formatDate(log.created_at)}</td>
                                        <td>${actorInfo}</td>
                                        <td><span class="badge bg-label-info border-0">${escapeHtml(log.action)}</span></td>
                                        <td class="font-monospace small">${escapeHtml(log.ip_address || '-')}</td>
                                        <td>${escapeHtml(log.notes || '-')}</td>
                                    </tr>`;
                            });
                        } else {
                            tbody.innerHTML = '<tr><td colspan="6" class="text-center">No logs found.</td></tr>';
                        }
                    })
                    .catch(err => {
                        console.error('Error loading adhoc logs:', err);
                        tbody.innerHTML = '<tr><td colspan="6" class="text-center text-danger">Failed to load logs.</td></tr>';
                    });
            } else {
                modalTitle.textContent = 'Transaction Logs — Doc Control';
                exportBtn.href = `/document-log/${id}/export`;

                fetch(`/document-log/${id}/logs`)
                    .then(response => response.json())
                    .then(data => {
                        tbody.innerHTML = '';
                        if (data.length > 0) {
                            data.forEach(log => {
                                const userName = log.name ?? 'Unknown';
                                tbody.innerHTML += `
                                    <tr>
                                        <td>${escapeHtml(log.document_no)} - ${escapeHtml(log.document_name)}</td>
                                        <td>${formatDate(log.created_at)}</td>
                                        <td>${escapeHtml(userName)}</td>
                                        <td>${escapeHtml(log.action)}</td>
                                        <td>-</td>
                                        <td>${escapeHtml(log.notes ?? '')}</td>
                                    </tr>`;
                            });
                        } else {
                            tbody.innerHTML = '<tr><td colspan="6" class="text-center">No logs found.</td></tr>';
                        }
                    })
                    .catch(err => {
                        console.error('Error loading logs:', err);
                        tbody.innerHTML = '<tr><td colspan="6" class="text-center text-danger">Failed to load logs.</td></tr>';
                    });
            }
        });
    });
  </script>
@endsection
