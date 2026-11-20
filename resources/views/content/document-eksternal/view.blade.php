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
    <div class="card mb-1">
      <div class="card-header d-flex align-items-center justify-content-between">
        <h5 class="mb-0">View Document Eksternal Type</h5> <small class="text-muted float-end"></small>
      </div>
      <div class="card-body">
        <form>
          @csrf
          <div class="row mb-0">
            <label class="col-sm-3 col-form-label text-end">Eksternal Type</label>
            <div class="col-sm-6">
              <label class="col-form-label">{{ $documentEksternal->document_eksternal_type }}</label>
            </div>
          </div>
          <div class="row mb-0">
            <label class="col-sm-3 col-form-label text-end">Document No</label>
            <div class="col-sm-6">
              <label class="col-form-label">{{ $documentEksternal->document_no }}</label>
            </div>
          </div>
          <div class="row mb-0">
            <label class="col-sm-3 col-form-label text-end">Document Name</label>
            <div class="col-sm-6">
              <label class="col-form-label">{{ $documentEksternal->document_name }}</label>
            </div>
          </div>
          <div class="row mb-0">
            <label class="col-sm-3 col-form-label text-end">Revisi</label>
            <div class="col-sm-6">
              <label class="col-form-label">{{ $documentEksternal->revisi }}</label>
            </div>
          </div>
          <div class="row mb-0">
            <label class="col-sm-3 col-form-label text-end">Status</label>
            <div class="col-sm-9">
              <label class="col-form-label">{{ strtoupper($documentEksternal->status) === 'ACTIVE' ? 'Active' : 'Obsolete' }}</label>
            </div>
          </div>
          <div class="row mb-0">
            <label class="col-sm-3 col-form-label text-end">Document File</label>
            <div class="col-sm-8">
              @php
                // Menggunakan storage_path agar otomatis menyesuaikan folder root (Windows/Linux VPS)
                $relativeFolder = 'app/public/eksternal-document/';
                $fileName = $documentEksternal->file_name;
                $absolutePath = storage_path($relativeFolder . $fileName);

                $fileExists = !empty($fileName) && file_exists($absolutePath);

                // Path untuk tampilan user agar tidak terlalu teknis
                $displayPath = "ROOT/" . $relativeFolder . $fileName;
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
                      <span class="small">
                        <b>Solusi:</b> Pastikan file tersebut ada di folder <code>storage/app/public/eksternal-document/</code>
                        atau silakan <b>Upload Ulang</b> melalui menu Edit.
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
                            const existingPdfUrl = "{{ asset('storage/eksternal-document/' . $documentEksternal->file_name) }}";
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
            </div>
          </div>
          <div class="row mb-3">
            <div class="col-sm-3">
            </div>
            <div class="col-sm-6">
              <a href="{{ route('document-eksternal.index') }}" class="btn btn-secondary" role="button">Back</a>
            </div>
          </div>
        </form>
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
          // Verify file existence first
          const response = await fetch(existingPdfUrl, { method: 'HEAD' });
          if (!response.ok) {
              throw new Error(`File not found at ${existingPdfUrl} (Status: ${response.status})`);
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
          container.innerHTML = `<div class="alert alert-danger">Error loading PDF: ${error.message}</div>`;
      }
    });
  </script>
@endsection
