@extends('layouts/contentNavbarLayout')

@section('title', 'Document Upload - Form')

@section('styles')
  <!-- Select2 -->
  <link href="/vendor/select2/dist/css/select2.min.css?v=20240303114000" rel="stylesheet">
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

@section('page-script')
@endsection

@section('content')

<div class="row">
    @php
      if ($action === 'check') {
        $formlabel = 'Checking Document';
      } elseif ($action === 'approve') {
        $formlabel = 'Approve Document';
      } elseif ($action === 'obsolete') {
        $formlabel = 'Obsolete Document';
      } elseif ($action === 'revise') {
        $formlabel = 'Revise Document';
      } else {
        $formlabel = 'Edit Document';
      }
    @endphp
    @php
      if (($action === 'edit' || $action === 'revise' || $action === 'doccontrol') && $documentUpload->is_obsolete == 0) {
        $isReadOnly = false;
      } elseif ($action === 'doccontrol' && $documentUpload->is_obsolete == 1) {
          $isReadOnly = true;
      } elseif ($action === 'check' || $action === 'approve') {
          $isReadOnly = true;
      } else {
          $isReadOnly = true;
      }
    @endphp
    @php
      $visibilityClass = 'd-none';
      // if ($action === 'stamp' || $action === 'approve') {
      //     $visibilityClass = '';
      // } elseif ($action === 'check') {
      //     $visibilityClass = 'd-none';
      // } else {
      //     $visibilityClass = 'd-none';
      // }
    @endphp
    @php
      if ($action === 'edit' || $action === 'stamp' || $action === 'delete') {
          $visibilityClassReason = 'd-none';
      } elseif ($action === 'doccontrol' || $action === 'check' || $action === 'approve' || $action === 'revise' || $action === 'obsolete') {
          $visibilityClassReason = '';
      } else {
          $visibilityClassReason = 'd-none';
      }
    @endphp
    @php
      if ($action === 'check' || $action === 'approve' || $action === 'obsolete') {
          $visibilitycheckerapprover = 'd-none';
          $visibilitycheckerapproverdisplay = '';
      } else {
          $visibilitycheckerapprover = '';
          $visibilitycheckerapproverdisplay = 'd-none';
      }
    @endphp

  <div class="col-xxl">
    <div class="card mb-2">
      <div class="card-header d-flex align-items-center justify-content-between">
        <h5 class="mb-0">{{ $formlabel }}</h5> <small class="text-muted float-end"></small>
      </div>
      <div class="card-body">
        <form id="documentform" action="{{ route('document-upload.store') }}" method="post" enctype="multipart/form-data">
          @csrf
              <input type="hidden" id="id" name="id" value="{{ $documentUpload->id }}">
              <input type="hidden" id="viewtype" name="viewtype" value="{{ $viewtype }}">
              <input type="hidden" id="departement" name="departement" value="{{ $documentUpload->departement }}">
              <input type="hidden" name="document-no" id="document-no" value="{{ $documentUpload->document_no }}">
              <input type="hidden" name="doc-type-code" id="doc-type-code" value="{{ $documentUpload->document_type }}">
              <input type="hidden" name="doc-classification-code" id="doc-classification-code" value="{{ $documentUpload->departement }}">
              <input type="hidden" name="doc-function-code" id="doc-function-code" value="{{ $documentUpload->document_classification }}">
              <input type="hidden" name="doc-form-code" id="doc-form-code" value="{{ $documentUpload->document_function }}">
              <input type="hidden" name="document-number" id="document-number" value="{{ $documentUpload->document_no }}">
              <input type="hidden" id="document-form-name" name="document-form-name" value="{{ $documentUpload->document_name }}">
              <input type="hidden" id="checker-name" name="checker-name" value="{{ $documentUpload->checker_name }}">
              <input type="hidden" id="approver-name" name="approver-name" value="{{ $documentUpload->approver_name }}">
          <div class="row mb-1">
            <label class="col-sm-3 col-form-label text-end">Document No</label>
            <div class="col-sm-6">
              <input type="text" class="form-control" value="{{ $documentUpload->document_no }}" readonly/>
            </div>
          </div>
          <div class="row mb-1">
            <label class="col-sm-3 col-form-label text-end">Document Name</label>
            <div class="col-sm-6">
              <input type="text" class="form-control" id="document-name" name="document-name" value="{{ $documentUpload->document_name }}" readonly/>
            </div>
          </div>
          <div class="row mb-1">
            <label class="col-sm-3 col-form-label text-end">Departement</label>
            <div class="col-sm-6">
              <input type="text" class="form-control" id="departement-name" name="departement-name" value="{{ $documentUpload->departement_name }}" readonly/>
            </div>
          </div>
          <div class="row mb-1">
            <label class="col-sm-3 col-form-label text-end" for="departementstag">Departements tag</label>
            <div class="col-sm-6">
              <select name="departementstag[]" multiple id="departementstag" class="form-select" required>
                <option value="">Select Departement</option>
                  @foreach ($departement as $item)
                    <option value="{{ $item->id }}" data-code="{{ $item->code }}"
                      {{ in_array($item->id, old('departements', $selectedDepartments ?? [])) ? 'selected' : '' }}>
                      {{ $item->code }} - {{ $item->name }}
                    </option>
                @endforeach
              </select>
            </div>
          </div>
          <div id="divdisplaycheckerapprover" class="{{ $visibilitycheckerapproverdisplay }}">
            <div class="row mb-1">
              <label class="col-sm-3 col-form-label text-end">Checker*</label>
              <div class="col-sm-6">
                <input type="text" class="form-control" value="{{ $documentUpload->checker_name }}" readonly/>
              </div>
            </div>
            <div class="row mb-1">
              <label class="col-sm-3 col-form-label text-end">Approver*</label>
              <div class="col-sm-6">
                <input type="text" class="form-control" value="{{ $documentUpload->approver_name }}" readonly/>
              </div>
            </div>
          </div>
          <div id="divcheckerapprover" class="{{ $visibilitycheckerapprover }}">
            <div class="row mb-1">
            <label class="col-sm-3 col-form-label text-end" for="checker">Checker*</label>
            <div class="col-sm-6">
              <select name="checker" id="checker" class="form-select" required>
                <option value="">Select Employee</option>
                @foreach ($employees as $departmentCode => $group)
                  <optgroup label="{{ $departmentCode }}">
                      @foreach ($group as $employee)
                        <option value="{{ $employee->id }}"
                          {{ old('checker', $documentUpload->checker) === $employee->id ? 'selected' : '' }}>
                          {{ $employee->employee_name }}
                        </option>
                      @endforeach
                  </optgroup>
                @endforeach
              </select>
            </div>
            </div>
            <div class="row mb-1">
              <label class="col-sm-3 col-form-label text-end" for="approver">Approver*</label>
              <div class="col-sm-6">
                <select name="approver" id="approver" class="form-select" required>
                  <option value="">Select Employee</option>
                  @foreach ($employees as $departmentCode => $group)
                    <optgroup label="{{ $departmentCode }}">
                        @foreach ($group as $employee)
                          <option value="{{ $employee->id }}"
                            {{ old('checker', $documentUpload->approver) === $employee->id ? 'selected' : '' }}>
                            {{ $employee->employee_name }}
                          </option>
                        @endforeach
                    </optgroup>
                  @endforeach
                </select>
              </div>
            </div>
          </div>
          <div class="row mb-1 {{ $visibilityClassReason }}">
            @if($action === 'obsolete')
              <label class="col-sm-3 col-form-label text-end" for="reason">Obsolete Notes</label>
              <div class="col-sm-6">
                <textarea rows="3" class="form-control" id="reason" name="reason" placeholder="obsolete or notes"></textarea>
              </div>
            @else
              <label class="col-sm-3 col-form-label text-end" for="reason">Notes</label>
              <div class="col-sm-6">
                <textarea rows="3" class="form-control" id="reason" name="reason" placeholder="reason or notes">{{ $documentUpload->reason_revisi }}</textarea>
              </div>
            @endif
          </div>
          <div class="row mb-1">
            <label class="col-sm-3 col-form-label text-end">Document File</label>
            <div class="col-sm-6">
              <div class="input-group mb-3">
                @if (!empty($documentUpload->file_name))
                    <input type="hidden" name="old_file" value="{{ $documentUpload->file_name }}">
                    <input type="text" style="display: none;" name="isdocumentexist" id="isdocumentexist">
                @else
                    <input type="hidden" name="old_file">
                    <input type="text" style="display: none;" name="isdocumentexist" id="isdocumentexist" required>
                @endif
                <input type="hidden" value="{{ $documentUpload->file_name }}" name="document" id="document">
                <input type="file" class="form-control" id="pdf-upload" name="pdf-upload" accept=".pdf, .doc, .docx, .xls, .xlsx" style="display: none;">
                <input type="text" class="form-control" id="file-name" value="{{ $documentUpload->file_name }}" placeholder="No file chosen" readonly>
                <button class="btn btn-outline-secondary" type="button" id="browse-button" {{ $isReadOnly ? 'disabled' : '' }}>Browse</button>
              </div>
            </div>
          </div>
          <div class="row mb-1">
            <label class="col-sm-3 col-form-label text-end"></label>
            <div class="col-sm-6">
              <div class="input-group mb-3">
                <button type="button" class="btn btn-outline-primary btn-sm" id="prev">< Previous</button>
                <input type="text" id="pageNumber" name="pageNumber" value="-" readonly placeholder="Page number" style="text-align: center">
                <button type="button" class="btn btn-outline-primary btn-sm" id="next">Next ></button>
              </div>
            </div>
          </div>
          <div class="row mb-1 {{ $visibilityClass }}">
            <label class="col-sm-3 col-form-label text-end">Stamp File</label>
            <div class="col-sm-6">
              <div class="input-group mb-3">
                <input type="file" class="form-control" id="stamp-upload" name="stamp-upload" accept="image/*" style="display: none;">
                <input type="text" class="form-control" id="file-stamp-name" readonly>
                <button class="btn btn-outline-secondary" type="button" id="browse-stamp-button">Browse</button>
              </div>
            </div>
          </div>
          <div class="row mb-1">
            <label class="col-sm-3 col-form-label text-end">Attachment File</label>
            <div class="col-sm-6">
              <div class="input-group mb-3">
                <input type="file" class="form-control" id="file-attachment" name="file-attachment" accept=".pdf, .doc, .docx, .xls, .xlsx" style="display: none;">
                <input type="text" class="form-control" id="file-attachment-name" value="{{ $documentUpload->file_attachment }}" placeholder="No file chosen" readonly>
                <button class="btn btn-outline-secondary" type="button" id="browse-button-attachment" {{ $isReadOnly ? 'disabled' : '' }}>Browse</button>
              </div>
            </div>
          </div>
          <div class="row mb-1">
            <label class="col-sm-3 col-form-label text-end"></label>
            <div class="col-sm-6">
              <div class="canvas-container" style="background-color: #fff; border-color: #fff;">
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
                @if(!empty($signature))
                    <input type="hidden" id="signature" name="signature" value="{{ $signature }}">
                    <script>
                        const existingSignature = "{{ asset('storage/signature/' . $signature) }}";
                    </script>
                @else
                    <input type="hidden" id="signature" name="signature">
                    <script>
                        const existingSignature = null;
                    </script>
                @endif
              </div>
            </div>
          </div>

          <div class="row mb-2">
            <div class="col-sm-3">
            </div>
            <div class="col-sm-6">
              <a href="{{ route('document-upload.index',['viewtype' => $viewtype]) }}" class="btn btn-primary" role="button">Cancel</a>
              @if ($action === 'stamp')
                  <button type="submit" name="action" value="stamp-{{ $viewtype }}" class="btn btn-success">Submit stamp</button>
              @elseif ($action === 'doccontrol')
                  <button type="submit" name="action" value="rejectByDc-{{ $viewtype }}" id="rejectByDcBtn" class="btn btn-danger">Reject By DC</button>
                  <button type="submit" name="action" value="checkByDc-{{ $viewtype }}" id="checkByDcBtn" class="btn btn-success">Submit By DC</button>
              @elseif ($action === 'check')
                  <button type="submit" name="action" value="rejectByChecker-{{ $viewtype }}" id="rejectByCheckerBtn" class="btn btn-danger">Reject By Checker</button>
                  <button type="submit" name="action" value="approveByChecker-{{ $viewtype }}" id="approveByCheckerBtn" class="btn btn-success">Submit By Checker</button>
              @elseif ($action === 'approve')
                  <button type="submit" name="action" value="rejectByApprover-{{ $viewtype }}" id="rejectByApprovalBtn" class="btn btn-danger">Reject By Approver</button>
                  <button type="submit" name="action" value="approveByApprover-{{ $viewtype }}" id="approveByApprovalBtn" class="btn btn-success">Submit  By Approver</button>
              @elseif ($action === 'edit')
                  <button type="submit" name="action" value="edit-{{ $viewtype }}" class="btn btn-success">Submit edit data</button>
              @elseif ($action === 'obsolete')
                  <button type="submit" name="action" value="obsolete-{{ $viewtype }}" id="submitByObsoleteBtn" class="btn btn-success">Submit obsolete</button>
              @elseif ($action === 'revise')
                  <button type="submit" name="action" value="revise-{{ $viewtype }}" id="submitByReviseBtn" class="btn btn-success">Submit Revise</button>
              @else
                  <p></p>
              @endif


            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

@endsection
@section('scripts')
<!-- Select2 -->
<script type="module" src="/vendor/select2/dist/js/select2.full.min.js?v=20240303114000"></script>
<script type="module">
  $("#departementstag").select2({
      placeholder: " Select departement tags",
      allowClear: true,
  });
</script>

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
                    };
                    stampImage.src = event.target.result;
                };
                reader.readAsDataURL(file);
            }
        });

        //Load PDF function
        function loadPDF(data) {
            pdfjsLib.getDocument(data).promise.then(function(pdf) {
                pdfDoc = pdf;
                //currentPage = pdfDoc.numPages;
                currentPage = 1;
                renderPage(currentPage);
            });
        }

        // function loadPDFFile(typedArray) {
        //   num = 1;
        //   return pdfjsLib.getDocument(typedArray).promise.then(pdf => {
        //     pdfDoc = pdf;
        //     return pdfDoc.getPage(1).then(page => {
        //       const viewport = page.getViewport({ scale: scale });
        //       pdfCanvas.height = viewport.height;
        //       pdfCanvas.width = viewport.width;

        //       // Set container size to match canvas
        //       canvasContainer.style.width = viewport.width + 'px';
        //       canvasContainer.style.height = viewport.height + 'px';
        //       stampContainer.style.width = viewport.width + 'px';
        //       stampContainer.style.height = viewport.height + 'px';

        //       const renderContext = {
        //           canvasContext: ctx,
        //           viewport: viewport
        //       };

        //       page.render(renderContext).promise.then(function() {
        //           // Reposition stamp after PDF renders if it exists
        //           if (stampImage.style.display === 'block') {
        //               stampX = (pdfCanvas.width - stampWidth) / 2;
        //               stampY = (pdfCanvas.height - stampHeight) / 2;
        //               updateStampPosition();
        //           }
        //           hideLoading();
        //       });

        //       document.getElementById('pageNumber').value = num;

        //       // Enable/disable buttons
        //       prevBtn.disabled = (num === 1);
        //       nextBtn.disabled = (num === pdfDoc.numPages);

        //       //Return this render promise
        //       return page.render(renderContext).promise;
        //     });
        //   });
        // }

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
                setTimeout(() => {
                  console.log('Loading image after delay...');
                  loadStamp();
                }, 1000);
            };
            fileReader.readAsArrayBuffer(file);
          })
          .catch(error => {
            console.error('Error loading PDF:', error);
          });
      });
    </script>
    <script>
      function loadStamp() {
        if (!existingSignature) return;
        console.log(existingSignature)
        fetch(existingSignature)
          .then(response => response.blob())
          .then(blob => {
              const reader = new FileReader();
              reader.onloadend = function () {
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
                  };
                  stampImage.src = reader.result; // This is the data:image/... base64
              };
              reader.readAsDataURL(blob);
          })
          .catch(error => {
              console.error('Error loading image:', error);
        });
      }
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
        if (this.files.length > 0) {
          document.getElementById('isdocumentexist').value = this.files[0].name;
        }
      });

      const fileInputStamp = document.getElementById('stamp-upload');
      const browseButtonStamp = document.getElementById('browse-stamp-button');
      const fileNameStamp = document.getElementById('file-stamp-name');

      browseButtonStamp.addEventListener('click', function () {
        fileInputStamp.click();
      });

      fileInputStamp.addEventListener('change', function () {
        fileNameStamp.value = this.files.length > 0 ? this.files[0].name : '';
      });

      const fileInputAttch = document.getElementById('file-attachment');
      const browseButtonAttch = document.getElementById('browse-button-attachment');
      const fileNameFieldAttch = document.getElementById('file-attachment-name');

      browseButtonAttch.addEventListener('click', function () {
        fileInputAttch.click();
      });

      fileInputAttch.addEventListener('change', function () {
        fileNameFieldAttch.value = this.files.length > 0 ? this.files[0].name : 'No file chosen';
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

        const selchecker = document.getElementById('checker');
        const checkername = selchecker.options[selchecker.selectedIndex].text;
        const selapprover = document.getElementById('approver');
        const approvername = selapprover.options[selapprover.selectedIndex].text;
        document.getElementById('checker-name').value = checkername;
        document.getElementById('approver-name').value = approvername;
    });
    </script>
    <script>
      const form = document.getElementById('documentform');
      const reason = document.getElementById('reason');

      let action = "{{ $action }}";
      console.log("Action:", action);

      if (action === 'doccontrol') {
        const submitByDcBtn = document.getElementById('checkByDcBtn');
        const rejectByDcBtn = document.getElementById('rejectByDcBtn');
        submitByDcBtn.addEventListener('click', () => {
          reason.required = false;  // Not required on submit
        });

        rejectByDcBtn.addEventListener('click', () => {
          reason.required = true;   // Required on reject
        });
      }

      if ( action === 'check') {
        const submitByCheckerBtn = document.getElementById('checkByCheckerBtn');
        const rejectByCheckerBtn = document.getElementById('rejectByCheckerBtn');
        submitByCheckerBtn.addEventListener('click', () => {
          reason.required = false;  // Not required on submit
        });

        rejectByCheckerBtn.addEventListener('click', () => {
          reason.required = true;   // Required on reject
        });
      }

      if ( action === 'approve') {
        const rejectByApprovalBtn = document.getElementById('rejectByApprovalBtn');
        const submitByApprovalBtn = document.getElementById('approveByApprovalBtn');
        submitByApprovalBtn.addEventListener('click', () => {
          reason.required = false;
        });
        rejectByApprovalBtn.addEventListener('click', () => {
          reason.required = true;
        });
      }

      if ( action === 'revise') {
        const submitByReviseBtn = document.getElementById('submitByReviseBtn');
        submitByReviseBtn.addEventListener('click', () => {
          reason.required = true;
        });
      }

      if ( action === 'obsolete') {
        const submitByObsoleteBtn = document.getElementById('submitByObsoleteBtn');
        submitByObsoleteBtn.addEventListener('click', () => {
          reason.required = true;
        });
      }
    </script>
@endsection
