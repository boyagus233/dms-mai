@extends('layouts/contentNavbarLayout')

@section('title', 'Document Upload - Form')

@section('styles')
  <style>
        .controls {
            margin-bottom: 20px;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        button, .file-label {
            padding: 10px 15px;
            border-radius: 4px;
            cursor: pointer;
            border: none;
        }
        .file-label {
            background: #4285f4;
            color: white;
        }
        #download-btn {
            background: #0f9d58;
            color: white;
        }
        #download-btn:disabled {
            background: #cccccc;
            cursor: not-allowed;
        }
        .canvas-container {
            position: relative;
            border: 1px solid #ddd;
            overflow: hidden;
            background: #f9f9f9;
        }
        #pdf-canvas {
            display: block;
        }
        #stamp-container {
            position: absolute;
            top: 0;
            left: 0;
            pointer-events: none;
            width: 100%;
            height: 100%;
        }
        #stamp-image {
            position: absolute;
            pointer-events: auto;
            cursor: move;
            display: none;
            max-width: 300px;
            max-height: 300px;
        }
        .resize-handle {
            position: absolute;
            width: 10px;
            height: 10px;
            background: white;
            border: 2px solid #4285f4;
            border-radius: 50%;
            pointer-events: auto;
            cursor: nwse-resize;
            z-index: 20;
            display: none;
        }
        #resize-handle {
            right: -5px;
            bottom: -5px;
        }
        .file-input {
            display: none;
        }
        .loading {
            display: none;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: rgba(0,0,0,0.7);
            color: white;
            padding: 10px 20px;
            border-radius: 4px;
            z-index: 100;
        }
    </style>
@endsection

@section('content')

<div class="row">
  <div class="col-xxl">
    <div class="card mb-4">
      <div class="card-header d-flex align-items-center justify-content-between">
        <h5 class="mb-0">Check Document</h5> <small class="text-muted float-end"></small>
      </div>
      <div class="card-body">
        <form id="documentform" action="{{ route('document-required.save-checked') }}" method="post" enctype="multipart/form-data">
          @csrf
              <input type="hidden" id="id" name="id" value="{{ $documentUpload->id }}">
              <input type="hidden" id="departement" name="departement" value="{{ $documentUpload->departement }}">
              <input type="hidden" name="document-number" id="document-number" value="{{ $documentUpload->document_no }}">
              <input type="hidden" id="document-form-name" name="document-form-name" value="{{ $documentUpload->document_name }}">
          <div class="row mb-3">
            <label class="col-sm-3 col-form-label text-end">Document No</label>
            <div class="col-sm-6">
              <input type="text" class="form-control" id="document-name" name="document-name" placeholder="Document Name" value="{{ $documentUpload->document_no }}" readonly/>
            </div>
          </div>
          <div class="row mb-3">
            <label class="col-sm-3 col-form-label text-end">Document Name</label>
            <div class="col-sm-6">
              <input type="text" class="form-control" id="document-name" name="document-name" placeholder="Document Name" value="{{ $documentUpload->document_name }}" readonly/>
            </div>
          </div>
          <div class="row mb-3">
            <label class="col-sm-3 col-form-label text-end" for="departement">Departement*</label>
            <div class="col-sm-6">
              <select class="form-select" disabled>
                <option value="">Select Departement</option>
                @foreach ($departement as $item)
                    <option value="{{ $item->id }}" data-code="{{ $item->code }}"
                      {{ old('departement', $documentUpload->departement) === $item->id ? 'selected' : '' }}>
                      {{ $item->code }} - {{ $item->name }}
                    </option>
                @endforeach
              </select>
            </div>
          </div>
          <div class="row mb-3">
            <label class="col-sm-3 col-form-label text-end" for="departementstag">Departements tag</label>
            <div class="col-sm-6">
              <select name="departementstag[]" multiple id="departementstag" class="form-select">
                <option value="">Select Departement</option>
                @foreach($departement as $item)
                  <option value="{{ $item->id }}" data-code="{{ $item->code }}">
                      {{ in_array($item->id, old('departement', $selectedDepartments ?? [])) ? 'selected' : '' }}>
                      {{ $item->code }} - {{ $item->name }}
                  </option>
                @endforeach
              </select>
            </div>
          </div>
          <div class="row mb-3">
            <label class="col-sm-3 col-form-label text-end" for="employee">Assign To*</label>
            <div class="col-sm-6">
              <select name="employee" id="employee" class="form-select" required>
                <option value="">Select Employee</option>
                @foreach ($employee as $key => $item)
                    <option value="{{ $key }}"
                      {{ old('assign_to', $documentUpload->assign_to) === $key ? 'selected' : '' }}>
                      {{ $item }}
                    </option>
                @endforeach
              </select>
            </div>
          </div>
          <div class="row mb-3">
            <label class="col-sm-3 col-form-label text-end" for="employee">Assign To (2)*</label>
            <div class="col-sm-6">
              <select name="employee2" id="employee2" class="form-select" required>
                <option value="">Select Employee</option>
                @foreach ($employee as $key => $item)
                    <option value="{{ $key }}"
                      {{ old('assign_to', $documentUpload->assign_to_2) === $key ? 'selected' : '' }}>
                      {{ $item }}
                    </option>
                @endforeach
              </select>
            </div>
          </div>
          <div class="row mb-3">
            <label class="col-sm-3 col-form-label text-end" for="reason">Reason Revision</label>
            <div class="col-sm-6">
              <textarea rows="3" class="form-control" id="reason" name="reason" placeholder="reason revision" required>{{ $documentUpload->reason_revisi }}</textarea>
            </div>
          </div>
          <div class="row mb-3">
            <label class="col-sm-3 col-form-label text-end" for="document">Document File</label>
            <div class="col-sm-6">
              <div class="input-group mb-3">
                @if (!empty($documentUpload->file_name))
                    <input type="hidden" name="old_file" value="{{ $documentUpload->file_name }}">
                @endif
                <input type="hidden" value="{{ $documentUpload->file_name }}" name="document" id="document">
                <input type="file" class="form-control" id="pdf-upload" name="pdf-upload" accept=".pdf, .doc, .docx, .xls, .xlsx" style="display: none;">
                <input type="text" class="form-control" id="file-name" value="{{ $documentUpload->file_name }}" placeholder="No file chosen" readonly>
                <button class="btn btn-outline-secondary" type="button" id="browse-button">Browse</button>
              </div>
            </div>
          </div>
          <div class="row mb-3">
            <label class="col-sm-3 col-form-label text-end"></label>
            <div class="col-sm-6">
              <div class="input-group mb-3">
                <button type="button" class="btn btn-outline-primary btn-sm" id="prev">< Previous</button>
                <input type="text" id="pageNumber" name="pageNumber" value="-" readonly placeholder="Page number" style="text-align: center">
                <button type="button" class="btn btn-outline-primary btn-sm" id="next">Next ></button>
              </div>
            </div>
          </div>
          <div class="row mb-3 d-none">
            <label class="col-sm-3 col-form-label text-end">Stamp File</label>
            <div class="col-sm-6">
              <div class="input-group mb-3">
                <input type="file" class="form-control" id="stamp-upload" name="stamp-upload" accept="image/*" style="display: none;">
                <input type="text" class="form-control" id="file-stamp-name" readonly>
                <button class="btn btn-outline-secondary" type="button" id="browse-stamp-button">Browse</button>
              </div>
            </div>
          </div>
          <div class="row mb-3">
            <label class="col-sm-3 col-form-label text-end"></label>
            <div class="col-sm-6">
              <div class="canvas-container">
                <input type="hidden" id="stampX" name="stampX">
                <input type="hidden" id="stampY" name="stampY">
                <input type="hidden" id="canvasHeight" name="canvasHeight">
                <input type="hidden" id="canvasWidth" name="canvasWidth">
                <input type="hidden" id="stampWidth" name="stampWidth">
                <input type="hidden" id="stampHeight" name="stampHeight">
                <div class="loading">Loading...</div>
                <canvas id="pdf-canvas"></canvas>
                <div id="stamp-container">
                    <img id="stamp-image" draggable="false">
                    <div id="resize-handle" class="resize-handle"></div>
                </div>
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

          <div class="row mb-3">
            <div class="col-sm-3">
            </div>
            <div class="col-sm-6">
              <a href="{{ route('document-required.index') }}" class="btn btn-primary" role="button">Cancel</a>
              <button type="submit" name="action" value="reject" id="rejectBtn" class="btn btn-danger">Reject</button>
              <button type="submit" name="action" value="edit" id="submitBtn" class="btn btn-success">Submit</button>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
@endsection
@section('scripts')

<script src="{{ asset('js/pdf.min.js') }}"></script>
<script src="{{ asset('js/jspdf.umd.min.js') }}"></script>

    <script>
        // Initialize PDF.js
        pdfjsLib.GlobalWorkerOptions.workerSrc = "/js/pdf.worker.min.js"
        const { jsPDF } = window.jspdf;

        // DOM elements
        const pdfUpload = document.getElementById('pdf-upload');
        const stampUpload = document.getElementById('stamp-upload');
        const downloadBtn = document.getElementById('download-btn');
        const pdfCanvas = document.getElementById('pdf-canvas');
        const ctx = pdfCanvas.getContext('2d');
        const stampImage = document.getElementById('stamp-image');
        const stampContainer = document.getElementById('stamp-container');
        const resizeHandle = document.getElementById('resize-handle');
        const canvasContainer = document.querySelector('.canvas-container');
        const loadingIndicator = document.querySelector('.loading');

        const pageNumEl = document.getElementById("pageNumber");
        const pageCountEl = document.getElementById("page-count");
        const prevBtn = document.getElementById("prev");
        const nextBtn = document.getElementById("next");

        // Variables
        let pdfDoc = null;
        let scale = 1.5;
        let stampX = 100;
        let stampY = 100;
        let stampWidth = 0;
        let stampHeight = 0;
        let isDragging = false;
        let isResizing = false;
        let offsetX, offsetY; // Track click position within the stamp
        let startWidth, startHeight;

        // Upload PDF
        pdfUpload.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                showLoading();
                const fileReader = new FileReader();
                fileReader.onload = function() {
                    const typedArray = new Uint8Array(this.result);
                    loadPDF(typedArray);
                };
                fileReader.readAsArrayBuffer(file);
            }
        });

        // Upload Stamp
        stampUpload.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(event) {
                    stampImage.onload = function() {
                        // Show the stamp image and resize handle
                        stampImage.style.display = 'block';
                        resizeHandle.style.display = 'block';

                        // Set initial dimensions (max 300px but preserve aspect ratio)
                        const maxSize = 300;
                        stampWidth = this.naturalWidth;
                        stampHeight = this.naturalHeight;

                        if (stampWidth > stampHeight && stampWidth > maxSize) {
                            stampHeight = (maxSize / stampWidth) * stampHeight;
                            stampWidth = maxSize;
                        } else if (stampHeight > maxSize) {
                            stampWidth = (maxSize / stampHeight) * stampWidth;
                            stampHeight = maxSize;
                        }

                        stampImage.style.width = stampWidth + 'px';
                        stampImage.style.height = stampHeight + 'px';

                        // Center the stamp if PDF is already loaded
                        if (pdfCanvas.width > 0) {
                            stampX = (pdfCanvas.width - stampWidth) / 2;
                            stampY = (pdfCanvas.height - stampHeight) / 2;
                            updateStampPosition();
                        }

                        downloadBtn.disabled = false;
                    };
                    stampImage.src = event.target.result;
                };
                reader.readAsDataURL(file);
            }
        });

        // Load PDF function
        function loadPDF(data) {
            pdfjsLib.getDocument(data).promise.then(function(pdf) {
                pdfDoc = pdf;
                //currentPage = pdfDoc.numPages;
                currentPage = 1;
                renderPage(currentPage);
            });
        }

        // Render PDF page
        function renderPage(num) {
            pdfDoc.getPage(num).then(function(page) {
                const viewport = page.getViewport({ scale: scale });
                pdfCanvas.height = viewport.height;
                pdfCanvas.width = viewport.width;

                // Set container size to match canvas
                canvasContainer.style.width = viewport.width + 'px';
                canvasContainer.style.height = viewport.height + 'px';
                stampContainer.style.width = viewport.width + 'px';
                stampContainer.style.height = viewport.height + 'px';

                const renderContext = {
                    canvasContext: ctx,
                    viewport: viewport
                };

                page.render(renderContext).promise.then(function() {
                    // Reposition stamp after PDF renders if it exists
                    if (stampImage.style.display === 'block') {
                        stampX = (pdfCanvas.width - stampWidth) / 2;
                        stampY = (pdfCanvas.height - stampHeight) / 2;
                        updateStampPosition();
                    }
                    hideLoading();
                });

                document.getElementById('pageNumber').value = num;

                // Enable/disable buttons
                prevBtn.disabled = (num === 1);
                nextBtn.disabled = (num === pdfDoc.numPages);
            });
        }

        // Button events
        prevBtn.addEventListener("click", () => {
            if (currentPage > 1) {
                currentPage--;
                renderPage(currentPage);
            }
        });

        nextBtn.addEventListener("click", () => {
            if (currentPage < pdfDoc.numPages) {
                currentPage++;
                renderPage(currentPage);
            }
        });

        // Update stamp position and size
        function updateStampPosition() {
            stampImage.style.left = stampX + 'px';
            stampImage.style.top = stampY + 'px';
            stampImage.style.width = stampWidth + 'px';
            stampImage.style.height = stampHeight + 'px';

            // Position resize handle
            resizeHandle.style.left = (stampX + stampWidth - 5) + 'px';
            resizeHandle.style.top = (stampY + stampHeight - 5) + 'px';
        }

        // Drag functionality - FIXED VERSION (precise cursor tracking)
        stampImage.addEventListener('mousedown', function(e) {
            if (e.target === resizeHandle) return;

            isDragging = true;

            // Calculate offset within the stamp image
            const stampRect = stampImage.getBoundingClientRect();
            offsetX = e.clientX - stampRect.left;
            offsetY = e.clientY - stampRect.top;

            stampImage.style.cursor = 'grabbing';
            e.preventDefault();
        });

        // Resize functionality
        resizeHandle.addEventListener('mousedown', function(e) {
            isResizing = true;
            startX = e.clientX;
            startY = e.clientY;
            startWidth = stampWidth;
            startHeight = stampHeight;
            e.stopPropagation();
            e.preventDefault();
        });

        document.addEventListener('mousemove', function(e) {
            const containerRect = canvasContainer.getBoundingClientRect();

            if (isDragging) {
                // Calculate new position based on cursor offset
                let newX = e.clientX - containerRect.left - offsetX;
                let newY = e.clientY - containerRect.top - offsetY;

                // Boundary checking
                newX = Math.max(0, Math.min(newX, pdfCanvas.width - stampWidth));
                newY = Math.max(0, Math.min(newY, pdfCanvas.height - stampHeight));

                // Update position
                stampX = newX;
                stampY = newY;
                updateStampPosition();
            } else if (isResizing) {
                // Calculate new size (maintain aspect ratio)
                const dx = e.clientX - startX;
                const dy = e.clientY - startY;
                const scaleFactor = 1 + (dx / startWidth);

                // Limit minimum and maximum size
                const newWidth = Math.max(50, Math.min(500, startWidth * scaleFactor));
                const newHeight = Math.max(50, Math.min(500, startHeight * scaleFactor));

                // Update dimensions
                stampWidth = newWidth;
                stampHeight = newHeight;

                updateStampPosition();
            }
        });

        document.addEventListener('mouseup', function() {
            isDragging = false;
            isResizing = false;
            stampImage.style.cursor = 'move';
        });

        // Download stamped PDF
        downloadBtn.addEventListener('click', function() {
            if (!pdfDoc || stampImage.style.display !== 'block') return;

            showLoading();

            // Create a new jsPDF instance
            const pdf = new jsPDF({
                orientation: pdfCanvas.width > pdfCanvas.height ? 'l' : 'p',
                unit: 'pt',
                format: [pdfCanvas.width, pdfCanvas.height]
            });

            // Add PDF page as background
            pdf.addImage(pdfCanvas.toDataURL('image/png'), 'PNG', 0, 0, pdfCanvas.width, pdfCanvas.height);

            // Add stamp image at current position and size
            pdf.addImage(
                stampImage.src,
                'PNG',
                stampX,
                stampY,
                stampWidth,
                stampHeight
            );

            // Save the PDF
            pdf.save('stamped-document.pdf');
            hideLoading();
        });

        // Helper functions
        function showLoading() {
            loadingIndicator.style.display = 'block';
        }

        function hideLoading() {
            loadingIndicator.style.display = 'none';
        }
    </script>

    <script>
      document.addEventListener("DOMContentLoaded", function () {
        if (!existingPdfUrl) return;

        fetch(existingPdfUrl)
          .then(response => {
            if (!response.ok) {
              throw new Error(`Failed to fetch PDF: ${response.status}`);
            }
            return response.arrayBuffer();
          })
          .then(arrayBuffer => {
            const typedArray = new Uint8Array(arrayBuffer);
            console.log('PDF as Uint8Array:', typedArray);

            showLoading();
            const file = new Blob([typedArray], { type: 'application/pdf' });
            const fileReader = new FileReader();
            fileReader.onload = function() {
                const typedArray = new Uint8Array(this.result);
                loadPDF(typedArray);
            };
            fileReader.readAsArrayBuffer(file);
          })
          .catch(error => {
            console.error('Error loading PDF:', error);
          });

      });
    </script>

    <script>
      const fileInput = document.getElementById('pdf-upload');
      const browseButton = document.getElementById('browse-button');
      const fileNameField = document.getElementById('file-name');

      browseButton.addEventListener('click', function () {
        fileInput.click();
      });

      fileInput.addEventListener('change', function () {
        fileNameField.value = this.files.length > 0 ? this.files[0].name : 'No file chosen';
      });

      const fileInputStamp = document.getElementById('stamp-upload');
      const browseButtonStamp = document.getElementById('browse-stamp-button');
      const fileNameStamp = document.getElementById('file-stamp-name');

      browseButtonStamp.addEventListener('click', function () {
        fileInputStamp.click();
      });

      fileInputStamp.addEventListener('change', function () {
        fileNameStamp.value = this.files.length > 0 ? this.files[0].name : 'No file chosen';
      });
    </script>
    <script>
      document.getElementById('documentform').addEventListener('submit', function (e) {
        document.getElementById('stampX').value = stampX;
        document.getElementById('stampY').value = stampY;
        document.getElementById('stampWidth').value = stampWidth;
        document.getElementById('stampHeight').value = stampHeight;
        document.getElementById('canvasWidth').value = pdfCanvas.width;
        document.getElementById('canvasHeight').value = pdfCanvas.height;
      });
    </script>
    <script>
      const form = document.getElementById('documentform');
      const reason = document.getElementById('reason');
      const submitBtn = document.getElementById('submitBtn');
      const rejectBtn = document.getElementById('rejectBtn');

      submitBtn.addEventListener('click', () => {
        reason.required = false;  // Not required on submit
      });

      rejectBtn.addEventListener('click', () => {
        reason.required = true;   // Required on reject
      });
    </script>
@endsection
