@extends('layouts/contentNavbarLayout')

@section('title', 'Review & Preview Dokumen Kalibrasi')

@section('content')
<div class="row">
  <div class="col-12">
    
    {{-- Header Action Bar --}}
    <div class="card shadow-sm border-0 mb-4">
      <div class="card-body py-3 d-flex flex-wrap align-items-center justify-content-between gap-3">
        <div class="d-flex align-items-center gap-3">
          <a href="{{ url()->previous() ?: route('kalibrasi.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="ri-arrow-left-line me-1"></i> Kembali
          </a>
          <div>
            <div class="d-flex align-items-center gap-2">
              <h5 class="mb-0 fw-bold font-monospace text-primary">{{ $kalibrasi->certificate_number }}</h5>
              @php
                $statusBadge = $kalibrasi->status_badge;
              @endphp
              <span class="badge {{ $statusBadge['cls'] }}">{{ $statusBadge['label'] }}</span>
            </div>
            <small class="text-muted">
              {{ $kalibrasi->alatUkur->asset_code }} - {{ $kalibrasi->alatUkur->name }} | Dikalibrasi: {{ $kalibrasi->calibration_date->format('d/m/Y') }}
            </small>
          </div>
        </div>

        <div class="d-flex align-items-center gap-2">
          {{-- Download button --}}
          <a href="{{ route('kalibrasi.download-pdf', $kalibrasi->id) }}" target="_blank" class="btn btn-outline-primary btn-sm">
            <i class="ri-download-2-line me-1"></i> Download PDF
          </a>

          {{-- Approval Action Buttons (Only for Approver / Admin when Pending) --}}
          @if($canApprove)
            {{-- Reject Button --}}
            <button type="button" class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#modalReject">
              <i class="ri-close-line me-1"></i> Tolak (Reject)
            </button>

            {{-- 1-Click Approve Button --}}
            <form action="{{ route('kalibrasi.approve', $kalibrasi->id) }}" method="POST" class="d-inline"
                  onsubmit="return confirm('Apakah Anda yakin menyetujui sertifikat kalibrasi ini dan membubuhkan stempel tanda tangan digital?');">
              @csrf
              <button type="submit" class="btn btn-success btn-sm fw-bold shadow-sm">
                <i class="ri-check-line me-1"></i> 1-Click Approve &amp; Stamp
              </button>
            </form>
          @endif
        </div>
      </div>
    </div>

    {{-- Details & PDF Viewer Row --}}
    <div class="row g-4">
      {{-- Sidebar Information --}}
      <div class="col-12 col-lg-4">
        <div class="card shadow-sm border-0 mb-4">
          <div class="card-header border-bottom py-3">
            <h6 class="mb-0 fw-bold text-dark"><i class="ri-information-line text-primary me-1"></i> Informasi Kalibrasi</h6>
          </div>
          <div class="card-body pt-3">
            <div class="mb-3">
              <label class="form-label text-muted small mb-0">Nama Alat Ukur</label>
              <div class="fw-bold text-dark">{{ $kalibrasi->alatUkur->name }}</div>
            </div>
            <div class="mb-3">
              <label class="form-label text-muted small mb-0">No. Inventaris / Kode Aset</label>
              <div class="font-monospace fw-bold text-primary">{{ $kalibrasi->alatUkur->asset_code }}</div>
            </div>
            <div class="mb-3">
              <label class="form-label text-muted small mb-0">Merk / Model / SN</label>
              <div class="text-dark">{{ $kalibrasi->alatUkur->brand_model ?: '-' }} (SN: {{ $kalibrasi->alatUkur->serial_number ?: '-' }})</div>
            </div>
            <div class="mb-3">
              <label class="form-label text-muted small mb-0">Rentang / Spek</label>
              <div class="text-dark">{{ $kalibrasi->alatUkur->standard_spec ?: '-' }}</div>
            </div>
            <div class="mb-3">
              <label class="form-label text-muted small mb-0">Kesimpulan Hasil</label>
              @php
                $conc = $kalibrasi->conclusion_badge;
              @endphp
              <div><span class="badge {{ $conc['cls'] }} px-2 py-1">{{ $conc['label'] }}</span></div>
            </div>
            <div class="mb-3">
              <label class="form-label text-muted small mb-0">Dikalibrasi Oleh (Teknisi)</label>
              <div class="fw-semibold text-dark">{{ $kalibrasi->technician?->name ?: '-' }}</div>
              <small class="text-muted">{{ $kalibrasi->technician?->employee?->departements?->name ?: 'QA & Metrologi' }}</small>
            </div>
            <div class="mb-3">
              <label class="form-label text-muted small mb-0">Disetujui Oleh (Approver)</label>
              <div class="fw-semibold text-dark">{{ $kalibrasi->approver?->name ?: 'QA Manager' }}</div>
              <small class="text-muted">{{ $kalibrasi->approved_at ? 'Disetujui pada ' . $kalibrasi->approved_at->format('d/m/Y H:i') : 'Menunggu Persetujuan' }}</small>
            </div>
            <div class="border-top pt-3">
              <div class="d-flex justify-content-between text-muted small mb-1">
                <span>Tanggal Kalibrasi:</span>
                <b>{{ $kalibrasi->calibration_date->format('d/m/Y') }}</b>
              </div>
              <div class="d-flex justify-content-between text-muted small">
                <span>Jatuh Tempo Ulang:</span>
                <b class="text-primary">{{ $kalibrasi->next_due_date->format('d/m/Y') }}</b>
              </div>
            </div>
          </div>
        </div>

        {{-- Alur Informasi Dokumen Gabungan --}}
        <div class="card shadow-sm border-0">
          <div class="card-header border-bottom py-3">
            <h6 class="mb-0 fw-bold text-dark"><i class="ri-file-copy-2-line text-primary me-1"></i> Komposisi Halaman PDF</h6>
          </div>
          <div class="card-body pt-3">
            <ul class="list-group list-group-flush small">
              <li class="list-group-item px-0 d-flex align-items-center gap-2">
                <span class="badge bg-primary rounded-pill">Hal 1</span>
                <span>Template Sertifikat Baku Perusahaan (Auto-Fill &amp; Fixed Stamp TTD)</span>
              </li>
              <li class="list-group-item px-0 d-flex align-items-center gap-2">
                <span class="badge bg-secondary rounded-pill">Hal 2+</span>
                <span>Lampiran Lembar Hitungan Manual / Titik Ukur yang diunggah teknisi</span>
              </li>
            </ul>
          </div>
        </div>
      </div>

      {{-- PDF Viewer Area --}}
      <div class="col-12 col-lg-8">
        <div class="card shadow-sm border-0 h-100">
          <div class="card-header border-bottom py-3 d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-bold text-dark"><i class="ri-file-pdf-2-line text-danger me-1"></i> Pratinjau Dokumen Gabungan (Live PDF Preview)</h6>
            <a href="{{ route('kalibrasi.download-pdf', $kalibrasi->id) }}" target="_blank" class="btn btn-xs btn-outline-secondary">
              <i class="ri-external-link-line me-1"></i> Buka Penuh
            </a>
          </div>
          <div class="card-body p-0" style="min-height: 750px;">
            <iframe src="{{ route('kalibrasi.download-pdf', $kalibrasi->id) }}#toolbar=1" width="100%" height="750px" style="border: none; border-radius: 0 0 8px 8px;"></iframe>
          </div>
        </div>
      </div>
    </div>

  </div>
</div>

{{-- Modal Reject --}}
@if($canApprove)
<div class="modal fade" id="modalReject" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form action="{{ route('kalibrasi.reject', $kalibrasi->id) }}" method="POST">
        @csrf
        <div class="modal-header border-bottom py-3">
          <h5 class="modal-title fw-bold text-danger"><i class="ri-close-circle-line me-1"></i> Tolak Pengajuan Kalibrasi</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body py-4">
          <p class="mb-2">Silakan tuliskan alasan penolakan sertifikat kalibrasi ini:</p>
          <textarea name="rejection_note" class="form-control" rows="4" placeholder="Contoh: Titik ukur 50mm melebihi batas toleransi / lembar hitungan belum lengkap..." required></textarea>
        </div>
        <div class="modal-footer border-top py-2">
          <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-danger btn-sm fw-bold">Konfirmasi Penolakan</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endif
@endsection
