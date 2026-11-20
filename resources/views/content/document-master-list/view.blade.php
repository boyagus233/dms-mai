@extends('layouts/contentNavbarLayout')

@section('title', 'Document Approved - Form')
@section('styles')
  <style>
      #pdf-scroll-container {
        height: 50vh;                 /* 80% of viewport height */
        overflow-y: auto;            /* vertical scroll */
        padding: 16px;
        border: 1px solid #ccc;
        background-color: #f9f9f9;   /* light background */
        box-shadow: inset 0 0 10px rgba(0,0,0,0.1);
        border-radius: 6px;
    }

    #canvas-container canvas {
        display: block;
        width: 100%;       /* full width of parent */
        height: auto;      /* auto-scale height to maintain aspect ratio */
        margin: 10px auto; /* spacing between pages */
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        border: 1px solid #ddd;
        max-width: 1000px; /* optional: limit max width */
    }
    #canvas-container-old {
        position: relative;
        width: max-content;
        height: max-content;
        padding: 0px;
    }
    .draggable {
        display: none;
        /* width: 107px;
        height: 107px; */
        background-color: #e6e3e8;
        touch-action: none;
        user-select: none;
        text-align: center;
        padding: 0px 0;
        color: white;
        position: absolute;
    }

    .download-button {
        background-color: #6e9af1;
        color: white;
        padding: 8px 12px;
        border-radius: 4px;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-size: 16px;
    }

    .scrollable-box {
      height: 200px; /* or max-height: 150px; */
      border: 1px solid #ccc;
      padding: 10px;
      overflow-y: auto; /* Adds a vertical scrollbar only when needed */
    }
  </style>
  <style>
    .text-primary { color: #0d6efd; }
    .text-success { color: #198754; }
    .text-info { color: #0dcaf0; }
    .text-secondary { color: #6c757d; }
    .me-2 { margin-right: .5rem; }
    .fs-5 { font-size: 1.25rem; }
    .list-group { list-style: none; padding: 0; }
    .list-group-item { padding: .5rem 1rem; border-bottom: 1px solid #eee; display: flex; align-items: center; }
</style>
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
  <style>
    .custom-modal-width {
        max-width: 80% !important; /* or whatever width you want */
    }
  </style>
  <style>
    /* Workflow Container */
    .workflow {
      display: flex;
      justify-content: space-between;
      align-items: center;
      position: relative;
      margin: 50px auto;
      max-width: 700px;
    }

    /* Step Wrapper */
    .step {
      text-align: center;
      position: relative;
      flex: 1;
    }

    /* Connector Lines */
    .step:not(:last-child)::after {
      content: "";
      position: absolute;
      top: 35px; /* aligns line with center of circles */
      right: -50%;
      width: 100%;
      height: 5px;
      background-color: #dee2e6;
      z-index: 0;
      transition: background-color 0.3s ease;
    }

    /* Step Circle (larger) */
    .circle {
      width: 70px;
      height: 70px;
      border-radius: 50%;
      background-color: #dee2e6;
      color: #6c757d;
      display: flex;
      justify-content: center;
      align-items: center;
      font-size: 32px; /* larger icons */
      margin: 0 auto 12px;
      position: relative;
      z-index: 1;
      transition: all 0.3s ease;
    }

    /* Active Step */
    .circle.active {
      background-color: #0d6efd; /* Bootstrap primary */
      color: #fff;
      box-shadow: 0 0 15px rgba(13,110,253,0.5);
      transform: scale(1.1);
    }

    /* Completed Step */
    .circle.completed {
      background-color: #78f634; /* Bootstrap success */
      color: #fff;
      transform: scale(1.05);
    }

    /* Step Label */
    .label {
      font-size: 16px;
      color: #495057;
      font-weight: 600;
      margin-top: 5px;
    }

    /* Responsive */
    @media (max-width: 576px) {
      .workflow {
        flex-direction: column;
      }
      .step:not(:last-child)::after {
        display: none;
      }
    }
    </style>

  <!-- Font Awesome for icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
@endsection

@section('page-script')
@endsection

@section('content')
<div class="content-wrapper">


  <div class="row mb-3">
    <div class="col-md-12">
      <div class="card mb-1">
        <h5 class="card-header">Document Progress</h5>
        <div class="card-body demo-vertical-spacing demo-only-element">
          <div class="workflow">
            <div class="step">
              <div class="circle {{ $documentUpload->is_stamp_by_creator == 1 ? 'completed' : '' }}" id="creator">
                <i class="fa-solid fa-user-pen"></i></div>
              <div class="label">Creator</div>
            </div>

            <div class="step">
              <div class="circle {{ $documentUpload->is_stamp_by_creator == 1 && ($documentUpload->is_rejected_by_dc == 1 || $documentUpload->is_checked_by_dc == 1) ? 'completed' : '' }}" id="doccontrol"><i class="fa-solid fa-user-check"></i></div>
              <div class="label">Admin Control</div>
            </div>

            <div class="step">
              <div class="circle {{ $documentUpload->is_stamp_by_creator == 1 && $documentUpload->is_checked_by_dc == 1 && $documentUpload->is_checked == 1? 'completed' : '' }}" id="checker"><i class="fa-solid fa-user-check"></i></div>
              <div class="label">Checker</div>
            </div>

            <div class="step">
              <div class="circle {{ $documentUpload->is_checked == 1 && $documentUpload->is_approved == 1 ? 'completed' : '' }}" id="approver"><i class="fa-solid fa-user-tie"></i></div>
              <div class="label">Approver</div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="row mb-3">
    <div class="col-md-8">
      <div class="card mb-1">
          <h5 class="card-header">Preview Document</h5>
          <div class="card-body demo-vertical-spacing demo-only-element">
            <div class="row mb-3">
              <label class="col-sm-3 col-form-label text-end py-0">Document No</label>
              <div class="col-sm-6">
                <label class="col-form-label py-0">{{ $documentUpload->document_no }}</label>
              </div>
            </div>
            <div class="row mb-3">
              <label class="col-sm-3 col-form-label text-end py-0">Document Name</label>
              <div class="col-sm-6">
                <label class="col-form-label py-0">{{ $documentUpload->document_name }}</label>
              </div>
            </div>
            <div class="row mb-3">
              <label class="col-sm-3 col-form-label text-end py-0">Document Type</label>
              <div class="col-sm-6">
                @if( $documentUpload->document_type == 'P')
                  <label class="col-form-label py-0">Procedure</label>
                @elseif( $documentUpload->document_type == 'WI')
                  <label class="col-form-label py-0">Work Intruction</label>
                @else
                    <label class="col-form-label py-0">Form</label>
                @endif
              </div>
            </div>
            <div class="row mb-3">
              <label class="col-sm-3 col-form-label text-end py-0">Departement</label>
              <div class="col-sm-6">
                <label class="col-form-label py-0">{{ $documentUpload->Departements->code }}</label>
              </div>
            </div>
            <div class="row mb-3">
              <label class="col-sm-3 col-form-label text-end py-0">Rev No</label>
              <div class="col-sm-6">
                @if( $documentUpload->document_status == 'APPROVED' && $documentUpload->is_obsolete == 1)
                  <label class="col-form-label py-0">Obsolete</label>
                @else
                    <label class="col-form-label py-0">{{ $documentUpload->rev_no }}</label>
                @endif
              </div>
            </div>
            <div class="row mb-3">
              <label class="col-sm-3 col-form-label text-end py-0">Document Status</label>
              <div class="col-sm-9">
                <label class="col-form-label py-0">{{ $documentUpload->document_status }}</label>
              </div>
            </div>
            <div class="row mb-1">
              <label class="col-sm-3 col-form-label text-end py-0">File Name</label>
              <div class="col-sm-9">
                <label class="col-form-label py-0">{{ $documentUpload->file_name }}</label>
              </div>
            </div>
            <div class="row mb-3">
              <label class="col-sm-3 col-form-label text-end py-0">Document File</label>
              <div class="col-sm-9">
                @php
                  $relativeFolder = 'app/public/documents/';
                  $fileName = $documentUpload->file_name;
                  $absolutePath = storage_path($relativeFolder . $fileName);
                  $fileExists = !empty($fileName) && file_exists($absolutePath);
                @endphp

                @if (!$fileExists)
                  <div class="alert alert-danger mt-2 shadow-sm border-start border-danger border-5">
                    <div class="d-flex">
                      <i class="ri-error-warning-fill me-3 fs-3"></i>
                      <div>
                        <b class="d-block">File Tidak Ditemukan!</b>
                        Sistem mencari file di lokasi berikut:
                        <code class="d-block bg-white p-2 my-2 border rounded" style="word-break: break-all;">
                          {{ $absolutePath }}
                        </code>
                        <span class="small text-dark">
                          <b>Solusi:</b> Pastikan file tersebut ada di folder <code>storage/app/public/documents/</code>
                          atau silakan <b>Upload Ulang</b> versi revisi melalui menu Registrasi.
                        </span>
                      </div>
                    </div>
                  </div>
                @endif

                <div class="input-group mb-3">
                  <div id="pdf-scroll-container" class="flex w-full bg-grey-lighter mt-3">
                    <div id="canvas-container" class="space-y-6"></div>
                      @if($fileExists)
                          <script>
                              const existingPdfUrl = "{{ asset('storage/documents/' . $documentUpload->file_name) }}";
                          </script>
                      @else
                          <script>
                              const existingPdfUrl = null;
                          </script>
                      @endif
                  </div>
                </div>
              </div>
            </div>
            <div class="row mb-3">
              <label class="col-sm-3 col-form-label text-end py-0">Attachment File</label>
              <div class="col-sm-8">
                @if (!empty($documentUpload->file_attachment))
                  {{ $documentUpload->file_attachment }}
                    <a href="{{ asset('storage/attachments/' . $documentUpload->file_attachment) }}" target="_blank"
                      class="download-button">
                      <i class="tf-icons ri-download-2-fill"></i>
                    </a>
                @endif
              </div>
            </div>
            <div class="row mb-3">
              <div class="col-sm-3">
              </div>
              <div class="col-sm-6">
                <a href="{{ route('document-master-list.index', ['viewtype' => $viewtype]) }}" class="btn btn-secondary" role="button">Back</a>
              </div>
            </div>
          </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="row">
        <div class="card mb-2">
          <h6 class="card-header"><b>Signature</b></h6>
          <div class="card-body demo-vertical-spacing demo-only-element">
            <div class="row mb-2">
              <label class="col-sm-4 col-form-label text-end py-0">Creator</label>
              <div class="col-sm-8">
                <label class="col-form-label py-0">{{ $documentUpload->creator_name }}</label>
              </div>
            </div>
            <div class="row mb-2">
              <label class="col-sm-4 col-form-label text-end py-0">Checker</label>
              <div class="col-sm-8">
                <label class="col-form-label py-0">{{ $documentUpload->checker_name }}</label>
              </div>
            </div>
            <div class="row mb-4">
              <label class="col-sm-4 col-form-label text-end py-0">Approver</label>
              <div class="col-sm-8">
                <label class="col-form-label py-0">{{ $documentUpload->approver_name }}</label>
              </div>
            </div>
          </div>
        </div>

        <div class="card mb-2">
          <h6 class="card-header"><b>Departement Distributed</b></h6>
          <div class="card-body demo-vertical-spacing demo-only-element">
            <div class="row mb-3">
              <label class="col-sm-4 col-form-label text-end">Departements</label>
              <div class="col-sm-8">
                <ul class="list-unstyled">
                    @foreach ($departmentNames as $dept)
                        <li>
                            <i class="ri-organization-chart"></i>
                              {{ $dept }}
                        </li>
                    @endforeach
                </ul>
              </div>
            </div>
          </div>
        </div>

        <div class="card mb-2">
          <h6 class="card-header"><b>Document Distributed</b></h6>
          <div class="card-body demo-vertical-spacing demo-only-element">
          <div class="row mb-3">
              <div class="col-sm-4">
                  <span class="badge s-PASS-BY-DC">Green : Read</span>
              </div>
              <div class="col-sm-4">
                  <span class="badge s-REJECT-BY-DC">Red : Unread</span>
              </div>
              <div class="col-sm-4">
              </div>
            </div>
            <div class="row mb-3">
              <label class="col-sm-4 col-form-label text-end">Employees</label>
              <div class="col-sm-8 scrollable-box">
                <ul class="list-unstyled">
                    @foreach ($documentDistributed as $doc)
                        <li>
                          <i class="ri-group-fill"></i>
                            <span class="badge {{ $doc['is_read'] ? 's-PASS-BY-DC' : 's-REJECT-BY-DC' }}">{{ $doc['employee_name'] }}</span>
                        </li>
                    @endforeach
                </ul>
              </div>
            </div>
          </div>
        </div>

        <div class="card mb-2">
          <h6 class="card-header"><b>Counter Download</b></h6>
          <div class="card-body">
            <div class="row mb-0">
              <label class="col-sm-4 col-form-label text-end">Internal</label>
              <label class="col-sm-4 col-form-label">{{ $countInternalDownload }} <a href="#" class="text-underline-hover" data-bs-placement="right" data-bs-toggle="modal" title="View whos download" data-bs-target="#downloadDocumentModal" data-id="{{ $documentUpload->id }}"><i class="ri-user-search-line"></i></a> </label>
              <div class="col-sm-1">
              </div>

            </div>
            <div class="row mb-0">
              <label class="col-sm-4 col-form-label text-end">Eksternal</label>
              <label class="col-sm-4 col-form-label">{{ $countEksternalDownload }} <a href="#" class="text-underline-hover" data-bs-placement="right" data-bs-toggle="modal" title="View whos download" data-bs-target="#downloadDocumentModal" data-id="{{ $documentUpload->id }}"><i class="ri-user-search-line"></i></a></label>
            </div>
          </div>
        </div>

        <div class="card mb-2">
          <h6 class="card-header"><b>Doc Terkait</b></h6>
          <div class="card-body">
            <div class="row mb-0">
              @if ($relatedDoc->isEmpty())
                  <p>No results found.</p>
              @else
                  @foreach ($relatedDoc as $item)
                  <p>
                    <a href="{{ route('document-master-list.view', [$item->id, 'viewtype' => $viewtype]) }}">{{ $item->document_no }} - {{ $item->document_name }}</a>
                  </p>
                  @endforeach
              @endif

            </div>
          </div>
        </div>
      </div>

    </div>

  </div>


</div>
@endsection
@section('scripts')
  <script type="module">
    import * as pdfjsLib from "/js/pdfjs/pdf.min.mjs";
    pdfjsLib.GlobalWorkerOptions.workerSrc = "/js/pdfjs/pdf.worker.min.mjs";

    document.addEventListener("DOMContentLoaded", async function () {
      if (!existingPdfUrl) return;

      const container = document.getElementById("canvas-container");

      try {
          // Verify file accessibility
          const response = await fetch(existingPdfUrl, { method: 'HEAD' });
          if (!response.ok) {
              throw new Error(`File inaccessible at ${existingPdfUrl} (Status: ${response.status})`);
          }

          const loadingTask = pdfjsLib.getDocument(existingPdfUrl);
          const pdf = await loadingTask.promise;

          for (let pageNumber = 1; pageNumber <= pdf.numPages; pageNumber++) {
              const page = await pdf.getPage(pageNumber);
              const scale = 1.5;
              const viewport = page.getViewport({ scale });

              // Create a new canvas element
              const canvas = document.createElement("canvas");
              canvas.classList.add("border", "border-gray-400", "shadow-md");
              canvas.width = viewport.width;
              canvas.height = viewport.height;

              const context = canvas.getContext("2d");

              const renderContext = {
                  canvasContext: context,
                  viewport: viewport,
              };

              await page.render(renderContext).promise;

              // Append canvas to container
              container.appendChild(canvas);
          }
      } catch (error) {
          console.error('Error loading PDF:', error);
          container.innerHTML = `<div class="alert alert-danger mt-3"><i class="ri-error-warning-line me-2"></i><b>Gagal memuat PDF:</b> ${error.message}</div>`;
      }
    });
  </script>
@endsection
