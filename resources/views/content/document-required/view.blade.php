@extends('layouts/contentNavbarLayout')

@section('title', 'Document Upload - Form')
@section('styles')
  <style>
      #pdf-scroll-container {
        height: 80vh;                 /* 80% of viewport height */
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
  </style>
@endsection
@section('content')
<div class="row">
  <div class="col-xxl">
    <div class="card mb-4">
      <div class="card-header d-flex align-items-center justify-content-between">
        <h5 class="mb-0">View Document</h5> <small class="text-muted float-end"></small>
      </div>
      <div class="card-body">
        <form>
          @csrf
          <div class="row mb-3">
            <label class="col-sm-3 col-form-label text-end">Document No</label>
            <div class="col-sm-6">
              <label class="col-form-label">{{ $documentUpload->document_no  }}</label>
            </div>
          </div>
          <div class="row mb-3">
            <label class="col-sm-3 col-form-label text-end">Document Name</label>
            <div class="col-sm-6">
              <label class="col-form-label">{{ $documentUpload->document_name  }}</label>
            </div>
          </div>
          <div class="row mb-3">
            <label class="col-sm-3 col-form-label text-end">Departement</label>
            <div class="col-sm-6">
              <label class="col-form-label">{{ $documentUpload->Departements->code }}</label>
            </div>
          </div>
          <div class="row mb-3">
            <label class="col-sm-3 col-form-label text-end">Departements tag</label>
            <div class="col-sm-6">
              <label class="col-form-label">{{ implode(', ', $departmentNames) }}</label>
            </div>
          </div>
          <div class="row mb-3">
            <label class="col-sm-3 col-form-label text-end">Assign To</label>
            <div class="col-sm-9">
              <label class="col-form-label">{{ $documentUpload->employee->name }}</label>
            </div>
          </div>
          <div class="row mb-3">
            <label class="col-sm-3 col-form-label text-end">Assign To (2)</label>
            <div class="col-sm-9">
              <label class="col-form-label">{{ $documentUpload->employee2nd->name }}</label>
            </div>
          </div>
          <div class="row mb-3">
            <label class="col-sm-3 col-form-label text-end">Document Status</label>
            <div class="col-sm-9">
              <label class="col-form-label">{{ $documentUpload->document_status }}</label>
            </div>
          </div>
          <div class="row mb-3">
            <label class="col-sm-3 col-form-label text-end">Document File</label>
            <div class="col-sm-8">
              <div class="input-group mb-3">
                <div id="pdf-scroll-container" class="flex w-full bg-grey-lighter mt-3">
                  <div id="canvas-container" class="space-y-6"></div>
                    @if(!empty($documentUpload->file_name))
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
            <div class="col-sm-1">
              @if (!empty($documentUpload->file_name))
                  <a href="{{ asset('storage/documents/' . $documentUpload->file_name) }}" target="_blank"
                    download="{{ $documentUpload->document_name }}"
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
              <a href="{{ route('document-acknowledge.index') }}" class="btn btn-secondary" role="button">Back</a>
              <a href="{{ route('document-acknowledge.index') }}" class="btn btn-success" role="button">Mark As Read</a>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
@endsection
@section('scripts')
  <!--<script type="module" src='https://cdnjs.cloudflare.com/ajax/libs/pdf.js/4.0.269/pdf.min.mjs'></script> -->
  <!--<script type="module" src='https://cdnjs.cloudflare.com/ajax/libs/pdf.js/4.0.269/pdf.worker.min.mjs'></script> -->
  <script type="module">
    import * as pdfjsLib from "/js/pdfjs/pdf.min.mjs";
    pdfjsLib.GlobalWorkerOptions.workerSrc = "/js/pdfjs/pdf.worker.min.mjs";
  </script>
  <script>
    document.addEventListener("DOMContentLoaded", async function () {
      if (!existingPdfUrl) return;

      const container = document.getElementById("canvas-container");

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
  });
  </script>
@endsection
