@extends('layouts/contentNavbarLayout')

@section('title', 'Review & Tanda Tangani - All Document Approval')

@section('styles')
<style>
    /* Tagging Overlay & PDF Viewer Styles */
    .pdf-preview-container {
        position: relative;
        background: #474b4e;
        padding: 16px;
        border-radius: 8px;
        min-height: 650px;
        overflow: auto;
        display: flex;
        justify-content: center;
        align-items: flex-start;
    }
    .pdf-canvas-wrapper {
        position: relative;
        box-shadow: 0 10px 30px rgba(0,0,0,0.35);
        background: #ffffff;
        user-select: none;
        display: inline-block;
    }
    #pdf-canvas {
        display: block;
        max-width: 100%;
        height: auto;
    }
    .tags-overlay-layer {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        pointer-events: none;
    }

    /* Tag Boxes */
    .preview-tag-box {
        position: absolute;
        box-sizing: border-box;
        border-radius: 6px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        text-align: center;
        padding: 4px;
        z-index: 10;
        transition: all 0.2s ease-in-out;
        pointer-events: auto;
        user-select: none;
        overflow: hidden;
    }

    /* Tag Status: Already Signed */
    .preview-tag-box.signed {
        border: 2px solid #28a745;
        background: rgba(40, 167, 69, 0.12);
        color: #1e7e34;
    }

    /* Tag Status: Waiting / Other Signers */
    .preview-tag-box.waiting {
        border: 2px dashed #6c757d;
        background: rgba(108, 117, 125, 0.08);
        color: #495057;
    }

    /* Tag Status: ACTIVE SIGN FOR CURRENT USER (Click-to-sign) */
    .preview-tag-box.my-active-sign {
        border: 2.5px solid #696cff;
        background: rgba(105, 108, 255, 0.18);
        color: #5659e6;
        cursor: pointer;
        box-shadow: 0 0 0 0 rgba(105, 108, 255, 0.7);
        animation: pulse-glow 1.8s infinite;
    }

    .preview-tag-box.my-active-sign:hover {
        border-color: #28a745;
        background: rgba(40, 167, 69, 0.25);
        color: #155724;
        transform: scale(1.03);
        box-shadow: 0 6px 18px rgba(40, 167, 69, 0.4);
    }

    @keyframes pulse-glow {
        0% {
            box-shadow: 0 0 0 0 rgba(105, 108, 255, 0.7);
            border-color: #696cff;
        }
        50% {
            box-shadow: 0 0 0 8px rgba(105, 108, 255, 0);
            border-color: #28a745;
        }
        100% {
            box-shadow: 0 0 0 0 rgba(105, 108, 255, 0);
            border-color: #696cff;
        }
    }

    /* Signature Canvas Pad in Modal */
    .signature-canvas-container {
        border: 2px dashed #b4b7c5;
        border-radius: 8px;
        background: #ffffff;
        position: relative;
        touch-action: none;
    }
    #signature-pad-canvas {
        display: block;
        width: 100%;
        height: 180px;
        cursor: crosshair;
    }
    .sig-preview-box {
        border: 1px solid #e0e2e8;
        border-radius: 6px;
        padding: 15px;
        text-align: center;
        background: #ffffff;
    }
    .sig-preview-img {
        max-height: 100px;
        max-width: 250px;
        object-fit: contain;
    }
</style>
@endsection

@section('content')
<div class="row">
    <div class="col-12">
        <!-- Top Review Bar -->
        <div class="card mb-3 shadow-sm border-primary">
            <div class="card-body py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h5 class="fw-bold mb-0 text-primary d-flex align-items-center gap-2">
                        <i class="ri-quill-pen-line"></i>
                        <span>Permintaan Tanda Tangan: {{ $document->title }}</span>
                    </h5>
                    <small class="text-muted">
                        Diajukan oleh: <strong>{{ $document->creator->name ?? 'Internal User' }}</strong> &bull; Peran Anda: <span class="badge bg-label-primary">{{ $signer->role_name ?: 'Signer' }}</span>
                    </small>
                </div>

                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-outline-danger d-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#modal-reject">
                        <i class="ri-close-circle-line"></i>
                        <span>Tolak Dokumen (Reject)</span>
                    </button>
                </div>
            </div>
        </div>

        <div class="row g-3">
            <!-- Left: Interactive PDF.js Document Viewer (col-lg-9) -->
            <div class="col-lg-9 col-md-8">
                <div class="card shadow-sm">
                    <div class="card-header border-bottom py-2 d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div class="d-flex align-items-center gap-2">
                            <h6 class="card-title mb-0 fw-bold"><i class="ri-file-pdf-line me-1 text-danger"></i> Pratinjau Dokumen</h6>
                            <span class="badge bg-label-info" id="badge-page-info">Halaman <span id="page-num">1</span> / <span id="page-count">{{ $document->page_count }}</span></span>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <!-- Page Controls -->
                            <div class="btn-group btn-group-sm" role="group">
                                <button type="button" class="btn btn-outline-secondary" onclick="prevPdfPage()" id="btn-prev" title="Halaman Sebelumnya">
                                    <i class="ri-arrow-left-s-line"></i> Prev
                                </button>
                                <button type="button" class="btn btn-outline-secondary" onclick="nextPdfPage()" id="btn-next" title="Halaman Selanjutnya">
                                    Next <i class="ri-arrow-right-s-line"></i>
                                </button>
                            </div>
                            <!-- Zoom Controls -->
                            <div class="btn-group btn-group-sm" role="group">
                                <button type="button" class="btn btn-outline-secondary" onclick="zoomPdfOut()" title="Perkecil"><i class="ri-zoom-out-line"></i></button>
                                <span class="btn btn-outline-secondary disabled fw-bold" id="zoom-label" style="min-width: 55px;">100%</span>
                                <button type="button" class="btn btn-outline-secondary" onclick="zoomPdfIn()" title="Perbesar"><i class="ri-zoom-in-line"></i></button>
                            </div>
                            <!-- Open Original in new window -->
                            <a href="{{ route('adhoc-document.view-pdf', ['uuid' => $document->uuid]) }}" target="_blank" class="btn btn-sm btn-outline-secondary" title="Buka PDF di Tab Baru">
                                <i class="ri-external-link-line"></i>
                            </a>
                        </div>
                    </div>

                    <!-- Instructions banner -->
                    <div class="alert alert-primary d-flex align-items-center justify-content-between m-3 mb-0 p-3 rounded-3 shadow-sm border-start border-primary border-4 flex-wrap gap-2">
                        <div class="d-flex align-items-center gap-2">
                            <i class="ri-quill-pen-line fs-3 text-primary"></i>
                            <div>
                                <strong class="d-block text-dark">Klik Kotak Tanda Tangan Anda Pada Dokumen!</strong>
                                <span class="text-secondary small">Kotak tanda tangan Anda ditandai dengan garis biru berkedip di bawah. <strong>Klik langsung pada kotak</strong> untuk membubuhkan tanda tangan Anda.</span>
                            </div>
                        </div>
                        @php
                            $myFirstField = $myFields->first();
                            $myTargetPage = $myFirstField ? $myFirstField->page_number : 1;
                        @endphp
                        @if($myFirstField)
                        <button type="button" class="btn btn-sm btn-primary text-nowrap d-flex align-items-center gap-1 shadow-sm" onclick="goToPdfPage({{ $myTargetPage }})">
                            <i class="ri-focus-3-line"></i> Lompat ke Posisi (Hal. {{ $myTargetPage }})
                        </button>
                        @endif
                    </div>

                    <!-- Canvas Viewer Wrapper -->
                    <div class="card-body p-3 bg-secondary bg-opacity-10 d-flex justify-content-center overflow-auto position-relative" style="min-height: 700px;" id="pdf-container-scroll">
                        <div id="pdf-wrapper" class="pdf-canvas-wrapper">
                            <canvas id="pdf-canvas"></canvas>
                            <div id="tags-overlay-layer" class="tags-overlay-layer"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right: Instructions & Tag Summary (col-lg-3) -->
            <div class="col-lg-3 col-md-4">
                <div class="card mb-3 shadow-sm">
                    <div class="card-header border-bottom py-3 bg-light">
                        <h6 class="card-title mb-0 fw-bold"><i class="ri-map-pin-line text-primary me-1"></i> Titik Tanda Tangan Anda</h6>
                    </div>
                    <div class="card-body pt-3">
                        <p class="text-muted small mb-2">Klik tombol halaman di bawah untuk menuju posisi kotak tanda tangan:</p>
                        <ul class="list-group list-group-flush small">
                            @forelse($myFields as $f)
                            <li class="list-group-item px-0 py-2 d-flex justify-content-between align-items-center">
                                <div>
                                    @if($f->field_type === 'SIGNATURE')
                                        <i class="ri-quill-pen-line text-primary me-1"></i> Tanda Tangan
                                    @elseif($f->field_type === 'INITIAL_PARAF')
                                        <i class="ri-edit-line text-info me-1"></i> Initial / Paraf
                                    @elseif($f->field_type === 'SIGN_DATE')
                                        <i class="ri-calendar-line text-success me-1"></i> Tanggal Sign
                                    @else
                                        <i class="ri-user-star-line text-secondary me-1"></i> Nama &amp; Jabatan
                                    @endif
                                </div>
                                <button type="button" class="btn btn-xs btn-outline-primary" onclick="goToPdfPage({{ $f->page_number }})">
                                    Hal. {{ $f->page_number }} &rarr;
                                </button>
                            </li>
                            @empty
                            <li class="list-group-item px-0 text-muted">Belum ada titik khusus.</li>
                            @endforelse
                        </ul>
                    </div>
                </div>

                <div class="card shadow-sm border-info">
                    <div class="card-body py-3">
                        <h6 class="fw-bold text-info mb-1"><i class="ri-shield-check-line me-1"></i> Keamanan &amp; Integritas</h6>
                        <p class="text-muted small mb-0">
                            Aksi persetujuan Anda dicatat secara permanen dengan hash SHA-256, IP address, dan timestamp presisi pada sistem audit log.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Signature Pad & Konfirmasi Tanda Tangan (Muncul Saat Tagging Diklik) -->
<div class="modal fade" id="modal-sign" tabindex="-1" aria-labelledby="modalSignLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow-lg border-primary">
            <div class="modal-header border-bottom bg-primary text-white">
                <h5 class="modal-title fw-bold text-white" id="modalSignLabel"><i class="ri-quill-pen-line me-1"></i> Bubuhkan Tanda Tangan</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <form action="{{ route('adhoc-document.sign', ['uuid' => $document->uuid]) }}" method="POST" id="form-sign-confirm">
                @csrf
                <input type="hidden" name="signature_data" id="input-signature-data">

                <div class="modal-body">
                    <!-- Nav Tabs: Tanda Tangan Profil vs Pad Gambar -->
                    <ul class="nav nav-tabs nav-fill mb-3" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link {{ $existingSignatureUrl ? 'active' : '' }}" id="tab-db" data-bs-toggle="tab" href="#content-db" role="tab">
                                <i class="ri-database-2-line me-1"></i> Tanda Tangan Profil
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ !$existingSignatureUrl ? 'active' : '' }}" id="tab-draw" data-bs-toggle="tab" href="#content-draw" role="tab">
                                <i class="ri-pencil-line me-1"></i> Gambar di Layar (Pad)
                            </a>
                        </li>
                    </ul>

                    <div class="tab-content p-0">
                        <!-- Tab Content 1: Tanda Tangan dari Profil -->
                        <div class="tab-pane fade {{ $existingSignatureUrl ? 'show active' : '' }}" id="content-db" role="tabpanel">
                            @if($existingSignatureUrl)
                            <div class="sig-preview-box mb-3">
                                <img src="{{ $existingSignatureUrl }}" alt="Signature Profil" class="sig-preview-img mb-2">
                                <div class="text-success small fw-semibold"><i class="ri-checkbox-circle-fill me-1"></i> Tanda tangan terdaftar di profil Anda</div>
                            </div>
                            <p class="text-muted small mb-0">Tanda tangan di atas akan otomatis digunakan dan ditempelkan pada posisi tagging dokumen ini.</p>
                            @else
                            <div class="alert alert-warning py-3 text-center mb-0">
                                <i class="ri-alert-line fs-3 d-block mb-1"></i>
                                Anda belum memiliki gambar tanda tangan di profil. Silakan gunakan tab <strong>"Gambar di Layar (Pad)"</strong> di sebelah.
                            </div>
                            @endif
                        </div>

                        <!-- Tab Content 2: Signature Pad Canvas -->
                        <div class="tab-pane fade {{ !$existingSignatureUrl ? 'show active' : '' }}" id="content-draw" role="tabpanel">
                            <p class="text-muted small mb-2">Gambarkan tanda tangan Anda di dalam kotak berikut:</p>
                            
                            <div class="signature-canvas-container mb-2" style="background: #ffffff; border: 2px dashed #b4b7c5; border-radius: 8px; position: relative; overflow: hidden;">
                                <canvas id="signature-pad-canvas" width="460" height="200" style="display: block; width: 100%; height: 200px; touch-action: none; cursor: crosshair; background: #ffffff;"></canvas>
                                <div style="position: absolute; bottom: 30px; left: 20px; right: 20px; border-bottom: 1px dashed #cbd5e1; pointer-events: none; text-align: right;">
                                    <span style="font-size: 10px; color: #94a3b8; user-select: none;">Garis Tanda Tangan</span>
                                </div>
                            </div>

                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="clearSignaturePad()">
                                    <i class="ri-eraser-line me-1"></i> Hapus / Gambar Ulang
                                </button>
                                <small class="text-muted" style="font-size: 11px;">Otomatis tersimpan &amp; sah secara hukum</small>
                            </div>
                        </div>
                    </div>

                    <div class="form-check mt-3">
                        <input class="form-check-input" type="checkbox" id="check-confirm-sign" required>
                        <label class="form-check-label small" for="check-confirm-sign">
                            Saya mengonfirmasi telah membaca dan menyetujui seluruh isi dokumen ini dengan sah.
                        </label>
                    </div>
                </div>

                <div class="modal-footer border-top">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-success d-flex align-items-center gap-1 shadow" onclick="handleSignSubmit()">
                        <i class="ri-check-line"></i>
                        <span>Setujui &amp; Tanda Tangani</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Tolak Dokumen (Reject) -->
<div class="modal fade" id="modal-reject" tabindex="-1" aria-labelledby="modalRejectLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow">
            <div class="modal-header border-bottom bg-danger text-white">
                <h5 class="modal-title text-white fw-bold" id="modalRejectLabel"><i class="ri-close-circle-line me-1"></i> Tolak Dokumen</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form action="{{ route('adhoc-document.reject', ['uuid' => $document->uuid]) }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="alert alert-danger py-2 small mb-3">
                        <i class="ri-error-warning-line me-1"></i> <strong>Perhatian:</strong> Penolakan dokumen akan menghentikan seluruh proses persetujuan dan dicatat pada riwayat audit resmi.
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="reject_reason">Alasan Penolakan <span class="text-danger">*</span></label>
                        <textarea class="form-control" name="reject_reason" id="reject_reason" rows="3" placeholder="Tuliskan alasan penolakan secara jelas dan rinci..." required minlength="5"></textarea>
                    </div>
                </div>

                <div class="modal-footer border-top">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="ri-close-line me-1"></i> Konfirmasi Tolak Dokumen
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('page-script')
<!-- PDF.js CDN for Canvas Rendering -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
<script>
    // Initialize PDF.js worker
    pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';

    const pdfUrl = "{{ route('adhoc-document.view-pdf', ['uuid' => $document->uuid]) }}";
    const allFieldsData = @json($fieldsData);
    const hasExistingProfileSig = {{ $existingSignatureUrl ? 'true' : 'false' }};

    let pdfDoc = null;
    let pageNum = 1;
    let totalPages = {{ $document->page_count ?? 1 }};
    let scale = 1.25;
    let isRendering = false;
    let pageNumPending = null;

    const canvas = document.getElementById('pdf-canvas');
    const ctx = canvas.getContext('2d');
    const wrapper = document.getElementById('pdf-wrapper');
    const tagsOverlayLayer = document.getElementById('tags-overlay-layer');

    // Load PDF
    pdfjsLib.getDocument({ url: pdfUrl, withCredentials: true }).promise.then(function(pdf) {
        pdfDoc = pdf;
        totalPages = pdf.numPages;
        document.getElementById('page-count').textContent = totalPages;
        renderPdfPage(pageNum);
    }).catch(function(err) {
        console.error('Error loading PDF:', err);
    });

    function renderPdfPage(num) {
        if (isRendering) {
            pageNumPending = num;
            return;
        }
        isRendering = true;

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
                isRendering = false;
                document.getElementById('page-num').textContent = num;
                document.getElementById('zoom-label').textContent = Math.round(scale * 80) + '%';
                
                // Render the tagging overlay for this page
                renderTagsForPage(num);

                if (pageNumPending !== null) {
                    const nextNum = pageNumPending;
                    pageNumPending = null;
                    renderPdfPage(nextNum);
                }
            });
        });

        // Update Prev/Next buttons
        document.getElementById('btn-prev').disabled = (num <= 1);
        document.getElementById('btn-next').disabled = (num >= totalPages);
    }

    function prevPdfPage() {
        if (pageNum <= 1) return;
        pageNum--;
        renderPdfPage(pageNum);
    }

    function nextPdfPage() {
        if (pageNum >= totalPages) return;
        pageNum++;
        renderPdfPage(pageNum);
    }

    function goToPdfPage(num) {
        if (num < 1 || num > totalPages) return;
        pageNum = num;
        renderPdfPage(pageNum);
    }

    function zoomPdfIn() {
        if (scale >= 2.5) return;
        scale += 0.25;
        renderPdfPage(pageNum);
    }

    function zoomPdfOut() {
        if (scale <= 0.75) return;
        scale -= 0.25;
        renderPdfPage(pageNum);
    }

    // Render Overlay Tags on Canvas
    function renderTagsForPage(currentPage) {
        tagsOverlayLayer.innerHTML = '';
        const wrapperWidth = wrapper.clientWidth;
        const wrapperHeight = wrapper.clientHeight;

        const pageFields = allFieldsData.filter(f => f.page_number === currentPage);

        pageFields.forEach(f => {
            const box = document.createElement('div');
            box.className = 'preview-tag-box';
            
            // Calculate absolute pixel coordinates from percentages
            const left = (f.x_percent / 100 * wrapperWidth);
            const top = (f.y_percent / 100 * wrapperHeight);
            const width = (f.width_percent / 100 * wrapperWidth);
            const height = (f.height_percent / 100 * wrapperHeight);

            box.style.left = left + 'px';
            box.style.top = top + 'px';
            box.style.width = width + 'px';
            box.style.height = height + 'px';

            let typeLabel = 'Tanda Tangan';
            if (f.field_type === 'INITIAL_PARAF') typeLabel = 'Paraf';
            if (f.field_type === 'SIGN_DATE') typeLabel = 'Tanggal Sign';
            if (f.field_type === 'NAME_TITLE') typeLabel = 'Nama & Jabatan';

            if (f.is_my_turn) {
                // ACTIVE: Current user's turn to sign -> Clickable & pulsating
                box.classList.add('my-active-sign');
                box.title = 'Klik di sini untuk menandatangani dokumen!';
                box.innerHTML = `
                    <div class="d-flex flex-column align-items-center justify-content-center h-100 p-1">
                        <i class="ri-quill-pen-fill fs-5 text-primary mb-1"></i>
                        <span class="fw-bold" style="font-size: 11px; line-height: 1.1;">👆 Klik untuk Tanda Tangan</span>
                        <small class="text-muted" style="font-size: 9px;">${f.signer_name}</small>
                    </div>
                `;
                box.addEventListener('click', function(e) {
                    e.stopPropagation();
                    openSignModal();
                });
            } else if (f.signer_status === 'SIGNED') {
                // Completed sign - Tampilkan Gambar Tanda Tangan Asli
                box.classList.add('signed');
                box.style.background = 'rgba(255, 255, 255, 0.9)';
                box.style.border = '1.5px dashed #28a745';

                if (f.signature_url && (f.field_type === 'SIGNATURE' || f.field_type === 'INITIAL_PARAF')) {
                    box.innerHTML = `
                        <div class="d-flex flex-column align-items-center justify-content-center h-100 w-100 p-1 position-relative">
                            <img src="${f.signature_url}" style="max-height: 75%; max-width: 95%; object-fit: contain; pointer-events: none;" alt="Tanda Tangan ${f.signer_name}">
                            <div class="d-flex align-items-center gap-1 text-success fw-bold" style="font-size: 8px; line-height: 1; margin-top: 2px;">
                                <i class="ri-checkbox-circle-fill" style="font-size: 9px;"></i>
                                <span>${f.signer_name}</span>
                            </div>
                        </div>
                    `;
                } else if (f.field_type === 'SIGN_DATE') {
                    box.innerHTML = `
                        <div class="d-flex flex-column align-items-center justify-content-center h-100 w-100 p-1">
                            <span class="fw-bold text-dark" style="font-size: 11px;">${f.signed_at_formatted || 'Signed'}</span>
                            <small class="text-success" style="font-size: 8px;"><i class="ri-checkbox-circle-fill"></i> ${f.signer_name}</small>
                        </div>
                    `;
                } else if (f.field_type === 'NAME_TITLE') {
                    box.innerHTML = `
                        <div class="d-flex flex-column align-items-center justify-content-center h-100 w-100 p-1">
                            <span class="fw-bold text-dark" style="font-size: 11px;">${f.signer_name}</span>
                            <small class="text-muted" style="font-size: 8px;">${f.signer_role}</small>
                        </div>
                    `;
                } else {
                    box.innerHTML = `
                        <div class="d-flex flex-column align-items-center justify-content-center h-100 p-1">
                            <i class="ri-checkbox-circle-fill fs-6 text-success mb-1"></i>
                            <span class="fw-bold" style="font-size: 10px; line-height: 1.1;">✓ Ditandatangani</span>
                            <small style="font-size: 9px;">${f.signer_name}</small>
                        </div>
                    `;
                }
            } else {
                // Waiting / Other signer
                box.classList.add('waiting');
                box.innerHTML = `
                    <div class="d-flex flex-column align-items-center justify-content-center h-100 p-1">
                        <i class="ri-user-line fs-6 mb-1"></i>
                        <span style="font-size: 10px; line-height: 1.1;">${typeLabel}</span>
                        <small class="text-muted" style="font-size: 9px;">${f.signer_name}</small>
                    </div>
                `;
            }

            tagsOverlayLayer.appendChild(box);
        });
    }

    // Modal & Signature Pad Canvas Management
    const sigCanvas = document.getElementById('signature-pad-canvas');
    const sigCtx = sigCanvas ? sigCanvas.getContext('2d') : null;
    let isDrawing = false;
    let hasDrawnOnPad = false;
    let lastX = 0;
    let lastY = 0;

    function initCanvasContext() {
        if (!sigCtx) return;
        sigCtx.strokeStyle = '#0f172a';
        sigCtx.fillStyle = '#0f172a';
        sigCtx.lineWidth = 2.5;
        sigCtx.lineCap = 'round';
        sigCtx.lineJoin = 'round';
    }

    function resizeSignatureCanvas() {
        if (!sigCanvas || !sigCtx) return;
        const container = sigCanvas.parentElement;
        const rect = sigCanvas.getBoundingClientRect();
        
        const targetWidth = Math.round(rect.width || (container ? container.clientWidth : 0) || 460);
        const targetHeight = Math.round(rect.height || (container ? container.clientHeight : 0) || 200);

        if (targetWidth < 50 || targetHeight < 50) return;

        if (sigCanvas.width === targetWidth && sigCanvas.height === targetHeight) {
            initCanvasContext();
            return;
        }

        let savedData = null;
        if (hasDrawnOnPad && sigCanvas.width > 0 && sigCanvas.height > 0) {
            try {
                savedData = sigCanvas.toDataURL('image/png');
            } catch (err) {}
        }

        sigCanvas.width = targetWidth;
        sigCanvas.height = targetHeight;
        initCanvasContext();

        if (savedData) {
            const img = new Image();
            img.onload = function() {
                sigCtx.drawImage(img, 0, 0);
            };
            img.src = savedData;
        }
    }

    function clearSignaturePad() {
        if (!sigCtx || !sigCanvas) return;
        sigCtx.clearRect(0, 0, sigCanvas.width, sigCanvas.height);
        hasDrawnOnPad = false;
    }

    function getCanvasCoordinates(e) {
        const rect = sigCanvas.getBoundingClientRect();
        let clientX = e.clientX;
        let clientY = e.clientY;

        if (e.touches && e.touches.length > 0) {
            clientX = e.touches[0].clientX;
            clientY = e.touches[0].clientY;
        }

        return {
            x: (clientX - rect.left) * (sigCanvas.width / rect.width),
            y: (clientY - rect.top) * (sigCanvas.height / rect.height)
        };
    }

    function startDrawing(e) {
        if (!sigCtx) return;
        isDrawing = true;
        const coords = getCanvasCoordinates(e);
        lastX = coords.x;
        lastY = coords.y;

        sigCtx.beginPath();
        sigCtx.arc(lastX, lastY, sigCtx.lineWidth / 2, 0, Math.PI * 2);
        sigCtx.fill();
        hasDrawnOnPad = true;
    }

    function draw(e) {
        if (!isDrawing || !sigCtx) return;
        e.preventDefault();
        const coords = getCanvasCoordinates(e);

        sigCtx.beginPath();
        sigCtx.moveTo(lastX, lastY);
        sigCtx.lineTo(coords.x, coords.y);
        sigCtx.stroke();

        lastX = coords.x;
        lastY = coords.y;
        hasDrawnOnPad = true;
    }

    function stopDrawing() {
        isDrawing = false;
    }

    // Attach mouse & touch events
    if (sigCanvas) {
        sigCanvas.addEventListener('mousedown', startDrawing);
        sigCanvas.addEventListener('mousemove', draw);
        sigCanvas.addEventListener('mouseup', stopDrawing);
        sigCanvas.addEventListener('mouseleave', stopDrawing);

        sigCanvas.addEventListener('touchstart', startDrawing, { passive: false });
        sigCanvas.addEventListener('touchmove', draw, { passive: false });
        sigCanvas.addEventListener('touchend', stopDrawing, { passive: false });
    }

    function openSignModal() {
        const modalEl = document.getElementById('modal-sign');
        const modal = new bootstrap.Modal(modalEl);
        modal.show();
    }

    document.addEventListener('DOMContentLoaded', function() {
        const modalEl = document.getElementById('modal-sign');
        if (modalEl) {
            modalEl.addEventListener('shown.bs.modal', function() {
                resizeSignatureCanvas();
            });
        }

        const tabDraw = document.getElementById('tab-draw');
        if (tabDraw) {
            tabDraw.addEventListener('shown.bs.tab', function() {
                setTimeout(resizeSignatureCanvas, 100);
            });
        }
    });

    function handleSignSubmit() {
        const checkConfirm = document.getElementById('check-confirm-sign');
        if (!checkConfirm.checked) {
            alert('Mohon centang persetujuan dokumen sebelum melanjutkan.');
            checkConfirm.focus();
            return;
        }

        const activeDrawTab = document.querySelector('#tab-draw.active');
        const inputSigData = document.getElementById('input-signature-data');

        if (activeDrawTab) {
            if (!hasDrawnOnPad) {
                alert('Silakan gambar tanda tangan Anda di canvas terlebih dahulu.');
                return;
            }
            inputSigData.value = sigCanvas.toDataURL('image/png');
        } else {
            if (!hasExistingProfileSig) {
                alert('Tanda tangan profil belum tersedia. Silakan gunakan tab "Gambar di Layar (Pad)".');
                return;
            }
            inputSigData.value = ''; // Use profile signature on backend
        }

        // Submit form
        document.getElementById('form-sign-confirm').submit();
    }
</script>
@endsection
