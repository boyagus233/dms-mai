@extends('layouts/contentNavbarLayout')

@section('title', 'Kalibrasi Alat Ukur - Perlu Kalibrasi')

@section('content')
<div class="row">
  <div class="col-12">
    
    {{-- Top Navigation Tabs --}}
    <div class="card mb-4 border-0 shadow-sm">
      <div class="card-body p-2 d-flex flex-wrap gap-2 align-items-center justify-content-between">
        <ul class="nav nav-pills flex-wrap gap-1">
          <li class="nav-item">
            <a class="nav-link active fw-bold" href="{{ route('kalibrasi.index') }}">
              <i class="ri-alarm-warning-line me-1"></i> Perlu Kalibrasi
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link fw-semibold" href="{{ route('kalibrasi.pending-return') }}">
              <i class="ri-truck-line me-1"></i> Pending Return (Di Vendor)
              @if($counts['pending_return'] > 0)
                <span class="badge rounded-pill bg-danger ms-1">{{ $counts['pending_return'] }}</span>
              @endif
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link fw-semibold" href="{{ route('kalibrasi.approval-queue') }}">
              <i class="ri-shield-check-line me-1"></i> Antrean Approval
              @if($counts['pending_approval'] > 0)
                <span class="badge rounded-pill bg-warning text-dark ms-1">{{ $counts['pending_approval'] }}</span>
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

    {{-- Metrics Summary Cards --}}
    <div class="row g-3 mb-4">
      <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm text-center py-3">
          <div class="text-muted small fw-semibold text-uppercase">Total Alat Terdaftar</div>
          <h3 class="mb-0 fw-bold text-primary mt-1">{{ number_format($counts['total']) }}</h3>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm text-center py-3 border-start border-danger border-4">
          <div class="text-danger small fw-semibold text-uppercase">Lewat Jatuh Tempo</div>
          <h3 class="mb-0 fw-bold text-danger mt-1">{{ number_format($counts['overdue']) }}</h3>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm text-center py-3 border-start border-warning border-4">
          <div class="text-warning small fw-semibold text-uppercase">Jatuh Tempo &le; 30 Hari</div>
          <h3 class="mb-0 fw-bold text-warning mt-1">{{ number_format($counts['due_soon']) }}</h3>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm text-center py-3 border-start border-info border-4">
          <div class="text-info small fw-semibold text-uppercase">Sedang di Luar (Vendor)</div>
          <h3 class="mb-0 fw-bold text-info mt-1">{{ number_format($counts['pending_return']) }}</h3>
        </div>
      </div>
    </div>

    {{-- Main Card: Table Alat Ukur --}}
    <div class="card shadow-sm border-0">
      <div class="card-header border-bottom py-3 d-flex flex-wrap align-items-center justify-content-between gap-2">
        <div>
          <h5 class="mb-0 fw-bold text-dark"><i class="ri-compass-3-line text-primary me-1"></i> Daftar Alat Ukur — Perlu Kalibrasi</h5>
          <small class="text-muted">Diurutkan otomatis dari tanggal jatuh tempo kalibrasi terdekat</small>
        </div>
      </div>

      {{-- Filter & Search Form --}}
      <div class="card-body border-bottom bg-lighter py-3">
        <form method="GET" action="{{ route('kalibrasi.index') }}" class="row g-2 align-items-center">
          <div class="col-12 col-md-4">
            <div class="input-group input-group-merge">
              <span class="input-group-text"><i class="ri-search-line"></i></span>
              <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Cari kode alat, nama, model, no seri..." />
            </div>
          </div>
          <div class="col-6 col-md-3">
            <select name="category" class="form-select" onchange="this.form.submit()">
              <option value="">Semua Tipe (KI & KE)</option>
              <option value="KI" @selected(request('category') === 'KI')>KI — Kalibrasi Internal</option>
              <option value="KE" @selected(request('category') === 'KE')>KE — Kalibrasi Eksternal</option>
            </select>
          </div>
          <div class="col-6 col-md-3">
            <select name="due_filter" class="form-select" onchange="this.form.submit()">
              <option value="">Semua Status Waktu</option>
              <option value="overdue" @selected(request('due_filter') === 'overdue')>Lewat Jatuh Tempo (Overdue)</option>
              <option value="due_30" @selected(request('due_filter') === 'due_30')>Jatuh Tempo &le; 30 Hari</option>
              <option value="safe" @selected(request('due_filter') === 'safe')>Jadwal Aman</option>
            </select>
          </div>
          <div class="col-12 col-md-2 d-flex gap-1">
            <button type="submit" class="btn btn-primary w-100"><i class="ri-filter-3-line me-1"></i> Filter</button>
            @if(request()->hasAny(['search', 'category', 'due_filter']))
              <a href="{{ route('kalibrasi.index') }}" class="btn btn-outline-secondary" title="Reset Filter"><i class="ri-refresh-line"></i></a>
            @endif
          </div>
        </form>
      </div>

      {{-- Table --}}
      <div class="table-responsive">
        <table class="table table-hover table-striped align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th width="12%">No. Alat Ukur</th>
              <th width="8%" class="text-center">Tipe</th>
              <th width="24%">Nama Alat & Spesifikasi</th>
              <th width="12%">Area / Lokasi</th>
              <th width="12%">Tgl Terakhir</th>
              <th width="16%">Jatuh Tempo Berikutnya</th>
              <th width="16%" class="text-center">Aksi Tindakan</th>
            </tr>
          </thead>
          <tbody>
            @forelse($tools as $tool)
              @php
                $meta = $tool->due_meta;
              @endphp
              <tr>
                <td>
                  <span class="fw-bold font-monospace text-primary fs-6">{{ $tool->asset_code }}</span>
                  @if($tool->serial_number)
                    <br><small class="text-muted"><i class="ri-barcode-line"></i> SN: {{ $tool->serial_number }}</small>
                  @endif
                </td>
                <td class="text-center">
                  @if($tool->category_type === 'KI')
                    <span class="badge bg-label-primary px-2 py-1">KI</span>
                  @else
                    <span class="badge bg-label-warning px-2 py-1">KE</span>
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
                  <span class="badge bg-label-secondary"><i class="ri-map-pin-line me-1"></i>{{ $tool->location ?: 'QA' }}</span>
                </td>
                <td>
                  <span class="text-muted">{{ $tool->last_calibration_date ? $tool->last_calibration_date->format('d/m/Y') : '-' }}</span>
                </td>
                <td>
                  <div class="fw-semibold">{{ $tool->next_calibration_date->format('d/m/Y') }}</div>
                  <span class="badge {{ $meta['badge_cls'] }} mt-1">
                    {{ $meta['label'] }}
                  </span>
                </td>
                <td class="text-center">
                  @if($tool->current_status === 'OUT_OF_PREMISE')
                    <span class="badge bg-label-info mb-1 d-block"><i class="ri-truck-line me-1"></i>Di Vendor</span>
                    <a href="{{ route('kalibrasi.return-form', $tool->id) }}" class="btn btn-xs btn-success w-100 shadow-sm">
                      <i class="ri-check-line me-1"></i> Barang Kembali
                    </a>
                  @elseif($tool->current_status === 'PENDING_APPROVAL')
                    <span class="badge bg-warning text-dark mb-1 d-block"><i class="ri-time-line me-1"></i>Approval Atasan</span>
                    @php
                      $activePending = $tool->kalibrasiRecords()->where('status', 'PENDING_APPROVAL')->first();
                    @endphp
                    @if($activePending)
                      <a href="{{ route('kalibrasi.preview', $activePending->id) }}" class="btn btn-xs btn-outline-primary w-100">
                        <i class="ri-eye-line me-1"></i> Lihat Draf
                      </a>
                    @endif
                  @else
                    {{-- Status ACTIVE: Pilihan Kalibrasi Internal atau Eksternal --}}
                    <div class="d-flex flex-column gap-1">
                      <a href="{{ route('kalibrasi.create-internal', $tool->id) }}" class="btn btn-xs btn-primary d-flex align-items-center justify-content-center gap-1 shadow-sm">
                        <i class="ri-tools-line"></i> Kalibrasi Internal
                      </a>
                      <button type="button" class="btn btn-xs btn-outline-warning d-flex align-items-center justify-content-center gap-1"
                              data-bs-toggle="modal" data-bs-target="#modalEksternal{{ $tool->id }}">
                        <i class="ri-truck-line"></i> Kalibrasi Eksternal
                      </button>
                    </div>

                    {{-- Modal Konfirmasi Kalibrasi Eksternal --}}
                    <div class="modal fade" id="modalEksternal{{ $tool->id }}" tabindex="-1" aria-hidden="true">
                      <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content text-start">
                          <form action="{{ route('kalibrasi.mark-eksternal', $tool->id) }}" method="POST">
                            @csrf
                            <div class="modal-header border-bottom py-3">
                              <h5 class="modal-title fw-bold"><i class="ri-truck-line text-warning me-1"></i> Kalibrasi Eksternal</h5>
                              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body py-4">
                              <p class="mb-3">Tandai alat ukur berikut untuk dibawa keluar pabrik oleh driver/vendor kalibrasi:</p>
                              <div class="p-3 bg-light rounded border mb-3">
                                <div><b>Kode:</b> <span class="font-monospace text-primary">{{ $tool->asset_code }}</span></div>
                                <div><b>Nama:</b> {{ $tool->name }}</div>
                                <div><b>Jatuh Tempo:</b> {{ $tool->next_calibration_date->format('d/m/Y') }}</div>
                              </div>
                              <div class="alert alert-warning d-flex align-items-center py-2 mb-0" role="alert">
                                <i class="ri-information-line me-2 fs-5"></i>
                                <small>Status alat akan berubah menjadi <b>OUT OF PREMISE (Sedang Kalibrasi)</b> dan masuk ke tab <b>Pending Return</b>.</small>
                              </div>
                            </div>
                            <div class="modal-footer border-top py-2">
                              <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                              <button type="submit" class="btn btn-warning btn-sm fw-bold">
                                <i class="ri-check-line me-1"></i> Tandai Keluar Pabrik
                              </button>
                            </div>
                          </form>
                        </div>
                      </div>
                    </div>
                  @endif
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="7" class="text-center py-5 text-muted">
                  <i class="ri-inbox-line fs-2 d-block mb-2"></i>
                  Tidak ada alat ukur yang sesuai dengan kriteria filter.
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>

      {{-- Pagination --}}
      @if($tools->hasPages())
        <div class="card-footer border-top d-flex justify-content-between align-items-center py-2">
          <small class="text-muted">Menampilkan {{ $tools->firstItem() }} - {{ $tools->lastItem() }} dari {{ $tools->total() }} alat ukur</small>
          {{ $tools->links() }}
        </div>
      @endif

    </div>
  </div>
</div>
@endsection
