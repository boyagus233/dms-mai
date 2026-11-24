@extends('layouts/contentNavbarLayout')

@section('title', 'Kalibrasi Alat Ukur - Arsip Sah & Riwayat')

@section('content')
<div class="row">
  <div class="col-12">
    
    {{-- Top Navigation Tabs --}}
    <div class="card mb-4 border-0 shadow-sm">
      <div class="card-body p-2 d-flex flex-wrap gap-2 align-items-center justify-content-between">
        <ul class="nav nav-pills flex-wrap gap-1">
          <li class="nav-item">
            <a class="nav-link fw-semibold" href="{{ route('kalibrasi.index') }}">
              <i class="ri-alarm-warning-line me-1"></i> Perlu Kalibrasi
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link fw-semibold" href="{{ route('kalibrasi.pending-return') }}">
              <i class="ri-truck-line me-1"></i> Pending Return (Di Vendor)
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link fw-semibold" href="{{ route('kalibrasi.approval-queue') }}">
              <i class="ri-shield-check-line me-1"></i> Antrean Approval
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link active fw-bold" href="{{ route('kalibrasi.archive') }}">
              <i class="ri-archive-line me-1"></i> Arsip Sah & Riwayat
            </a>
          </li>
        </ul>
      </div>
    </div>

    {{-- Main Card --}}
    <div class="card shadow-sm border-0">
      <div class="card-header border-bottom py-3 d-flex flex-wrap align-items-center justify-content-between gap-2">
        <div>
          <h5 class="mb-0 fw-bold text-dark"><i class="ri-archive-line text-success me-1"></i> Riwayat &amp; Database Sertifikat Kalibrasi (Arsip Sah)</h5>
          <small class="text-muted">Seluruh arsip sertifikat kalibrasi internal yang telah disetujui serta sertifikat resmi kalibrasi eksternal</small>
        </div>
      </div>

      {{-- Search & Filter --}}
      <div class="card-body border-bottom bg-lighter py-3">
        <form method="GET" action="{{ route('kalibrasi.archive') }}" class="row g-2 align-items-center">
          <div class="col-12 col-md-5">
            <div class="input-group input-group-merge">
              <span class="input-group-text"><i class="ri-search-line"></i></span>
              <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Cari nomor sertifikat, alat ukur, atau vendor..." />
            </div>
          </div>
          <div class="col-6 col-md-4">
            <select name="type" class="form-select" onchange="this.form.submit()">
              <option value="">Semua Jenis Kalibrasi</option>
              <option value="INTERNAL" @selected(request('type') === 'INTERNAL')>Kalibrasi Internal (KI)</option>
              <option value="EKSTERNAL" @selected(request('type') === 'EKSTERNAL')>Kalibrasi Eksternal (KE)</option>
            </select>
          </div>
          <div class="col-6 col-md-3 d-flex gap-1">
            <button type="submit" class="btn btn-primary w-100"><i class="ri-filter-3-line me-1"></i> Cari</button>
            @if(request()->hasAny(['search', 'type']))
              <a href="{{ route('kalibrasi.archive') }}" class="btn btn-outline-secondary" title="Reset"><i class="ri-refresh-line"></i></a>
            @endif
          </div>
        </form>
      </div>

      {{-- Table --}}
      <div class="table-responsive">
        <table class="table table-hover table-striped align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th width="18%">No. Sertifikat</th>
              <th width="8%" class="text-center">Tipe</th>
              <th width="24%">Alat Ukur</th>
              <th width="14%">Tgl Kalibrasi</th>
              <th width="14%">Next Due Date</th>
              <th width="10%" class="text-center">Hasil</th>
              <th width="12%" class="text-center">Dokumen PDF</th>
            </tr>
          </thead>
          <tbody>
            @forelse($records as $rec)
              @php
                $conc = $rec->conclusion_badge;
              @endphp
              <tr>
                <td>
                  <span class="fw-bold font-monospace text-primary fs-6">{{ $rec->certificate_number }}</span>
                  @if($rec->calibration_type === 'EKSTERNAL' && $rec->vendor_name)
                    <br><small class="text-muted"><i class="ri-building-line"></i> {{ $rec->vendor_name }}</small>
                  @endif
                </td>
                <td class="text-center">
                  @if($rec->calibration_type === 'INTERNAL')
                    <span class="badge bg-label-primary px-2 py-1">KI</span>
                  @else
                    <span class="badge bg-label-warning px-2 py-1">KE</span>
                  @endif
                </td>
                <td>
                  <div class="fw-bold text-dark">{{ $rec->alatUkur->name }}</div>
                  <small class="font-monospace text-muted">{{ $rec->alatUkur->asset_code }}</small>
                </td>
                <td>
                  <span class="fw-semibold">{{ $rec->calibration_date->format('d/m/Y') }}</span>
                  <br><small class="text-muted">Oleh: {{ $rec->technician?->name ?: '-' }}</small>
                </td>
                <td>
                  <span class="fw-semibold text-primary">{{ $rec->next_due_date->format('d/m/Y') }}</span>
                  <br><small class="text-success"><i class="ri-check-line"></i> Sah 1 Tahun</small>
                </td>
                <td class="text-center">
                  <span class="badge {{ $conc['cls'] }} px-2 py-1">
                    {{ $conc['label'] }}
                  </span>
                </td>
                <td class="text-center">
                  <div class="d-flex justify-content-center gap-1">
                    <a href="{{ route('kalibrasi.download-pdf', $rec->id) }}" target="_blank" class="btn btn-xs btn-outline-danger" title="Unduh PDF Sertifikat">
                      <i class="ri-file-pdf-2-line"></i> PDF
                    </a>
                    <a href="{{ route('kalibrasi.preview', $rec->id) }}" class="btn btn-xs btn-outline-info" title="Lihat Pratinjau">
                      <i class="ri-eye-line"></i>
                    </a>
                  </div>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="7" class="text-center py-5 text-muted">
                  <i class="ri-archive-line fs-2 d-block mb-2 text-secondary"></i>
                  Belum ada arsip sertifikat kalibrasi yang tersimpan.
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>

      @if($records->hasPages())
        <div class="card-footer border-top py-2">
          {{ $records->links() }}
        </div>
      @endif

    </div>
  </div>
</div>
@endsection
