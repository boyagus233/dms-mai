@extends('layouts/contentNavbarLayout')

@section('title', 'Kalibrasi Alat Ukur - Antrean Approval')

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
            <a class="nav-link active fw-bold" href="{{ route('kalibrasi.approval-queue') }}">
              <i class="ri-shield-check-line me-1"></i> Antrean Approval
              @if($items->total() > 0)
                <span class="badge rounded-pill bg-white text-primary ms-1">{{ $items->total() }}</span>
              @endif
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link fw-semibold" href="{{ route('kalibrasi.archive') }}">
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
          <h5 class="mb-0 fw-bold text-dark"><i class="ri-shield-check-line text-warning me-1"></i> Antrean Persetujuan Kalibrasi (Approval Queue)</h5>
          <small class="text-muted">Tinjau hasil kalibrasi teknisi dan berikan stempel persetujuan tanda tangan digital 1-Click</small>
        </div>
      </div>

      <div class="table-responsive">
        <table class="table table-hover table-striped align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th width="18%">No. Sertifikat</th>
              <th width="24%">Alat Ukur</th>
              <th width="15%">Teknisi Pengaju</th>
              <th width="13%">Tgl Kalibrasi</th>
              <th width="15%" class="text-center">Kesimpulan</th>
              <th width="15%" class="text-center">Aksi Tindakan</th>
            </tr>
          </thead>
          <tbody>
            @forelse($items as $item)
              @php
                $conc = $item->conclusion_badge;
              @endphp
              <tr>
                <td>
                  <span class="fw-bold font-monospace text-primary fs-6">{{ $item->certificate_number }}</span>
                  <br><small class="text-muted">Diajukan: {{ $item->created_at->diffForHumans() }}</small>
                </td>
                <td>
                  <div class="fw-bold text-dark">{{ $item->alatUkur->name }}</div>
                  <small class="text-muted font-monospace">{{ $item->alatUkur->asset_code }}</small>
                  @if($item->alatUkur->brand_model)
                    <small class="text-secondary">&bull; {{ $item->alatUkur->brand_model }}</small>
                  @endif
                </td>
                <td>
                  <div class="fw-semibold text-dark">{{ $item->technician?->name ?: 'Teknisi' }}</div>
                  <small class="text-muted">{{ $item->technician?->employee?->departements?->name ?: 'QA & Metrologi' }}</small>
                </td>
                <td>
                  <span class="fw-semibold">{{ $item->calibration_date->format('d/m/Y') }}</span>
                  <br><small class="text-muted">Next: {{ $item->next_due_date->format('d/m/Y') }}</small>
                </td>
                <td class="text-center">
                  <span class="badge {{ $conc['cls'] }} px-2 py-1">
                    {{ $conc['label'] }}
                  </span>
                </td>
                <td class="text-center">
                  <a href="{{ route('kalibrasi.preview', $item->id) }}" class="btn btn-sm btn-primary fw-bold shadow-sm">
                    <i class="ri-eye-line me-1"></i> Review &amp; Approve
                  </a>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="6" class="text-center py-5 text-muted">
                  <i class="ri-shield-check-line fs-2 text-success d-block mb-2"></i>
                  Tidak ada antrean approval pending. Semua pengajuan telah diproses!
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>

      @if($items->hasPages())
        <div class="card-footer border-top py-2">
          {{ $items->links() }}
        </div>
      @endif

    </div>
  </div>
</div>
@endsection
