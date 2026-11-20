@extends('layouts/contentNavbarLayout')

@section('title', 'Review & Tanda Tangani - All Document Approval')

@section('styles')
<style>
    .signature-canvas-container {
        border: 2px dashed #b4b7c5;
        border-radius: 8px;
        background: #fbfbfd;
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
                    <button type="button" class="btn btn-success btn-lg d-flex align-items-center gap-2 shadow" data-bs-toggle="modal" data-bs-target="#modal-sign">
                        <i class="ri-check-double-line fs-5"></i>
                        <span>Tanda Tangani &amp; Setujui (Sign &amp; Approve)</span>
                    </button>
                </div>
            </div>
        </div>

        <div class="row g-3">
            <!-- Left: Document Viewer -->
            <div class="col-lg-9 col-md-8">
                <div class="card shadow-sm">
                    <div class="card-body p-0">
                        <iframe src="{{ route('adhoc-document.view-pdf', ['uuid' => $document->uuid]) }}#toolbar=0" width="100%" height="750px" style="border: none;"></iframe>
                    </div>
                </div>
            </div>

            <!-- Right: Instructions & Tag Summary -->
            <div class="col-lg-3 col-md-4">
                <div class="card mb-3 shadow-sm">
                    <div class="card-header border-bottom py-3 bg-light">
                        <h6 class="card-title mb-0 fw-bold"><i class="ri-map-pin-line text-primary me-1"></i> Titik Tanda Tangan Anda</h6>
                    </div>
                    <div class="card-body pt-3">
                        <p class="text-muted small mb-2">Tanda tangan Anda akan otomatis ditempatkan pada posisi berikut:</p>
                        <ul class="list-group list-group-flush small">
                            @forelse($myFields as $f)
                            <li class="list-group-item px-0 py-2 d-flex justify-content-between align-items-center">
                                <div>
                                    @if($f->field_type === 'SIGNATURE')
                                        <i class="ri-quill-pen-line text-primary me-1"></i> Tanda Tangan Utama
                                    @elseif($f->field_type === 'INITIAL_PARAF')
                                        <i class="ri-edit-line text-info me-1"></i> Initial / Paraf
                                    @elseif($f->field_type === 'SIGN_DATE')
                                        <i class="ri-calendar-line text-success me-1"></i> Tanggal Sign
                                    @else
                                        <i class="ri-user-star-line text-secondary me-1"></i> Nama &amp; Jabatan
                                    @endif
                                </div>
                                <span class="badge bg-label-secondary">Hal. {{ $f->page_number }}</span>
                            </li>
                            @empty
                            <li class="list-group-item px-0 text-muted">Belum ada titik khusus. Tanda tangan akan divalidasi pada dokumen.</li>
                            @endforelse
                        </ul>
                    </div>
                </div>

                <div class="card shadow-sm border-info">
                    <div class="card-body py-3">
                        <h6 class="fw-bold text-info mb-1"><i class="ri-shield-check-line me-1"></i> Keamanan &amp; Integritas</h6>
                        <p class="text-muted small mb-0">
                            Aksi persetujuan Anda dicatat secara permanen dengan hash SHA-256, IP address, dan timestamp presisi pada Certificate of Completion resmi.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal 1: Sign & Approve Modal -->
<div class="modal fade" id="modal-sign" tabindex="-1" aria-labelledby="modalSignLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-bottom">
                <h5 class="modal-title fw-bold" id="modalSignLabel"><i class="ri-quill-pen-line text-primary me-1"></i> Konfirmasi Tanda Tangan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <form action="{{ route('adhoc-document.sign', ['uuid' => $document->uuid]) }}" method="POST" id="form-sign-confirm">
                @csrf
                <input type="hidden" name="signature_data" id="input-signature-data">

                <div class="modal-body">
                    <!-- Nav Tabs: Database vs Pad -->
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
                        <!-- Tab Content 1: Existing Database Signature -->
                        <div class="tab-pane fade {{ $existingSignatureUrl ? 'show active' : '' }}" id="content-db" role="tabpanel">
                            @if($existingSignatureUrl)
                            <div class="sig-preview-box mb-3">
                                <img src="{{ $existingSignatureUrl }}" alt="Signature Profil" class="sig-preview-img mb-2">
                                <div class="text-success small fw-semibold"><i class="ri-checkbox-circle-fill me-1"></i> Tanda tangan terdaftar di profil Anda</div>
                            </div>
                            <p class="text-muted small mb-0">Tanda tangan di atas akan otomatis digunakan dan ditempelkan pada dokumen ini.</p>
                            @else
                            <div class="alert alert-warning py-3 text-center mb-0">
                                <i class="ri-alert-line fs-3 d-block mb-1"></i>
                                Anda belum memiliki gambar tanda tangan di profil. Silakan gunakan tab <strong>"Gambar di Layar (Pad)"</strong> di sebelah.
                            </div>
                            @endif
                        </div>

                        <!-- Tab Content 2: Signature Pad Canvas -->
                        <div class="tab-pane fade {{ !$existingSignatureUrl ? 'show active' : '' }}" id="content-draw" role="tabpanel">
                            <p class="text-muted small mb-2">Tanda tangani langsung di dalam kotak berikut menggunakan mouse atau jari Anda:</p>
                            
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
                                <small class="text-muted" style="font-size: 11px;">Otomatis disimpan ke profil Anda</small>
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
                    <button type="button" class="btn btn-success d-flex align-items-center gap-1" onclick="handleSignSubmit()">
                        <i class="ri-check-line"></i>
                        <span>Setujui &amp; Tanda Tangani</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal 2: Reject Modal -->
<div class="modal fade" id="modal-reject" tabindex="-1" aria-labelledby="modalRejectLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
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
<script>
    // Bulletproof Responsive Signature Pad Canvas Engine
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

        // Do not resize if hidden with 0 width
        if (targetWidth < 50 || targetHeight < 50) return;

        // If dimensions already match, just ensure stroke styles are set
        if (sigCanvas.width === targetWidth && sigCanvas.height === targetHeight) {
            initCanvasContext();
            return;
        }

        // Preserve current drawing if resizing
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

    function getCanvasCoordinates(e) {
        if (!sigCanvas) return { x: 0, y: 0 };
        const rect = sigCanvas.getBoundingClientRect();
        let clientX = e.clientX;
        let clientY = e.clientY;

        if (e.touches && e.touches.length > 0) {
            clientX = e.touches[0].clientX;
            clientY = e.touches[0].clientY;
        }

        const scaleX = sigCanvas.width / (rect.width || 1);
        const scaleY = sigCanvas.height / (rect.height || 1);

        return {
            x: (clientX - rect.left) * scaleX,
            y: (clientY - rect.top) * scaleY
        };
    }

    if (sigCanvas && sigCtx) {
        initCanvasContext();

        // Listen for Modal Open
        const modalSign = document.getElementById('modal-sign');
        if (modalSign) {
            modalSign.addEventListener('shown.bs.modal', function() {
                setTimeout(resizeSignatureCanvas, 60);
            });
        }

        // Listen for Tab Switching
        const tabDraw = document.getElementById('tab-draw');
        if (tabDraw) {
            tabDraw.addEventListener('shown.bs.tab', function() {
                setTimeout(resizeSignatureCanvas, 60);
            });
            tabDraw.addEventListener('click', function() {
                setTimeout(resizeSignatureCanvas, 80);
            });
        }

        // Start Stroke (Mouse & Touch)
        function startDrawing(e) {
            if (e.type === 'touchstart') e.preventDefault();
            // Failsafe resize if canvas was initialized with 0
            if (sigCanvas.width < 50 || sigCanvas.height < 50) {
                resizeSignatureCanvas();
            }
            initCanvasContext();

            isDrawing = true;
            hasDrawnOnPad = true;

            const pos = getCanvasCoordinates(e);
            lastX = pos.x;
            lastY = pos.y;

            // Draw initial dot on tap/click
            sigCtx.beginPath();
            sigCtx.arc(lastX, lastY, 1.25, 0, Math.PI * 2);
            sigCtx.fill();
        }

        // Continue Stroke (Mouse & Touch)
        function draw(e) {
            if (!isDrawing) return;
            if (e.type === 'touchmove') e.preventDefault();

            const pos = getCanvasCoordinates(e);

            sigCtx.beginPath();
            sigCtx.moveTo(lastX, lastY);
            sigCtx.lineTo(pos.x, pos.y);
            sigCtx.stroke();

            lastX = pos.x;
            lastY = pos.y;
        }

        // End Stroke
        function stopDrawing() {
            isDrawing = false;
        }

        // Event Listeners
        sigCanvas.addEventListener('mousedown', startDrawing);
        sigCanvas.addEventListener('mousemove', draw);
        window.addEventListener('mouseup', stopDrawing);

        sigCanvas.addEventListener('touchstart', startDrawing, { passive: false });
        sigCanvas.addEventListener('touchmove', draw, { passive: false });
        window.addEventListener('touchend', stopDrawing);
    }

    function clearSignaturePad() {
        if (!sigCanvas || !sigCtx) return;
        sigCtx.clearRect(0, 0, sigCanvas.width, sigCanvas.height);
        hasDrawnOnPad = false;
        initCanvasContext();
    }

    function handleSignSubmit() {
        const checkbox = document.getElementById('check-confirm-sign');
        if (!checkbox || !checkbox.checked) {
            alert('Harap centang konfirmasi persetujuan dokumen terlebih dahulu.');
            return;
        }

        const activeTab = document.querySelector('#modal-sign .nav-link.active');
        const isDrawTab = activeTab && activeTab.id === 'tab-draw';
        const hasExisting = {{ $existingSignatureUrl ? 'true' : 'false' }};

        if (isDrawTab) {
            if (!hasDrawnOnPad) {
                alert('Silakan buat tanda tangan Anda terlebih dahulu pada kotak Signature Pad.');
                return;
            }
            // Export canvas to base64 DataURL (PNG)
            const dataUrl = sigCanvas.toDataURL('image/png');
            document.getElementById('input-signature-data').value = dataUrl;
        } else {
            if (!hasExisting) {
                alert('Anda belum memiliki tanda tangan di database. Silakan gunakan tab "Gambar di Layar (Pad)".');
                return;
            }
            document.getElementById('input-signature-data').value = '';
        }

        document.getElementById('form-sign-confirm').submit();
    }
</script>
@endsection
