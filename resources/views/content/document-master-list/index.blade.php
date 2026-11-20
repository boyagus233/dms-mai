@extends('layouts/contentNavbarLayout')

@section('title', 'Document Approved - Form')

@section('styles')
  @vite('resources/css/datatables.css')
  <!-- Font Awesome for icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  @endsection

@section('content')
<?php
    use App\Enums\DocumentStatus;
?>
<div class="row">
  <div class="card">
    <h5 class="card-header mb-1">
      @if ($viewtype == 'obsolete')
          <div class="col-sm-12">List Obsolete Document</div>
      @elseif ($viewtype == 'approve')
          <div class="col-sm-12">List Approved Document</div>
      @else
          <div class="col-sm-12">List All Document</div>
      @endif
      <div class="d-flex justify-content-end">
        <br>
      </div>

      @if ($viewtype == 'alldata')
        <form id="filterForm" method="GET" action="{{ route('document-master-list.index') }}" class="mb-3">
          <input type="hidden" name="viewtype" value="{{ $viewtype }}">
          <div class="row">
            <div class="col-md-3">
                <label>Status</label>
                <select name="status" class="form-select">
                    <option value="all">All</option>
                    <option value="exclrevisi" {{ $status == 'exclrevisi' ? 'selected' : '' }}>Exclude Revise</option>
                </select>
            </div>

            <div class="col-md-3">
                <label>Start Date</label>
                <input type="date" name="start_date" class="form-control" value="{{ $startDate }}">
            </div>

            <div class="col-md-3">
                <label>End Date</label>
                <input type="date" name="end_date" class="form-control" value="{{ $endDate }}">
            </div>

            <div class="col-md-3 d-flex align-items-end">
                <button type="submit" class="btn btn-primary">Filter</button>
            </div>
          </div>
        </form>
      @endif
    </h5>

      <div class="table-responsive">
        @if ($viewtype == 'alldata')
        <div class="row mb-3" style="padding-left: 20px">
          <div class="col-md-6 d-flex align-items-center gap-2">
            <button type="button" id="btnCompare" class="btn btn-primary"><i class="fa fa-arrows-alt-h"></i>&nbsp;<span>Compare Document</span></button>
            <a id="exportBtn" href="{{ route('document-master-list.export') }}" class="btn btn-primary">
              <i class="ri-download-2-fill"></i>&nbsp;<span>Download Excel</span>
            </a>
          </div>
        </div>
        @endif

        <table id="datatable-responsive" class="table table-striped table-bordered datatable-responsive">
          <thead>
            <tr>
              <th width="13%">Action</th>
              <th>Document No</th>
              <th>Document Name</th>
              <th>Departement</th>
              <th>Revisi</th>
              <th>Creator</th>
              <th>Checker</th>
              <th>Approver</th>
              {{--
              @if ($viewtype === 'alldata')
                <th>Is Obsolete</th>
              @endif
              --}}
              @if ($viewtype === 'obsolete')
                <th>Obsolete Date</th>
              @endif
              <th>Document Status</th>
            </tr>
          </thead>
          <tbody class="table-border-bottom-0">
            @if(!empty($documentUploads))
              @foreach ( $documentUploads as $document )
                  @php
                    $statusLabel = DocumentStatus::from($document->document_status)->label();
                
                    if ($document->is_obsolete == 1 && $document->document_status == 'APPROVED') {
                      $statusLabel = 'Obsolete';
                    } elseif ($document->is_obsolete == 0 && $document->document_status == 'APPROVED') {
                      $statusLabel = 'Active';
                    }
                  @endphp
              <tr>
                  <td>
                    @if ($viewtype == 'alldata')
                        <input type="checkbox" class="row-checkbox" value="{{ $document->id }}" data-id="{{ $document->id }}" data-pdf="{{ $document->file_name ?? '' }}">
                        <a href="#" class="text-underline-hover add-to-collector" data-bs-placement="right" title="Add Document" data-id="{{ $document->id }}" data-pdf="{{ $document->file_name ?? '' }}" ></a>
                    @else
                      @if ($document->file_name != '' && $document->is_obsolete === 1 && $document->document_status === "APPROVED")
                        <a href="{{ url('/document-master-list/download-obsolete/' . $document->id) }}" class="text-underline-hover" data-bs-placement="right" data-bs-toggle="tooltip" title="Download Obsolete"><i class="tf-icons ri-download-2-fill" style="color: rgb(91, 2, 5);"></i></a>
                      @endif
                      @if ($document->file_name != '' && $document->is_obsolete === 0)
                        <a href="#" class="text-underline-hover" data-bs-placement="right" data-bs-toggle="modal" title="Download" data-bs-target="#downloadDocumentModal" data-id="{{ $document->id }}"><i class="tf-icons ri-download-2-fill"></i></a>
                      @endif
                      <a href="#" class="text-underline-hover" data-bs-placement="right" data-bs-toggle="modal" title="View" data-bs-target="#previewDocumentModal" data-pdf="{{ asset('storage/documents/' . $document->file_name) }}"><i class="ri-eye-fill"></i></a>
                      <a href="#" class="text-underline-hover open-email-modal" data-bs-placement="right" data-bs-toggle="modal" title="Send Mail" data-bs-target="#sendMailModal" data-id="{{ $document->id }}" data-filename="{{ $document->file_name }}"><i class="ri-mail-send-line"></i></a>
                      @if ($document->file_attachment != '')
                        <a href="{{ url('/document-master-list/download-attachment/' . $document->id) }}" class="text-underline-hover" data-bs-placement="right" data-bs-toggle="tooltip" title="Download Attachment"><i class="tf-icons ri-download-2-fill" style="color: rgb(1, 121, 51);"></i></a>
                      @endif

                    @endif
                  </td>
                  <td>
                    <a href="{{ route('document-master-list.view', [$document->id, 'viewtype' => $viewtype]) }}" class="text-underline-hover" data-bs-placement="right" data-bs-toggle="tooltip" title="">{{ $document->document_no }}</a>
                  </td>
                  <td>{{ $document->document_name }}</td>
                  <td>{{ $document->departement_name }}</td>
                  <td>{{ $document->rev_no }}
                  </td>
                  <td>{{ $document->creator_name }}</td>
                  <td>{{ $document->checker_name}}</td>
                  <td>{{ $document->approver_name}}</td>
                  @if ($viewtype === 'obsolete')
                    <td>
                      @if ($document->is_obsolete===1 && $document->document_status === 'APPROVED')
                        {{ $document->obsolete_date}}
                      @endif
                    </td>
                  @endif
                    <td>
                  @if ($viewtype == 'alldata')
                      @if ($document->is_obsolete == 1 && $document->document_status == 'APPROVED')
                        <span class="badge s-OBSOLETED">Obsolete</span>
                      @elseif ($document->is_obsolete == 0 && $document->document_status == 'APPROVED')
                        <span class="badge s-ACTIVE">Active</span>
                      @elseif ($document->document_status == 'PASS_BY_DC')
                        <span class="badge s-PASS-BY-DC">{{ $statusLabel }}</span>
                      @elseif ($document->document_status == 'REJECT_BY_DC' || $document->document_status == 'REJECT_BY_CHECKER' || $document->document_status == 'REJECT_BY_APPROVER')
                        <span class="badge s-REJECTED">{{ $statusLabel }}</span>
                      @elseif ($document->document_status == 'CHECKED')
                        <span class="badge s-CHECKED">{{ $statusLabel }}</span>
                      @elseif ($document->document_status == 'CREATED')
                        <span class="badge s-CREATED">{{ $statusLabel }}</span>
                      @elseif ($document->document_status == 'ARCHIVED')
                        <span class="badge s-ARCHIVED">{{ $statusLabel }}</span>
                      @else
                        <span class="badge s-{{ $document->document_status }}">{{ $statusLabel }}</span>
                      @endif
                  @else
                      @if ($document->document_status <> 'APPROVED' && $document->document_status <> 'ARCHIVED' )
                        <span class="badge s-ACTIVE">Active</span>
                      @elseif ($document->document_status == 'APPROVED' && $document->is_obsolete===0)
                        <span class="badge s-ACTIVE">Active</span>
                      @elseif ($document->document_status == 'APPROVED' && $document->is_obsolete===1)
                        <span class="badge s-OBSOLETED">Obsolete</span>
                      @elseif ($document->document_status == 'REJECT_BY_DC')
                        <span class="badge s-REJECT-BY-DC">{{ $statusLabel  }}</span>
                      @elseif ($document->document_status == 'PASS_BY_DC')
                        <span class="badge s-PASS-BY-DC">{{ $statusLabel  }}</span>
                      @elseif ($document->document_status== 'CHECKED')
                        <span class="badge s-CHECKED">{{ $statusLabel }}</span>
                      @elseif ($document->document_status == 'MARK_AS_READ')
                        <span class="badge s-APPROVED">{{ $statusLabel }}</span>
                      @elseif ($document->document_status == 'REJECT_BY_DC' || $document->document_status == 'REJECT_BY_CHECKER' || $document->document_status == 'REJECT_BY_APPROVER')
                        <span class="badge s-REJECTED">{{ $statusLabel }}</span>
                      @elseif ($document->document_status == 'CREATED')
                        <span class="badge s-CREATED">{{ $statusLabel }}</span>
                      @elseif ($document->document_status == 'ARCHIVED')
                        <span class="badge s-ARCHIVED">{{ $statusLabel }}</span>
                      @else
                        <span>{{ $document->document_status }}</span>
                      @endif
                  @endif
                </td>
              </tr>
              @endforeach
            @endif
          </tbody>
        </table>
        {{-- remove this --}}
        {{--{{ $documentUploads->withQueryString()->links() }}--}}
        <!-- add modal -->
        <div class="modal fade" id="downloadDocumentModal" tabindex="-1" aria-hidden="true">
          <div class="modal-dialog modal-md">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title">Download Document</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
              </div>
              <div class="modal-body">
                <div class="row mb-3">
                    <label class="col-sm-2 col-form-label text-end" for="mailto"></label>
                    <div class="col-sm-10">
                    </div>
                  </div>
                <div class="row mb-3">
                  <div class="col-sm-2">
                  </div>
                  <div class="col-sm-8">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Cancel</button>
                    <a href="#" class="btn btn-success downloadFileBtn" id="internalDownloadFileBtn">
                      Internal
                    </a>
                    <a href="#" class="btn btn-success downloadFileBtn" id="ekternalDownloadFileBtn">
                      External
                    </a>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- add modal -->
        <div class="modal fade" id="previewDocumentModal" tabindex="-1" aria-labelledby="logModalLabel" aria-hidden="true">
          <div class="modal-dialog custom-modal-width">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title">Preview Document</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
              </div>
              <div class="modal-body">
                <!-- PDF Viewer -->
                <iframe id="pdfFrame" src="" width="100%" height="700px" style="border: none;"></iframe>
              </div>
            </div>
          </div>
        </div>

        <div class="modal fade" id="sendMailModal" tabindex="-1" aria-hidden="true">
          <div class="modal-dialog modal-lg">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title">Send Document to Email</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
              </div>
              <div class="modal-body">
                <form id="sendEmailForm" method="POST" action="{{ route('document-master-list.email', ['viewtype' => request()->query('viewtype')]) }}">
                  @csrf
                  <input type="hidden" id="id" name="id">
                  <input type="hidden" id="file-name" name="file-name">
                  <div class="row mb-3 d-none">
                    <label class="col-sm-2 col-form-label text-end" for="subject">Subject</label>
                    <div class="col-sm-10">
                      <input type="text" class="form-control" id="subject" name="subject" placeholder="Subject email"/>
                    </div>
                  </div>
                  <div class="row mb-3">
                    <label class="col-sm-2 col-form-label text-end" for="mailto">To</label>
                    <div class="col-sm-10">
                      <input type="text" class="form-control" id="mailto" name="mailto" placeholder="Mail to" required="required"/>
                    </div>
                  </div>
                  <div class="row mb-3">
                    <label class="col-sm-2 col-form-label text-end" for="description">Document Description</label>
                    <div class="col-sm-10">
                      <textarea class="form-control" id="description" name="description" rows="4" required></textarea>
                    </div>
                  </div>
                  <div class="row mb-3 d-none">
                    <label class="col-sm-2 col-form-label text-end" for="message">Message</label>
                    <div class="col-sm-10">
                      <textarea class="form-control" id="message" name="message" rows="4"></textarea>
                    </div>
                  </div>
                  <div class="row mb-3">
                    <div class="col-sm-2">
                    </div>
                    <div class="col-sm-6">
                      <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Cancel</button>
                      <button type="submit" class="btn btn-success">Send Email</button>
                    </div>
                  </div>
                </form>
              </div>
            </div>
          </div>
        </div>
        <!-- /add modal -->

        <!-- canvas multiple document -->
        <div class="offcanvas offcanvas-end"
          tabindex="-1"
          id="pdfCollector"
          aria-labelledby="pdfCollectorLabel">
          <div class="offcanvas-header">
            <h5 id="pdfCollectorLabel">Collected PDF Documents</h5>
            <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
          </div>
          <div class="offcanvas-body">
            <ul id="collectedPdfList" class="list-group mb-3"></ul>

            <form method="POST">
              @csrf
              <div id="hiddenPdfInputs"></div>
              <button type="button" class="btn btn-primary" id="previewAllBtn">Compare</button>
            </form>
          </div>
        </div>

        <div class="modal fade" id="pdfPreviewModal" tabindex="-1" aria-hidden="true">
          <div class="modal-dialog custom-modal-width modal-dialog-scrollable">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title">PDF Preview</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
              </div>
              <div class="modal-body d-flex flex-wrap gap-3" id="pdfPreviewContainer">
                <!-- PDFs will be added here -->
              </div>
            </div>
          </div>
        </div>


        <!-- Modal multiple document -->
        <div class="modal fade" id="compareDocumentModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false" aria-labelledby="compareDocumentModalLabel" aria-hidden="true">
          <div class="modal-dialog modal-sm">
            <div class="modal-content">
              <form id="uploadDocumentForm" enctype="multipart/form-data">
                @csrf
                <div class="modal-header">
                  <h5 class="modal-title">Compared Document</h5>
                  <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                  <input type="hidden" name="transaction_id" id="transactionIdInput">

                  <!-- Preview Area -->
                  <div id="pdfPreviewArea" class="border rounded p-3">
                    <strong>Selected PDFs:</strong>
                    <ul id="addedPdfFileList" class="mt-2 list-unstyled"></ul>
                  </div>
                </div>
                <div class="modal-footer">
                  <button type="submit" class="btn btn-primary">Compare</button>
                  <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                </div>
              </form>
            </div>
          </div>
        </div>

      </div>

  </div>
</div>

@endsection

@section('scripts')
  @vite('resources/js/datatables.js')
  <script>
    let viewtype = "{{ $viewtype }}";
    //console.log(viewtype);
  </script>
  <script>
    document.addEventListener('DOMContentLoaded', function () {
        // Get the modal element
        const pdfModal = document.getElementById('previewDocumentModal');
        const pdfFrame = document.getElementById('pdfFrame');

        // When the modal is about to be shown
        pdfModal.addEventListener('show.bs.modal', function (event) {
            const triggerLink = event.relatedTarget;
            const pdfUrl = triggerLink.getAttribute('data-pdf');

            // Set the iframe source
            pdfFrame.src = pdfUrl+'#toolbar=0';
        });

        // Clear PDF when modal is hidden (optional: helps with memory)
        pdfModal.addEventListener('hidden.bs.modal', function () {
            pdfFrame.src = '';
        });
    });
  </script>
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      const downloadModal = document.getElementById('downloadDocumentModal');
      const internaldownloadBtn = document.getElementById('internalDownloadFileBtn');
      const eksternaldownloadBtn = document.getElementById('ekternalDownloadFileBtn');
      downloadModal.addEventListener('show.bs.modal', function (event) {
        const btn = event.relatedTarget;
        const documentId = btn.getAttribute('data-id');

        const downloadUrlInternal = `/document-master-list/downloadfile/${documentId}/internal`;
        const downloadUrlEksternal = `/document-master-list/downloadfile/${documentId}/eksternal`;
        internaldownloadBtn.setAttribute('href', downloadUrlInternal);
        eksternaldownloadBtn.setAttribute('href', downloadUrlEksternal);
      });

      const downloadButtons = document.querySelectorAll('.downloadFileBtn');
      downloadButtons.forEach(button => {
        button.addEventListener('click', function (e) {
          e.preventDefault();

          const fileUrl = this.getAttribute('href');
          // Close modal
          const downloadModal = document.getElementById('downloadDocumentModal');
          const modalInstance = bootstrap.Modal.getInstance(downloadModal);
          if (modalInstance) {
            modalInstance.hide();
          }
          setTimeout(() => {
            if (fileUrl && fileUrl !== '#') {
              const link = document.createElement('a');
              link.href = fileUrl;
              link.download = ''; // Optional: hint download behavior
              document.body.appendChild(link);
              link.click();
              document.body.removeChild(link);
            }
          }, 10); // wait for modal to close smoothly

        });
      });

    });
  </script>
  <script>
    document.addEventListener('DOMContentLoaded', function () {
        const emailModal = document.getElementById('sendMailModal');
        document.querySelectorAll('.open-email-modal').forEach(button => {
            button.addEventListener('click', function (e) {
                e.preventDefault();
                const documentId = this.getAttribute('data-id');
                const filename = this.getAttribute('data-filename');
                document.getElementById('id').value = documentId;
                document.getElementById('file-name').value = filename;
                const modal = new bootstrap.Modal(emailModal);
                modal.show();
            });
        });
    });
  </script>
  <script>
    const modal = document.getElementById('sendMailModal');
    modal.addEventListener('hidden.bs.modal', function () {
      document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
      document.body.classList.remove('modal-open');
      document.body.style = '';
    });
  </script>

  <script>

    document.addEventListener('DOMContentLoaded', function () {
      const checkboxes = document.querySelectorAll('.row-checkbox');
      const maxSelection = 2;

      checkboxes.forEach(cb => {
          cb.addEventListener('change', function () {
              const checked = document.querySelectorAll('.row-checkbox:checked');
              const isLimitReached = checked.length >= maxSelection;

              checkboxes.forEach(box => {
                  if (!box.checked) {
                      box.disabled = isLimitReached;
                  }
              });
          });
      });

      const pdfList = document.getElementById('collectedPdfList');
      const hiddenInputs = document.getElementById('hiddenPdfInputs');
      const compareBtn = document.getElementById('btnCompare');
      const previewModalEl = document.getElementById('pdfPreviewModal');
      const previewModal = new bootstrap.Modal(previewModalEl);
      const previewContainer = document.getElementById('pdfPreviewContainer');

      if (viewtype == 'alldata') {

        // Preview button logic
        compareBtn.addEventListener('click', function () {
          previewContainer.innerHTML = ''; // Clear previous previews
          // const selectedIds = Array.from(document.querySelectorAll('.row-checkbox:checked'))
          //   .map(cb => cb.value);
          const selectedIds = Array.from(document.querySelectorAll('.row-checkbox:checked'))
            .map(cb => ({
              id: cb.value,
              documentid: cb.dataset.id,
              pdfname: cb.dataset.pdf
            }));

          console.log(selectedIds);

          if (selectedIds.length === 0) {
            previewContainer.innerHTML = '<li class="text-muted">No transactions selected.</li>';
            return;
          }

          // Add selected IDs to modal list
          const pdfName = "";
          selectedIds.forEach(item => {
            const li = document.createElement('li');
            li.className = 'list-group-item d-flex align-items-center';
            li.innerHTML = `
              <i class="ri-file-pdf-line text-danger me-2"></i>
              <span>${item.pdf}</span>
            `;
            pdfList.appendChild(li);
            // Hidden input for form submission
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'pdf_files[]';
            input.value = item.pdfname;
            hiddenInputs.appendChild(input);
          });

          // Get all selected PDF names
          const pdfInputs = hiddenInputs.querySelectorAll('input[name="pdf_files[]"]');

          if (pdfInputs.length === 0) {
            alert("No documents selected for preview.");
            return;
          }

          pdfInputs.forEach(input => {
            const pdfName = input.value;
            const iframe = document.createElement('iframe');

            iframe.src = `/storage/documents/${pdfName}#toolbar=0`;
            iframe.width = '48%';
            iframe.height = '550px';
            iframe.classList.add('border');

            previewContainer.appendChild(iframe);
          });

          previewModal.show();

          //Clear the offcanvas content
          pdfList.innerHTML = '';
          hiddenInputs.innerHTML = '';

        });

      }

    });
  </script>

  <script>
    if (viewtype == 'alldata') {
      document.addEventListener('DOMContentLoaded', function() {
      const form = document.getElementById('filterForm');
      const exportBtn = document.getElementById('exportBtn');

      function updateExportLink() {
          // Collect all form data
          const formData = new FormData(form);
          const params = new URLSearchParams();

          for (const [key, value] of formData.entries()) {
              if (value) { // skip empty values
                  params.append(key, value);
              }
          }

          // Update button href
          exportBtn.href = "{{ route('document-master-list.export') }}?" + params.toString();
        }

        // Update on page load
        updateExportLink();

        // Update whenever form values change
        form.addEventListener('change', updateExportLink);
      });
    }

  </script>

@endsection
