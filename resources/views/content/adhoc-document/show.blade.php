@extends('layouts/contentNavbarLayout')

@section('title', 'Detail Dokumen - All Document Approval')

@section('content')
<div class="row">
    <div class="col-12">
        <!-- Top Nav & Actions -->
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
            <div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-1">
                        <li class="breadcrumb-item"><a href="{{ route('adhoc-document.index') }}">All Document Approval</a></li>
                        <li class="breadcrumb-item active">Detail Dokumen</li>
                    </ol>
                </nav>
                <h4 class="fw-bold mb-0">{{ $document->title }}</h4>
            </div>

            <div class="d-flex align-items-center gap-2 flex-wrap">
                <!-- If draft and I am creator/admin -->
                @if($document->status === 'DRAFT' && ($document->created_by === Auth::id() || (Auth::check() && Auth::user()->hasRole('ADM'))))
                <a href="{{ route('adhoc-document.tagging', ['uuid' => $document->uuid]) }}" class="btn btn-primary d-flex align-items-center gap-1">
                    <i class="ri-drag-drop-line fs-5"></i>
                    <span>Atur Posisi Tanda Tangan</span>
                </a>
                @endif

                <!-- If I need to sign right now -->
                @if($mySignerRecord && $mySignerRecord->status === 'PENDING' && $document->status === 'IN_PROGRESS')
                <a href="{{ route('adhoc-document.review', ['uuid' => $document->uuid]) }}" class="btn btn-danger d-flex align-items-center gap-1">
                    <i class="ri-quill-pen-line fs-5"></i>
                    <span>Tanda Tangani Sekarang</span>
                </a>
                @endif

                <!-- Tombol 1: Download Signed Document -->
                @if($document->status === 'COMPLETED' || $document->signed_file_path)
                <a href="{{ route('adhoc-document.download-signed', ['uuid' => $document->uuid]) }}" class="btn btn-success d-flex align-items-center gap-1">
                    <i class="ri-download-2-line fs-5"></i>
                    <span>Download Signed Document</span>
                </a>
                @else
                <button type="button" class="btn btn-outline-secondary disabled" title="Dokumen belum selesai ditandatangani">
                    <i class="ri-download-2-line"></i> Download Signed Document
                </button>
                @endif

                <!-- Tombol 2: Cetak / Download Audit History -->
                <a href="{{ route('adhoc-document.download-audit-history', ['uuid' => $document->uuid]) }}" target="_blank" class="btn btn-outline-primary d-flex align-items-center gap-1">
                    <i class="ri-shield-check-line fs-5"></i>
                    <span>Cetak Audit History (Certificate)</span>
                </a>
            </div>
        </div>

        @if($document->status === 'DRAFT')
        <div class="alert alert-warning d-flex align-items-center justify-content-between p-3 mb-4 rounded-3 shadow-sm flex-wrap gap-2" role="alert">
            <div class="d-flex align-items-center gap-3">
                <i class="ri-alert-line fs-2 text-warning"></i>
                <div>
                    <h6 class="alert-heading mb-1 fw-bold text-dark">Dokumen Masih Berstatus Draft</h6>
                    <span class="text-secondary small">Dokumen ini belum dikirimkan ke penanda tangan. Anda perlu menempatkan posisi kotak tanda tangan pada pratinjau PDF (Visual Tagging) terlebih dahulu.</span>
                </div>
            </div>
            @if($document->created_by === Auth::id() || (Auth::check() && Auth::user()->hasRole('ADM')))
            <a href="{{ route('adhoc-document.tagging', ['uuid' => $document->uuid]) }}" class="btn btn-primary d-flex align-items-center gap-1 text-nowrap">
                <i class="ri-drag-drop-line fs-5"></i>
                <span>Atur Posisi Tanda Tangan &rarr;</span>
            </a>
            @endif
        </div>
        @endif

        <div class="row g-4">
            <!-- Left Column: Document Overview & Signers -->
            <div class="col-lg-4 col-md-5">
                <!-- Status & Metadata Card -->
                <div class="card mb-4 shadow-sm">
                    <div class="card-header border-bottom py-3">
                        <h6 class="card-title mb-0 fw-bold"><i class="ri-information-line me-1 text-primary"></i> Status &amp; Informasi Dokumen</h6>
                    </div>
                    <div class="card-body pt-3">
                        <div class="mb-3">
                            <label class="form-label text-muted small mb-1">Status Dokumen</label>
                            <div>
                                @if($document->status === 'COMPLETED')
                                    <span class="badge bg-success fs-6"><i class="ri-checkbox-circle-line me-1"></i> Completed (Selesai)</span>
                                @elseif($document->status === 'IN_PROGRESS')
                                    <span class="badge bg-primary fs-6"><i class="ri-time-line me-1"></i> In Progress (Proses Tanda Tangan)</span>
                                @elseif($document->status === 'REJECTED')
                                    <span class="badge bg-danger fs-6"><i class="ri-close-circle-line me-1"></i> Rejected (Ditolak)</span>
                                @else
                                    <span class="badge bg-secondary fs-6">Draft</span>
                                @endif
                            </div>
                        </div>

                        <div class="mb-2">
                            <label class="form-label text-muted small mb-0">Mode Alur</label>
                            <p class="mb-0 fw-semibold">
                                @if($document->workflow_type === 'SEQUENTIAL')
                                    <i class="ri-arrow-right-line text-info"></i> Sequential (Berurutan)
                                @else
                                    <i class="ri-shuffle-line text-warning"></i> Parallel (Bersamaan)
                                @endif
                            </p>
                        </div>

                        <div class="mb-2">
                            <label class="form-label text-muted small mb-0">Pengunggah</label>
                            <p class="mb-0 fw-semibold">{{ $document->creator->name ?? 'Internal User' }} ({{ $document->creator->email ?? '-' }})</p>
                        </div>

                        <div class="mb-2">
                            <label class="form-label text-muted small mb-0">Tanggal Unggah</label>
                            <p class="mb-0">{{ $document->created_at ? $document->created_at->format('d M Y, H:i:s') : '-' }} WIB</p>
                        </div>

                        <div class="mb-2">
                            <label class="form-label text-muted small mb-0">Ukuran &amp; Jumlah Halaman</label>
                            <p class="mb-0">{{ number_format($document->file_size / 1024, 1) }} KB &bull; {{ $document->page_count }} Halaman</p>
                        </div>

                        @if($document->description)
                        <div class="mt-3 p-2 bg-light rounded">
                            <small class="text-muted d-block fw-semibold mb-1">Catatan Pengunggah:</small>
                            <small>{{ $document->description }}</small>
                        </div>
                        @endif
                    </div>
                </div>

                <!-- Signers List Card -->
                <div class="card mb-4 shadow-sm">
                    <div class="card-header border-bottom py-3">
                        <h6 class="card-title mb-0 fw-bold"><i class="ri-user-star-line me-1 text-primary"></i> Daftar Penanda Tangan</h6>
                    </div>
                    <div class="card-body pt-3 px-3">
                        <ul class="list-group list-group-flush">
                            @forelse($document->signers as $signer)
                            <li class="list-group-item px-0 py-3">
                                <div class="d-flex justify-content-between align-items-start mb-1">
                                    <div>
                                        <span class="badge bg-label-primary me-1">#{{ $signer->order_index }}</span>
                                        <strong>{{ $signer->user->name ?? 'User' }}</strong>
                                        <small class="text-muted d-block">{{ $signer->role_name ?: 'Signer' }}</small>
                                    </div>
                                    <div>
                                        @if($signer->status === 'SIGNED')
                                            <span class="badge bg-success"><i class="ri-check-line"></i> Signed</span>
                                        @elseif($signer->status === 'REJECTED')
                                            <span class="badge bg-danger"><i class="ri-close-line"></i> Rejected</span>
                                        @elseif($signer->status === 'PENDING')
                                            <span class="badge bg-warning text-dark"><i class="ri-loader-4-line"></i> Pending</span>
                                        @else
                                            <span class="badge bg-secondary">Waiting</span>
                                        @endif
                                    </div>
                                </div>

                                @if($signer->status === 'SIGNED')
                                <small class="text-success d-block" style="font-size: 11px;">
                                    <i class="ri-time-line"></i> Ditandatangani: {{ $signer->signed_at->format('d/m/Y H:i') }}
                                </small>
                                @endif

                                @if($signer->status === 'REJECTED' && $signer->reject_reason)
                                <div class="alert alert-danger py-1 px-2 mt-2 mb-0" style="font-size: 11px;">
                                    <strong>Alasan Penolakan:</strong> {{ $signer->reject_reason }}
                                </div>
                                @endif
                            </li>
                            @empty
                            <li class="list-group-item px-0 py-3 text-center text-muted">
                                <i class="ri-user-unfollow-line fs-3 d-block mb-1"></i>
                                <small>Belum ada penanda tangan terdaftar.</small>
                            </li>
                            @endforelse
                        </ul>
                    </div>
                </div>

                <!-- Integrity Checksum Box -->
                <div class="card mb-4 shadow-sm">
                    <div class="card-header border-bottom py-3">
                        <h6 class="card-title mb-0 fw-bold"><i class="ri-fingerprint-line me-1 text-primary"></i> SHA-256 File Checksum</h6>
                    </div>
                    <div class="card-body pt-3">
                        <div class="mb-3">
                            <small class="text-muted d-block fw-semibold mb-1">Original Document Hash:</small>
                            <code class="d-block text-break p-2 bg-light rounded" style="font-size: 10px;">{{ $document->original_file_hash ?: '-' }}</code>
                        </div>
                        <div>
                            <small class="text-muted d-block fw-semibold mb-1">Signed Document Hash:</small>
                            <code class="d-block text-break p-2 bg-light rounded" style="font-size: 10px;">{{ $document->signed_file_hash ?: '(Belum selesai / dalam proses)' }}</code>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column: Document Viewer & Audit Logs -->
            <div class="col-lg-8 col-md-7">
                <!-- PDF Viewer Card -->
                <div class="card mb-4 shadow-sm">
                    <div class="card-header border-bottom py-3 d-flex justify-content-between align-items-center">
                        <h6 class="card-title mb-0 fw-bold"><i class="ri-file-pdf-line me-1 text-danger"></i> Pratinjau Dokumen</h6>
                        <a href="{{ route('adhoc-document.view-pdf', ['uuid' => $document->uuid]) }}" target="_blank" class="btn btn-sm btn-outline-secondary">
                            <i class="ri-external-link-line me-1"></i> Buka Layar Penuh
                        </a>
                    </div>
                    <div class="card-body p-0">
                        <iframe src="{{ route('adhoc-document.view-pdf', ['uuid' => $document->uuid]) }}#toolbar=0" width="100%" height="650px" style="border: none;"></iframe>
                    </div>
                </div>

                <!-- Audit Trail Card -->
                <div class="card shadow-sm">
                    <div class="card-header border-bottom py-3">
                        <h6 class="card-title mb-0 fw-bold"><i class="ri-history-line me-1 text-primary"></i> Jejak Rekam Aktivitas (Audit Trail)</h6>
                    </div>
                    <div class="card-body pt-3">
                        <div class="table-responsive text-nowrap">
                            <table class="table table-sm table-striped">
                                <thead>
                                    <tr>
                                        <th>Waktu</th>
                                        <th>Event / Aksi</th>
                                        <th>Pelaku (Actor)</th>
                                        <th>IP Address</th>
                                        <th>Keterangan</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($document->auditLogs as $log)
                                    <tr>
                                        <td style="font-size: 12px;">{{ $log->created_at->format('d/m/Y H:i:s') }}</td>
                                        <td>
                                            <span class="badge bg-label-info">{{ $log->action }}</span>
                                        </td>
                                        <td style="font-size: 12px;">
                                            <strong>{{ $log->actor_name }}</strong><br>
                                            <span class="text-muted" style="font-size: 10px;">{{ $log->actor_email }}</span>
                                        </td>
                                        <td style="font-size: 11px;">{{ $log->ip_address ?: '-' }}</td>
                                        <td style="font-size: 11px;" class="text-truncate" style="max-width: 250px;">{{ $log->description ?: '-' }}</td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="5" class="text-center py-3 text-muted">Belum ada riwayat aktivitas.</td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
