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
   <style>
        .table-container {
            max-height: 400px; /* Set your preferred height */
            overflow-y: auto;
            border: 1px solid #ccc;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead {
            position: sticky;
            top: 0;
            background: #f9f9f9;
        }

        th, td {
            padding: 8px 12px;
            border: 1px solid #ddd;
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
        <form id="documentform" action="{{ route('document-acknowledge.read-document') }}" method="post">
          @csrf
          <input type="hidden" name="id" value="{{ $documentUpload->id }}">
          <input type="hidden" name="document_no" value="{{ $documentUpload->document_no }}">
          <input type="hidden" name="document_name" value="{{ $documentUpload->document_name }}">
          <input type="hidden" name="departement_id" value="{{ $documentUpload->Departements->id }}">
          <input type="hidden" name="departement_name" value="{{ $documentUpload->Departements->name }}">
          <input type="hidden" name="document_status" value="{{ $documentUpload->document_status }}">
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
            <label class="col-sm-3 col-form-label text-end">Views by</label>
            <div class="col-sm-6">
              <div class="table-container">
                <table>
                    <thead>
                      <tr>
                          <th>Departement</th>
                          <th>Employee</th>
                      </tr>
                    </thead>
                    <tbody>
                      @forelse ($auditrail as $audit)
                          <tr>
                              <td>{{ $audit->departement_name }}</td>
                              <td>{{ $audit->employee_name }}</td>
                          </tr>
                      @empty
                          <tr>
                              <td colspan="3">No data found.</td>
                          </tr>
                      @endforelse
                    </tbody>
                </table>
              </div>
            </div>
          </div>
          <div class="row mb-3">
            <label class="col-sm-3 col-form-label text-end">Creator</label>
            <div class="col-sm-9">
              <label class="col-form-label">{{ $documentUpload->creator_name }}</label>
          </div>
          </div>
          <div class="row mb-3">
            <label class="col-sm-3 col-form-label text-end">Checker</label>
            <div class="col-sm-9">
              <label class="col-form-label">{{ $documentUpload->checker_name }}</label>
            </div>
          </div>
          <div class="row mb-3">
            <label class="col-sm-3 col-form-label text-end">Approver</label>
            <div class="col-sm-9">
              <label class="col-form-label">{{ $documentUpload->approver_name }}</label>
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
          </div>
          <div class="row mb-3">
            <div class="col-sm-3">
            </div>
            <div class="col-sm-6">
              <a href="{{ route('document-acknowledge.index') }}" class="btn btn-secondary" role="button">Back</a>
              <button type="submit" class="btn btn-success">Mark As Read</button>
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
