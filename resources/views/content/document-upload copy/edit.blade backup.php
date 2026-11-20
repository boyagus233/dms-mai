@extends('layouts/contentNavbarLayout')

@section('title', 'Document Upload - Form')

@section('styles')
  <style>
    #canvas-container {
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
  </style>
@endsection

@section('content')

<div class="row">
  <div class="col-xxl">
    <div class="card mb-4">
      <div class="card-header d-flex align-items-center justify-content-between">
        <h5 class="mb-0">Edit Document</h5> <small class="text-muted float-end"></small>
      </div>
      <div class="card-body">
        <form action="{{ route('document-upload.store') }}" method="post" enctype="multipart/form-data">
          @csrf
          <div class="row mb-3">
            <label class="col-sm-3 col-form-label text-end" for="document-level">Document Level*</label>
            <div class="col-sm-6">
              <input type="hidden" id="id" name="id" value="{{ $documentUpload->id }}">
              <input type="hidden" id="document-level" name="document-level" value="{{ $documentUpload->document_level }}">
              <input type="hidden" id="management-process" name="management-process" value="{{ $documentUpload->management_process }}">
              <input type="hidden" id="document-level-code" name="document-level-code">
              <input type="hidden" id="management-process-code" name="management-process-code">
              <input type="hidden" id="document-form-name" name="document-form-name" value="{{ $documentUpload->document_name }}">
              <select class="form-select" disabled>
                <option value="">Select Document Level</option>
                @foreach ($documentLevel as $item)
                    <option value="{{ $item->id }}" data-code="{{ $item->code }}"
                      {{ old('document_level', $documentUpload->document_level) === $item->id ? 'selected' : '' }}>
                      {{ $item->code }} - {{ $item->document_level }}
                    </option>
                @endforeach
              </select>
            </div>
          </div>
          <div class="row mb-3">
            <label class="col-sm-3 col-form-label text-end" for="process-code">Process Code*</label>
            <div class="col-sm-6">
              <select class="form-select" disabled>
                <option value="">Select Management Process</option>
                @foreach ($managementProcess as $item)
                    <option value="{{ $item->id }}" data-code="{{ $item->code }}"
                      {{ old('management_process', $documentUpload->management_process) === $item->id ? 'selected' : '' }}>
                      {{ $item->code }} - {{ $item->name }}
                    </option>
                @endforeach
              </select>
            </div>
          </div>
          <div class="row mb-3">
            <label class="col-sm-3 col-form-label text-end" for="departement">Document Form*</label>
            <div class="col-sm-6">
              <select class="form-select" disabled>
                <option value="">Select Document Form</option>
                @foreach ($documentForm as $item)
                  <option value="{{ $item->id }}" data-name="{{ $item->name }}"
                    {{ old('document_name', $documentUpload->document_name) === $item->name ? 'selected' : '' }}>
                    {{ $item->name }}
                  </option>
                @endforeach
              </select>
            </div>
          </div>
          <div class="row mb-3">
            <label class="col-sm-3 col-form-label text-end">Document No</label>
            <div class="col-sm-6">
              <input type="text" class="form-control" id="document-no" name="document-no" placeholder="Document No" value="{{ $documentUpload->document_no }}" readonly/>
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
            <label class="col-sm-3 col-form-label text-end" for="document">Document File</label>
            <div class="col-sm-6">
              <div class="input-group mb-3">
                @if (!empty($documentUpload->file_name))
                    <input type="hidden" name="old_file" value="{{ $documentUpload->file_name }}">
                @endif
                <input type="hidden" value="{{ $documentUpload->file_name }}" name="document" id="document">
                <input type="file" class="form-control" id="file-upload" name="file-upload" style="display: none;">
                <input type="text" class="form-control" id="file-name" value="{{ $documentUpload->file_name }}" placeholder="No file chosen" readonly>
                <button class="btn btn-outline-secondary" type="button" id="browse-button">Browse</button>
              </div>
            </div>
          </div>
          <div class="row mb-3">
            <label class="col-sm-3 col-form-label text-end" for="document">Stamp File</label>
            <div class="col-sm-6">
              <div class="input-group mb-3">
                <input type="file" class="form-control" id="file-stamp" name="file-stamp" accept=".png, .jpg, .jpeg" style="display: none;">
                <input type="text" class="form-control" id="file-stamp-name" readonly>
                <button class="btn btn-outline-secondary" type="button" id="browse-stamp-button">Browse</button>
              </div>
            </div>
            <div class="col-sm-3">
              <div></div>
	          </div>
          </div>
          <div class="row mb-3">
            <label class="col-sm-3 col-form-label text-end"></label>
            <div class="col-sm-6">
              <div class="input-group mb-3">
                <button type="button" class="btn btn-outline-primary btn-sm" id="prev">< Previous</button>
                <!--<p>&nbsp;<span> Page: <span id="pageNumber" name="pageNumber">1</span> / <span id="page-count" name="page-count">?</span></span>&nbsp;</p>-->
                <input type="text" id="pageNumber" name="pageNumber" value="-" readonly placeholder="Page number" style="text-align: center">
                <button type="button" class="btn btn-outline-primary btn-sm" id="next">Next ></button>
              </div>
            </div>
          </div>
          <div class="row mb-3">
            <label class="col-sm-3 col-form-label text-end"></label>
            <input type="hidden" id="stampX" name="stampX">
            <input type="hidden" id="stampY" name="stampY">
            <input type="hidden" id="canvasHeight" name="canvasHeight">
            <input type="hidden" id="canvasWidth" name="canvasWidth">
            <div class="col-sm-6">
              <div class="input-group mb-3">
                <div class="flex w-full bg-grey-lighter mt-3" id="canvas-container">
                    <div class="draggable" id="previewContainer"> Stamp </div>
                    <canvas class="border-solid border-2 dark:border-zinc-50" id="pdf-canvas"> ~ PDF ~</canvas>
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
              <a href="{{ route('document-upload.index') }}" class="btn btn-primary" role="button">Cancel</a>
              <button type="submit" class="btn btn-success">Submit</button>
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
  <!-- <script>
    document.addEventListener("DOMContentLoaded", function () {
        if (!existingPdfUrl) return;

        const canvas = document.getElementById("pdf-canvas");
        const ctx = canvas.getContext("2d");

        const renderPDF = async (url) => {
            const loadingTask = pdfjsLib.getDocument(url);
            const pdf = await loadingTask.promise;
            const page = await pdf.getPage(1); // Load first page

            const viewport = page.getViewport({ scale: 1.5 }); // You can adjust scale
            canvas.height = viewport.height;
            canvas.width = viewport.width;

            const renderContext = {
                canvasContext: ctx,
                viewport: viewport
            };

            await page.render(renderContext).promise;
        };

        renderPDF(existingPdfUrl).catch(console.error);
    });
  </script> -->

  <!-- image stamp preview -->
  <script>
    document.querySelector("#file-stamp").addEventListener("change", async function(e) {
      const file = e.target.files[0];
        if (!file) return;

        const fileType = file.type;
        const previewContainer = $("#previewContainer");
        previewContainer.empty();
        const blobUrl = URL.createObjectURL(file);

        if (fileType.startsWith('image/')) {
              const img = $("<img id='imagepreview'>").attr("src", blobUrl).css({
              maxWidth: "100%",
              height: "auto",
            });
            previewContainer.append(img);
        }
		});
	</script>
	<!--image stamp preview -->
  <script>
    document.addEventListener("DOMContentLoaded", function () {
        if (!existingPdfUrl) return;

        document.getElementsByClassName('draggable')[0].style.display = 'block';
        const canvas = document.getElementById("pdf-canvas");
        const ctx = canvas.getContext("2d");

        const pageNumEl = document.getElementById("pageNumber");
        const pageCountEl = document.getElementById("page-count");
        const prevBtn = document.getElementById("prev");
        const nextBtn = document.getElementById("next");

        let pdfDoc = null;
        let currentPage = 1;

        const renderPage = async (pageNum) => {
            const page = await pdfDoc.getPage(pageNum);
            const viewport = page.getViewport({ scale: 1.5 });

            canvas.height = viewport.height;
            canvas.width = viewport.width;

            document.getElementById('canvasHeight').value = viewport.height;
            document.getElementById('canvasWidth').value = viewport.width;

            const renderContext = {
                canvasContext: ctx,
                viewport: viewport
            };

            await page.render(renderContext).promise;

            // Update UI
            //pageNumEl.textContent = pageNum;
            //pageCountEl.textContent = pdfDoc.numPages;
            document.getElementById('pageNumber').value = pageNum;

            // Enable/disable buttons
            prevBtn.disabled = (pageNum === 1);
            nextBtn.disabled = (pageNum === pdfDoc.numPages);
        };

        const loadPDF = async (url) => {
            const loadingTask = pdfjsLib.getDocument(url);
            pdfDoc = await loadingTask.promise;

            currentPage = pdfDoc.numPages;
            renderPage(currentPage);
        };

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

        // Load PDF on page load
        loadPDF(existingPdfUrl).catch(console.error);
    });
    </script>

  <script>
        // Predefine the variables
        var pdfDoc = null,
        pageNum = 1,
        pageRendering = false,
        pageNumPending = null,
        scale = 1.5,
        canvas = document.getElementById('pdf-canvas'),
        ctx = canvas.getContext('2d');

        document.querySelector("#file-upload").addEventListener("change", async function(e){
            var file = e.target.files[0]
            if(file.type != "application/pdf"){
                alert(file.name, "is not a pdf file.")
                return
            }

            var fileReader = new FileReader();

            fileReader.onload = async function() {
                var typedarray = new Uint8Array(this.result);

                const loadingTask = pdfjsLib.getDocument(typedarray);
                loadingTask.promise.then(pdf => {
                    // Set pdfDoc to the PDFJS object so we can reference it globally
                    pdfDoc = pdf
                    // Get Page number
                    pageNum = pdfDoc.numPages

                    document.getElementById('pageNumber').value = pageNum;
                    // Last page rendering
                    renderPage(pageNum);
                });
            };

            fileReader.readAsArrayBuffer(file);

            document.getElementsByClassName('draggable')[0].style.display = 'block';
        })

        /**
         * Get page info from document, resize canvas accordingly, and render page.
         * @param num Page number.
         */
         function renderPage(num) {
            pageRendering = true;
            // Using promise to fetch the page
            pdfDoc.getPage(num).then(function(page) {
                var viewport = page.getViewport({scale: scale});
                canvas.height = viewport.height;
                canvas.width = viewport.width;

                document.getElementById('canvasHeight').value = viewport.height;
                document.getElementById('canvasWidth').value = viewport.width;

                // Render PDF page into canvas context
                var renderContext = {
                    canvasContext: ctx,
                    viewport: viewport
                };
                var renderTask = page.render(renderContext);

                // Wait for rendering to finish
                renderTask.promise.then(function() {
                    pageRendering = false;
                    if (pageNumPending !== null) {
                        // New page rendering is pending
                        renderPage(pageNumPending);
                        pageNumPending = null;
                    }
                });
            });
        }

        /**
         * If another page rendering in progress, waits until the rendering is
         * finised. Otherwise, executes rendering immediately.
         */
        function queueRenderPage(num) {
            document.getElementById('pageNumber').value = num;
            if (pageRendering) {
                pageNumPending = num;
            } else {
                renderPage(num);
            }
        }

        /**
         * Displays previous page.
         */
        function onPrevPage() {
            if (pageNum <= 1) {
                return;
            }
            pageNum--;

            queueRenderPage(pageNum);
        }
        document.getElementById('prev').addEventListener('click', onPrevPage);

        /**
         * Displays next page.
         */
        function onNextPage() {
            if (pageNum >= pdfDoc.numPages) {
                return;
            }
            pageNum++;
            queueRenderPage(pageNum);
        }
        document.getElementById('next').addEventListener('click', onNextPage);
    </script>

    <!--<script src="https://cdn.jsdelivr.net/npm/interactjs@1.10.20/dist/interact.min.js"></script>-->
    <script src="{{ asset('js/interact.min.js') }}"></script>

    <script>
        const position = { x: 0, y: 0 }
        interact('.draggable').draggable({
            listeners: {
                move (event) {
                    position.x += event.dx
                    position.y += event.dy

                    event.target.style.transform =
                        `translate(${position.x}px, ${position.y}px)`
                },
                end (event) {
                    var style = window.getComputedStyle(event.target);
                    var matrix = new WebKitCSSMatrix(style.transform);

                    console.log(matrix.m41, matrix.m42)
                    document.getElementById('stampX').value = matrix.m41;
                    document.getElementById('stampY').value = matrix.m42;
                }
            },
            inertia: true,
            modifiers: [
                interact.modifiers.restrictRect({
                    restriction: 'parent',
                    endOnly: true
                })
            ],
        })
    </script>

  <script>
    document.getElementById('document-level').addEventListener('change', function() {
      const option = this.options[this.selectedIndex];
      const code = option.dataset.code;
      document.getElementById('document-level-code').value = code;
    });

    document.getElementById('management-process').addEventListener('change', function() {
      const option = this.options[this.selectedIndex];
      const code = option.dataset.code;
      document.getElementById('management-process-code').value = code;
    });

  </script>
  <script>
    const fileInput = document.getElementById('file-upload');
    const browseButton = document.getElementById('browse-button');
    const fileNameField = document.getElementById('file-name');

    browseButton.addEventListener('click', function () {
      fileInput.click();
    });

    fileInput.addEventListener('change', function () {
      fileNameField.value = this.files.length > 0 ? this.files[0].name : 'No file chosen';
    });
  </script>
  <script>
    const fileInputStamp = document.getElementById('file-stamp');
    const browseButtonStamp = document.getElementById('browse-stamp-button');
    const fileNameStamp = document.getElementById('file-stamp-name');

    browseButtonStamp.addEventListener('click', function () {
      fileInputStamp.click();
    });

    fileInputStamp.addEventListener('change', function () {
      fileNameStamp.value = this.files.length > 0 ? this.files[0].name : 'No file chosen';
    });
  </script>
@endsection
