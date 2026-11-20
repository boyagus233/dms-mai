@extends('layouts/contentNavbarLayout')

@section('title', 'Visual Tagging - All Document Approval')

@section('styles')
<style>
    .pdf-viewer-container {
        position: relative;
        background: #525659;
        padding: 20px;
        border-radius: 8px;
        min-height: 600px;
        overflow: auto;
        display: flex;
        justify-content: center;
        align-items: flex-start;
    }
    .canvas-wrapper {
        position: relative;
        box-shadow: 0 8px 24px rgba(0,0,0,0.3);
        background: #ffffff;
        user-select: none;
    }
    #pdf-canvas {
        display: block;
        width: 100%;
        height: auto;
    }
    .field-tag-box {
        position: absolute;
        border: 2px dashed #666cff;
        background: rgba(102, 108, 255, 0.15);
        border-radius: 4px;
        cursor: move;
        box-sizing: border-box;
        z-index: 10;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 11px;
        font-weight: bold;
        color: #333;
        padding: 2px 4px;
        text-align: center;
        overflow: hidden;
    }
    .field-tag-box:hover {
        border-style: solid;
        box-shadow: 0 0 6px rgba(102, 108, 255, 0.5);
    }
    .tag-delete-btn {
        position: absolute;
        top: 2px;
        right: 2px;
        width: 16px;
        height: 16px;
        background: #ea5455;
        color: #ffffff;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 10px;
        cursor: pointer;
        line-height: 1;
        z-index: 20;
    }
    .tag-resize-handle {
        position: absolute;
        bottom: 0;
        right: 0;
        width: 10px;
        height: 10px;
        background: #666cff;
        cursor: nwse-resize;
        z-index: 20;
    }
    .tool-palette-btn {
        width: 100%;
        margin-bottom: 8px;
        text-align: left;
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 10px 14px;
        border-radius: 6px;
        font-weight: 500;
        transition: all 0.2s;
    }
    .tool-palette-btn:hover {
        transform: translateX(4px);
    }
    .signer-selector-pill {
        border: 2px solid #e0e2e8;
        border-radius: 6px;
        padding: 8px 12px;
        cursor: pointer;
        margin-bottom: 8px;
        transition: all 0.2s;
    }
    .signer-selector-pill.active {
        border-color: #666cff;
        background: rgba(102, 108, 255, 0.08);
    }
</style>
@endsection

@section('content')
<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h4 class="fw-bold mb-1"><span class="text-muted fw-light">All Document Approval /</span> Visual Tagging</h4>
                <p class="text-muted mb-0">Tentukan posisi kotak tanda tangan, paraf, tanggal, atau nama untuk tiap penanda tangan.</p>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('adhoc-document.create') }}" class="btn btn-outline-secondary">
                    <i class="ri-arrow-left-line me-1"></i> Kembali
                </a>
                <button type="button" class="btn btn-primary d-flex align-items-center gap-1" onclick="submitTagging()">
                    <i class="ri-send-plane-fill"></i>
                    <span>Kirim Permintaan Tanda Tangan</span>
                </button>
            </div>
        </div>

        <div class="row g-3">
            <!-- Sidebar Palette (Left Column) -->
            <div class="col-lg-3 col-md-4">
                <!-- 1. Pilih Signer Aktif -->
                <div class="card mb-3 shadow-sm">
                    <div class="card-header py-3 bg-light">
                        <h6 class="card-title mb-0 fw-bold"><i class="ri-user-line me-1 text-primary"></i> 1. Pilih Signer</h6>
                    </div>
                    <div class="card-body pt-3 pb-2">
                        <div id="signer-pills-container">
                            @foreach($preparedSigners as $idx => $s)
                            <div class="signer-selector-pill {{ $idx === 0 ? 'active' : '' }}" onclick="selectActiveSigner('{{ $s['key'] }}')" id="pill-{{ $s['key'] }}">
                                <div class="d-flex justify-content-between align-items-center">
                                    <strong class="text-truncate" style="max-width: 160px;">{{ $s['name'] }}</strong>
                                    <span class="badge bg-primary">#{{ $s['order'] }}</span>
                                </div>
                                <small class="text-muted d-block text-truncate">{{ $s['role_name'] }}</small>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <!-- 2. Tool Palette (Field Types) -->
                <div class="card mb-3 shadow-sm">
                    <div class="card-header py-3 bg-light">
                        <h6 class="card-title mb-0 fw-bold"><i class="ri-drag-drop-line me-1 text-primary"></i> 2. Klik untuk Taruh di Halaman</h6>
                    </div>
                    <div class="card-body pt-3">
                        <p class="text-muted small mb-2">Klik tombol di bawah untuk menempatkan kotak pada halaman PDF yang aktif:</p>
                        
                        <button type="button" class="btn btn-outline-primary tool-palette-btn" onclick="addTagField('SIGNATURE')">
                            <i class="ri-quill-pen-line fs-5"></i>
                            <div>
                                <span class="d-block fw-bold">Tanda Tangan / QR</span>
                                <small class="text-muted" style="font-size: 10px;">Area tanda tangan utama</small>
                            </div>
                        </button>

                        <button type="button" class="btn btn-outline-info tool-palette-btn" onclick="addTagField('INITIAL_PARAF')">
                            <i class="ri-edit-line fs-5"></i>
                            <div>
                                <span class="d-block fw-bold">Initial / Paraf</span>
                                <small class="text-muted" style="font-size: 10px;">Paraf lembar / tepi halaman</small>
                            </div>
                        </button>

                        <button type="button" class="btn btn-outline-success tool-palette-btn" onclick="addTagField('SIGN_DATE')">
                            <i class="ri-calendar-line fs-5"></i>
                            <div>
                                <span class="d-block fw-bold">Tanggal Sign</span>
                                <small class="text-muted" style="font-size: 10px;">Otomatis tanggal &amp; jam</small>
                            </div>
                        </button>

                        <button type="button" class="btn btn-outline-secondary tool-palette-btn" onclick="addTagField('NAME_TITLE')">
                            <i class="ri-user-star-line fs-5"></i>
                            <div>
                                <span class="d-block fw-bold">Nama &amp; Jabatan</span>
                                <small class="text-muted" style="font-size: 10px;">Otomatis nama penanda tangan</small>
                            </div>
                        </button>
                    </div>
                </div>

                <!-- Petunjuk Singkat -->
                <div class="alert alert-info py-2 px-3 small">
                    <i class="ri-information-line me-1"></i>
                    <strong>Tips:</strong> Geser kotak untuk mengatur posisi. Tarik sudut kanan bawah kotak untuk mengubah ukuran.
                </div>
            </div>

            <!-- Main PDF Viewer Canvas (Right Column) -->
            <div class="col-lg-9 col-md-8">
                <div class="card shadow-sm">
                    <!-- Toolbar PDF Nav -->
                    <div class="card-header py-2 px-3 border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div class="d-flex align-items-center gap-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="prevPage()">
                                <i class="ri-arrow-left-s-line"></i> Prev
                            </button>
                            <span class="small fw-semibold">
                                Halaman <span id="page-num" class="text-primary fw-bold">1</span> dari <span id="page-count">1</span>
                            </span>
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="nextPage()">
                                Next <i class="ri-arrow-right-s-line"></i>
                            </button>
                        </div>

                        <div class="d-flex align-items-center gap-2">
                            <button type="button" class="btn btn-sm btn-icon btn-outline-secondary" onclick="zoomOut()" title="Zoom Out">
                                <i class="ri-zoom-out-line"></i>
                            </button>
                            <span class="small text-muted" id="zoom-label">100%</span>
                            <button type="button" class="btn btn-sm btn-icon btn-outline-secondary" onclick="zoomIn()" title="Zoom In">
                                <i class="ri-zoom-in-line"></i>
                            </button>
                        </div>
                    </div>

                    <!-- PDF Canvas Area -->
                    <div class="pdf-viewer-container" id="pdf-container">
                        <div class="canvas-wrapper" id="canvas-wrapper">
                            <canvas id="pdf-canvas"></canvas>
                            <!-- Overlay tags will be injected here -->
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Hidden Submission Form -->
        <form action="{{ route('adhoc-document.save-tagging', ['uuid' => $document->uuid]) }}" method="POST" id="form-save-tagging">
            @csrf
            <input type="hidden" name="signers_json" id="input-signers-json">
            <input type="hidden" name="fields_json" id="input-fields-json">
        </form>
    </div>
</div>
@endsection

@section('page-script')
<!-- Load PDF.js from local assets with fallback -->
<script src="{{ asset('js/pdf.min.js') }}"></script>
<script>
    if (typeof pdfjsLib === 'undefined' && typeof window['pdfjs-dist/build/pdf'] !== 'undefined') {
        window.pdfjsLib = window['pdfjs-dist/build/pdf'];
    }
    if (typeof pdfjsLib !== 'undefined') {
        pdfjsLib.GlobalWorkerOptions.workerSrc = "{{ asset('js/pdf.worker.min.js') }}";
    }

    const documentUuid = "{{ $document->uuid }}";
    const pdfUrl = "{{ route('adhoc-document.view-pdf', ['uuid' => $document->uuid]) }}";
    const signersList = @json($preparedSigners);
    
    let activeSignerKey = signersList[0] ? signersList[0].key : '';
    let pdfDoc = null;
    let pageNum = 1;
    let scale = 1.25;
    let placedFields = []; // Array of field objects { id, signer_key, field_type, page_number, x_percent, y_percent, width_percent, height_percent }
    let fieldCounter = 0;

    // Colors mapping per signer
    const signerColors = ['#666cff', '#00bad1', '#ff9f43', '#28c76f', '#ea5455', '#7367f0', '#ff4c51'];

    function getSignerColor(signerKey) {
        const idx = signersList.findIndex(s => s.key === signerKey);
        return signerColors[(idx >= 0 ? idx : 0) % signerColors.length];
    }

    function selectActiveSigner(key) {
        activeSignerKey = key;
        document.querySelectorAll('.signer-selector-pill').forEach(pill => {
            pill.classList.toggle('active', pill.id === `pill-${key}`);
        });
    }

    // Load PDF
    const canvas = document.getElementById('pdf-canvas');
    const ctx = canvas.getContext('2d');
    const wrapper = document.getElementById('canvas-wrapper');

    pdfjsLib.getDocument(pdfUrl).promise.then(function(pdf) {
        pdfDoc = pdf;
        document.getElementById('page-count').textContent = pdfDoc.numPages;
        renderPage(pageNum);
    }).catch(function(error) {
        console.error('Error loading PDF:', error);
        alert('Gagal memuat file PDF: ' + error.message);
    });

    function renderPage(num) {
        pdfDoc.getPage(num).then(function(page) {
            const viewport = page.getViewport({ scale: scale });
            canvas.height = viewport.height;
            canvas.width = viewport.width;
            wrapper.style.width = viewport.width + 'px';
            wrapper.style.height = viewport.height + 'px';

            const renderContext = {
                canvasContext: ctx,
                viewport: viewport
            };

            page.render(renderContext).promise.then(function() {
                document.getElementById('page-num').textContent = num;
                document.getElementById('zoom-label').textContent = Math.round(scale * 80) + '%';
                renderOverlayTagsForCurrentPage();
            });
        });
    }

    function prevPage() {
        if (pageNum <= 1) return;
        pageNum--;
        renderPage(pageNum);
    }

    function nextPage() {
        if (pageNum >= pdfDoc.numPages) return;
        pageNum++;
        renderPage(pageNum);
    }

    function zoomIn() {
        if (scale >= 2.5) return;
        scale += 0.25;
        renderPage(pageNum);
    }

    function zoomOut() {
        if (scale <= 0.75) return;
        scale -= 0.25;
        renderPage(pageNum);
    }

    // Add a new visual tagging box
    function addTagField(fieldType) {
        if (!activeSignerKey) {
            alert('Silakan pilih salah satu signer terlebih dahulu.');
            return;
        }

        const signer = signersList.find(s => s.key === activeSignerKey);
        fieldCounter++;

        // Default dimensions in percentage
        let widthPct = 22; // 22% of page width
        let heightPct = 7;  // 7% of page height

        if (fieldType === 'INITIAL_PARAF') {
            widthPct = 12;
            heightPct = 6;
        } else if (fieldType === 'SIGN_DATE' || fieldType === 'NAME_TITLE') {
            widthPct = 25;
            heightPct = 4;
        }

        const newField = {
            id: 'tag_' + fieldCounter,
            signer_key: signer.key,
            user_id: signer.user_id,
            signer_name: signer.name,
            field_type: fieldType,
            page_number: pageNum,
            x_percent: 38, // Center initially
            y_percent: 45,
            width_percent: widthPct,
            height_percent: heightPct
        };

        placedFields.push(newField);
        renderOverlayTagsForCurrentPage();
    }

    function removeTagField(fieldId) {
        placedFields = placedFields.filter(f => f.id !== fieldId);
        renderOverlayTagsForCurrentPage();
    }

    // Render tags for current active page
    function renderOverlayTagsForCurrentPage() {
        // Remove previous overlay elements except canvas
        document.querySelectorAll('.field-tag-box').forEach(el => el.remove());

        const wrapperWidth = wrapper.clientWidth;
        const wrapperHeight = wrapper.clientHeight;

        placedFields.filter(f => f.page_number === pageNum).forEach(f => {
            const box = document.createElement('div');
            box.className = 'field-tag-box';
            box.id = f.id;

            const color = getSignerColor(f.signer_key);
            box.style.borderColor = color;
            box.style.backgroundColor = color + '22'; // 15% opacity

            // Position & Dimensions in pixels based on percentages
            box.style.left = (f.x_percent / 100 * wrapperWidth) + 'px';
            box.style.top = (f.y_percent / 100 * wrapperHeight) + 'px';
            box.style.width = (f.width_percent / 100 * wrapperWidth) + 'px';
            box.style.height = (f.height_percent / 100 * wrapperHeight) + 'px';

            let typeLabel = 'Tanda Tangan';
            if (f.field_type === 'INITIAL_PARAF') typeLabel = 'Paraf';
            if (f.field_type === 'SIGN_DATE') typeLabel = 'Tanggal Sign';
            if (f.field_type === 'NAME_TITLE') typeLabel = 'Nama & Jabatan';

            box.innerHTML = `
                <div class="tag-delete-btn" onclick="event.stopPropagation(); removeTagField('${f.id}')" title="Hapus">&times;</div>
                <div style="line-height: 1.2;">
                    <div style="font-size: 10px; color: ${color};">${f.signer_name}</div>
                    <div style="font-size: 11px;">${typeLabel}</div>
                </div>
                <div class="tag-resize-handle"></div>
            `;

            setupDraggableAndResizable(box, f);
            wrapper.appendChild(box);
        });
    }

    function setupDraggableAndResizable(box, fieldData) {
        let isDragging = false;
        let isResizing = false;
        let startX, startY, startLeft, startTop, startWidth, startHeight;

        const resizeHandle = box.querySelector('.tag-resize-handle');

        // Dragging
        box.addEventListener('mousedown', function(e) {
            if (e.target === resizeHandle || e.target.classList.contains('tag-delete-btn')) return;
            isDragging = true;
            startX = e.clientX;
            startY = e.clientY;
            startLeft = box.offsetLeft;
            startTop = box.offsetTop;

            function onMouseMove(ev) {
                if (!isDragging) return;
                const dx = ev.clientX - startX;
                const dy = ev.clientY - startY;

                let newLeft = Math.max(0, Math.min(wrapper.clientWidth - box.offsetWidth, startLeft + dx));
                let newTop = Math.max(0, Math.min(wrapper.clientHeight - box.offsetHeight, startTop + dy));

                box.style.left = newLeft + 'px';
                box.style.top = newTop + 'px';

                // Update percentage
                fieldData.x_percent = (newLeft / wrapper.clientWidth) * 100;
                fieldData.y_percent = (newTop / wrapper.clientHeight) * 100;
            }

            function onMouseUp() {
                isDragging = false;
                window.removeEventListener('mousemove', onMouseMove);
                window.removeEventListener('mouseup', onMouseUp);
            }

            window.addEventListener('mousemove', onMouseMove);
            window.addEventListener('mouseup', onMouseUp);
        });

        // Resizing
        resizeHandle.addEventListener('mousedown', function(e) {
            e.stopPropagation();
            isResizing = true;
            startX = e.clientX;
            startY = e.clientY;
            startWidth = box.offsetWidth;
            startHeight = box.offsetHeight;

            function onMouseMove(ev) {
                if (!isResizing) return;
                const dx = ev.clientX - startX;
                const dy = ev.clientY - startY;

                let newWidth = Math.max(40, Math.min(wrapper.clientWidth - box.offsetLeft, startWidth + dx));
                let newHeight = Math.max(20, Math.min(wrapper.clientHeight - box.offsetTop, startHeight + dy));

                box.style.width = newWidth + 'px';
                box.style.height = newHeight + 'px';

                // Update percentage
                fieldData.width_percent = (newWidth / wrapper.clientWidth) * 100;
                fieldData.height_percent = (newHeight / wrapper.clientHeight) * 100;
            }

            function onMouseUp() {
                isResizing = false;
                window.removeEventListener('mousemove', onMouseMove);
                window.removeEventListener('mouseup', onMouseUp);
            }

            window.addEventListener('mousemove', onMouseMove);
            window.addEventListener('mouseup', onMouseUp);
        });
    }

    // Submit Tagging
    function submitTagging() {
        if (placedFields.length === 0) {
            alert('Harap tempatkan minimal satu kotak Tanda Tangan pada dokumen.');
            return;
        }

        // Verify that every signer has at least one SIGNATURE field placed
        const signersMissingSig = [];
        signersList.forEach(s => {
            const hasSig = placedFields.some(f => f.signer_key === s.key && f.field_type === 'SIGNATURE');
            if (!hasSig) {
                signersMissingSig.push(s.name);
            }
        });

        if (signersMissingSig.length > 0) {
            const confirmMissing = confirm(
                'Perhatian: Penanda tangan berikut belum memiliki kotak Tanda Tangan:\n- ' + 
                signersMissingSig.join('\n- ') + 
                '\n\nApakah Anda yakin ingin melanjutkan?'
            );
            if (!confirmMissing) return;
        }

        document.getElementById('input-signers-json').value = JSON.stringify(signersList);
        document.getElementById('input-fields-json').value = JSON.stringify(placedFields);
        document.getElementById('form-save-tagging').submit();
    }
</script>
@endsection
