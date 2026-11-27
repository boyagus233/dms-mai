@extends('layouts/contentNavbarLayout')

@section('title', 'Detail Alat Ukur - Master Data')

@section('content')
@php
  $statusMeta = $alatUkur->status_meta;
  $dueMeta = $alatUkur->due_meta;
@endphp
<div class="row">
  <div class="col-12 col-xl-10 mx-auto">
    <div class="card shadow-sm mb-4">
      <div class="card-header border-bottom py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
          <h5 class="mb-0 fw-bold">
            <i class="ri-compass-3-line text-primary me-1"></i> Detail Master Alat Ukur
          </h5>
          <small class="text-muted">Informasi lengkap spesifikasi &amp; jadwal kalibrasi alat ukur</small>
        </div>
        <div class="d-flex align-items-center gap-2">
          <a href="{{ route('alat-ukur.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="ri-arrow-left-line me-1"></i> Kembali
          </a>
          <a href="{{ route('alat-ukur.edit', $alatUkur->id) }}" class="btn btn-primary btn-sm">
            <i class="ri-pencil-line me-1"></i> Edit Data
          </a>
          <form action="{{ route('alat-ukur.destroy', $alatUkur->id) }}" method="POST" class="d-inline"
                onsubmit="return confirm('Hapus alat ukur {{ addslashes($alatUkur->asset_code) }}? {{ $alatUkur->kalibrasiRecords->count() > 0 ? 'Perhatian: riwayat kalibrasi terkait juga akan ikut terhapus.' : '' }}')">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-outline-danger btn-sm">
              <i class="ri-delete-bin-6-line me-1"></i> Hapus
            </button>
          </form>
        </div>
      </div>

      <div class="card-body pt-4">
        {{-- Summary Header --}}
        <div class="p-3 bg-light rounded-3 mb-4 d-flex justify-content-between align-items-center flex-wrap gap-3">
          <div>
            <div class="d-flex align-items-center gap-2 mb-1">
              <span class="badge bg-primary font-monospace fs-6">{{ $alatUkur->asset_code }}</span>
              <span class="badge {{ $alatUkur->category_type === 'KI' ? 'bg-label-primary' : 'bg-label-warning' }}">
                {{ $alatUkur->category_type === 'KI' ? 'KI — Internal' : 'KE — Eksternal' }}
              </span>
              <span class="badge {{ $statusMeta['cls'] }}">{{ $statusMeta['label'] }}</span>
            </div>
            <h4 class="mb-1 fw-bold text-dark">{{ $alatUkur->name }}</h4>
            <small class="text-muted">Area: <b>{{ $alatUkur->location ?: '-' }}</b> &bull; Interval Kalibrasi: <b>{{ $alatUkur->calibration_interval_months ?: 12 }} Bulan</b></small>
          </div>
          <div class="text-md-end">
            <div class="small text-muted">Next Kalibrasi</div>
            <div class="fs-5 fw-bold text-dark">{{ $alatUkur->next_calibration_date ? $alatUkur->next_calibration_date->format('d/m/Y') : '-' }}</div>
            <span class="badge {{ $dueMeta['badge_cls'] }} mt-1">{{ $dueMeta['label'] }}</span>
          </div>
        </div>

        <div class="row g-4">
          <div class="col-md-4">
            <div class="border rounded-3 p-3 text-center bg-lighter h-100 d-flex flex-column justify-content-center align-items-center">
              @if($alatUkur->photo_url)
                <img src="{{ $alatUkur->photo_url }}" alt="{{ $alatUkur->name }}" class="img-fluid rounded shadow-sm mb-2" style="max-height: 220px; object-fit: contain;">
                <small class="text-muted">Picture Alat Ukur</small>
              @else
                <i class="ri-image-line fs-1 text-muted mb-2"></i>
                <div class="text-muted small">Belum ada Picture</div>
              @endif
            </div>
          </div>

          <div class="col-md-8">
            <div class="row g-3">
              <div class="col-sm-6">
                <label class="form-label text-muted small mb-0">No. Alat Ukur</label>
                <div class="fw-bold font-monospace text-primary">{{ $alatUkur->asset_code }}</div>
              </div>
              <div class="col-sm-6">
                <label class="form-label text-muted small mb-0">KI / KE</label>
                <div class="fw-semibold">{{ $alatUkur->category_type }}</div>
              </div>
              <div class="col-sm-6">
                <label class="form-label text-muted small mb-0">Alat Ukur</label>
                <div class="fw-semibold">{{ $alatUkur->name }}</div>
              </div>
              <div class="col-sm-6">
                <label class="form-label text-muted small mb-0">Standard</label>
                <div class="fw-semibold">{{ $alatUkur->standard_spec ?: '-' }}</div>
              </div>
              <div class="col-sm-6">
                <label class="form-label text-muted small mb-0">Model</label>
                <div class="fw-semibold">{{ $alatUkur->brand_model ?: '-' }}</div>
              </div>
              <div class="col-sm-6">
                <label class="form-label text-muted small mb-0">No. Serial</label>
                <div class="fw-semibold font-monospace">{{ $alatUkur->serial_number ?: '-' }}</div>
              </div>
              <div class="col-sm-6">
                <label class="form-label text-muted small mb-0">Kalibrator</label>
                <div class="fw-semibold">{{ $alatUkur->calibrator_ref ?: '-' }}</div>
              </div>
              <div class="col-sm-6">
                <label class="form-label text-muted small mb-0">Area</label>
                <div class="fw-semibold">{{ $alatUkur->location ?: '-' }}</div>
              </div>
              <div class="col-sm-6">
                <label class="form-label text-muted small mb-0">Last Kalibrasi</label>
                <div class="fw-semibold">{{ $alatUkur->last_calibration_date ? $alatUkur->last_calibration_date->format('d/m/Y') : '-' }}</div>
              </div>
              <div class="col-sm-6">
                <label class="form-label text-muted small mb-0">Next Kalibrasi</label>
                <div class="fw-semibold">{{ $alatUkur->next_calibration_date ? $alatUkur->next_calibration_date->format('d/m/Y') : '-' }}</div>
              </div>
              <div class="col-12">
                <label class="form-label text-muted small mb-0">Note</label>
                <div class="p-2 bg-lighter rounded border text-dark small" style="white-space: pre-line;">{{ $alatUkur->notes ?: '-' }}</div>
              </div>
            </div>
          </div>
        </div>

        <div class="border-top mt-4 pt-3 d-flex justify-content-between text-muted small flex-wrap gap-2">
          <span>Dibuat: {{ $alatUkur->created_at ? $alatUkur->created_at->format('d/m/Y H:i') : '-' }}{{ $alatUkur->creator ? ' oleh ' . $alatUkur->creator->name : '' }}</span>
          <span>Diperbarui: {{ $alatUkur->updated_at ? $alatUkur->updated_at->format('d/m/Y H:i') : '-' }}{{ $alatUkur->updater ? ' oleh ' . $alatUkur->updater->name : '' }}</span>
        </div>
      </div>
    </div>

    {{-- Calibration History --}}
    <div class="card shadow-sm">
      <div class="card-header border-bottom py-3 d-flex justify-content-between align-items-center">
        <h6 class="mb-0 fw-bold"><i class="ri-history-line text-primary me-1"></i> Riwayat Kalibrasi Alat Ini</h6>
        <span class="badge bg-label-secondary">{{ $alatUkur->kalibrasiRecords->count() }} transaksi</span>
      </div>
      <div class="table-responsive">
        <table class="table table-sm table-striped align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th>No. Sertifikat</th>
              <th>Tipe</th>
              <th>Tgl Kalibrasi</th>
              <th>Next Due</th>
              <th>Hasil</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            @forelse($alatUkur->kalibrasiRecords as $rec)
              <tr>
                <td class="font-monospace">{{ $rec->certificate_number }}</td>
                <td>{{ $rec->calibration_type }}</td>
                <td>{{ $rec->calibration_date ? $rec->calibration_date->format('d/m/Y') : '-' }}</td>
                <td>{{ $rec->next_due_date ? $rec->next_due_date->format('d/m/Y') : '-' }}</td>
                <td>{{ $rec->conclusion }}</td>
                <td><span class="badge bg-label-info">{{ $rec->status }}</span></td>
              </tr>
            @empty
              <tr>
                <td colspan="6" class="text-center py-4 text-muted">Belum ada riwayat kalibrasi untuk alat ini.</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
@endsection
