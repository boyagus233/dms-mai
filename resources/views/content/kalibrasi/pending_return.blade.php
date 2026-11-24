@extends('layouts/contentNavbarLayout')

@section('title', 'Kalibrasi Alat Ukur - Pending Return')

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
            <a class="nav-link active fw-bold" href="{{ route('kalibrasi.pending-return') }}">
              <i class="ri-truck-line me-1"></i> Pending Return (Di Vendor)
              @if($tools->total() > 0)
                <span class="badge rounded-pill bg-white text-primary ms-1">{{ $tools->total() }}</span>
              @endif
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link fw-semibold" href="{{ route('kalibrasi.approval-queue') }}">
              <i class="ri-shield-check-line me-1"></i> Antrean Approval
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
          <h5 class="mb-0 fw-bold text-dark"><i class="ri-truck-line text-warning me-1"></i> Dashboard Pending Return — Kalibrasi Eksternal</h5>
          <small class="text-muted">Daftar alat ukur yang saat ini sedang berada di luar pabrik / dibawa oleh vendor kalibrasi</small>
        </div>
      </div>

      <div class="table-responsive">
        <table class="table table-hover table-striped align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th width="15%">No. Alat Ukur</th>
              <th width="28%">Nama Alat & Spesifikasi</th>
              <th width="15%">Area Asal</th>
              <th width="18%">Tanggal Keluar</th>
              <th width="24%" class="text-center">Aksi Penerimaan</th>
            </tr>
          </thead>
          <tbody>
            @forelse($tools as $tool)
              <tr>
                <td>
                  <span class="fw-bold font-monospace text-primary fs-6">{{ $tool->asset_code }}</span>
                  @if($tool->serial_number)
                    <br><small class="text-muted">SN: {{ $tool->serial_number }}</small>
                  @endif
                </td>
                <td>
                  <div class="fw-bold text-dark">{{ $tool->name }}</div>
                  @if($tool->brand_model)
                    <small class="text-muted">{{ $tool->brand_model }}</small>
                  @endif
                  @if($tool->standard_spec)
                    <small class="text-secondary">&bull; Range: {{ $tool->standard_spec }}</small>
                  @endif
                </td>
                <td>
                  <span class="badge bg-label-secondary">{{ $tool->location ?: 'QA' }}</span>
                </td>
                <td>
                  <span class="fw-semibold text-warning">
                    <i class="ri-time-line me-1"></i>{{ $tool->updated_at->format('d/m/Y H:i') }}
                  </span>
                  <br><small class="text-muted">({{ $tool->updated_at->diffForHumans() }})</small>
                </td>
                <td class="text-center">
                  <a href="{{ route('kalibrasi.return-form', $tool->id) }}" class="btn btn-sm btn-success fw-bold shadow-sm">
                    <i class="ri-checkbox-circle-line me-1"></i> Barang Kembali &amp; Upload Sertifikat
                  </a>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="5" class="text-center py-5 text-muted">
                  <i class="ri-checkbox-circle-line fs-2 text-success d-block mb-2"></i>
                  Tidak ada alat ukur yang sedang berada di luar pabrik (Seluruh alat ada di premis).
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>

      @if($tools->hasPages())
        <div class="card-footer border-top py-2">
          {{ $tools->links() }}
        </div>
      @endif

    </div>
  </div>
</div>
@endsection
