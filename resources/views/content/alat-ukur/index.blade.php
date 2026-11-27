@extends('layouts/contentNavbarLayout')

@section('title', 'Master Data Alat Ukur')

@section('content')
<div class="row">
  <div class="col-12">

    {{-- Metrics Summary Cards --}}
    <div class="row g-3 mb-4">
      <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm text-center py-3">
          <div class="text-muted small fw-semibold text-uppercase">Total Alat Ukur</div>
          <h3 class="mb-0 fw-bold text-primary mt-1">{{ number_format($counts['total']) }}</h3>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm text-center py-3 border-start border-info border-4">
          <div class="text-info small fw-semibold text-uppercase">Internal (KI)</div>
          <h3 class="mb-0 fw-bold text-info mt-1">{{ number_format($counts['ki']) }}</h3>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm text-center py-3 border-start border-warning border-4">
          <div class="text-warning small fw-semibold text-uppercase">Eksternal (KE)</div>
          <h3 class="mb-0 fw-bold text-warning mt-1">{{ number_format($counts['ke']) }}</h3>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm text-center py-3 border-start border-success border-4">
          <div class="text-success small fw-semibold text-uppercase">Status Active</div>
          <h3 class="mb-0 fw-bold text-success mt-1">{{ number_format($counts['active']) }}</h3>
        </div>
      </div>
    </div>

    <div class="card shadow-sm border-0">
      <div class="card-header border-bottom py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
          <h5 class="card-title mb-0 fw-bold">
            <i class="ri-compass-3-line text-primary me-1"></i> Master Data Alat Ukur
          </h5>
          <small class="text-muted">Kelola daftar master alat ukur (Create, Update, Delete &amp; Export)</small>
        </div>
        <div class="d-flex align-items-center gap-2 flex-wrap">
          <a href="{{ route('alat-ukur.export', request()->query()) }}" class="btn btn-outline-success btn-sm d-flex align-items-center gap-1 shadow-sm">
            <i class="ri-file-excel-2-line"></i>
            <span>Export CSV</span>
          </a>
          <a href="{{ route('alat-ukur.create') }}" class="btn btn-primary btn-sm d-flex align-items-center gap-1 shadow-sm">
            <i class="ri-add-line"></i>
            <span>Tambah Alat Ukur</span>
          </a>
        </div>
      </div>

      {{-- Search & Filter Bar --}}
      <div class="card-body border-bottom bg-lighter py-3">
        <form method="GET" action="{{ route('alat-ukur.index') }}" class="row g-2 align-items-center">
          <div class="col-12 col-md-4">
            <div class="input-group input-group-merge">
              <span class="input-group-text"><i class="ri-search-line"></i></span>
              <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Cari No. Alat, nama, model, serial, area, note..." />
            </div>
          </div>
          <div class="col-6 col-md-2">
            <select name="category_type" class="form-select" onchange="this.form.submit()">
              <option value="">Semua KI / KE</option>
              <option value="KI" @selected(request('category_type') === 'KI')>KI (Internal)</option>
              <option value="KE" @selected(request('category_type') === 'KE')>KE (Eksternal)</option>
            </select>
          </div>
          <div class="col-6 col-md-2">
            <select name="location" class="form-select searchable-select" data-placeholder="Semua Area" onchange="this.form.submit()">
              <option value="">Semua Area</option>
              @foreach($areas as $area)
                <option value="{{ $area }}" @selected(request('location') === $area)>{{ $area }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-6 col-md-2">
            <select name="current_status" class="form-select" onchange="this.form.submit()">
              <option value="">Semua Status</option>
              @foreach($statuses as $code => $meta)
                <option value="{{ $code }}" @selected(request('current_status') === $code)>{{ $meta['label'] }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-6 col-md-1">
            <select name="per_page" class="form-select" onchange="this.form.submit()" title="Jumlah per halaman">
              @foreach([10, 25, 50, 100, 250] as $pp)
                <option value="{{ $pp }}" @selected((int) request('per_page', 25) === $pp)>{{ $pp }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-12 col-md-1 d-flex gap-1">
            <button type="submit" class="btn btn-primary w-100" title="Terapkan Filter"><i class="ri-filter-3-line"></i></button>
            @if(request()->hasAny(['search', 'category_type', 'location', 'current_status', 'per_page']))
              <a href="{{ route('alat-ukur.index') }}" class="btn btn-outline-secondary" title="Reset Filter"><i class="ri-refresh-line"></i></a>
            @endif
          </div>
        </form>
      </div>

      {{-- 13-Column Master Data Table --}}
      <div class="table-responsive text-nowrap">
        <table class="table table-hover table-striped table-bordered align-middle mb-0" style="font-size: 0.84rem;">
          <thead class="table-light">
            <tr>
              <th class="text-center">Aksi</th>
              <th>No. Alat Ukur</th>
              <th class="text-center">KI/KE</th>
              <th>Alat Ukur</th>
              <th>Standard</th>
              <th>Model</th>
              <th>No.Serial</th>
              <th>Kalibrator</th>
              <th>Area</th>
              <th>Last Kalibrasi</th>
              <th>Next Kalibrasi</th>
              <th class="text-center">Status</th>
              <th class="text-center">Picture</th>
              <th>Note</th>
            </tr>
          </thead>
          <tbody>
            @forelse($tools as $tool)
              @php
                $statusMeta = $tool->status_meta;
                $dueMeta = $tool->due_meta;
              @endphp
              <tr>
                <td class="text-center">
                  <div class="d-flex justify-content-center gap-1">
                    <a href="{{ route('alat-ukur.show', $tool->id) }}" class="btn btn-xs btn-icon btn-outline-info" title="Lihat Detail">
                      <i class="ri-eye-line"></i>
                    </a>
                    <a href="{{ route('alat-ukur.edit', $tool->id) }}" class="btn btn-xs btn-icon btn-outline-primary" title="Edit Alat Ukur">
                      <i class="ri-pencil-line"></i>
                    </a>
                    <form action="{{ route('alat-ukur.destroy', $tool->id) }}" method="POST" class="d-inline delete-tool-form"
                          data-code="{{ $tool->asset_code }}"
                          data-name="{{ $tool->name }}"
                          data-history="{{ $tool->kalibrasi_records_count }}">
                      @csrf
                      @method('DELETE')
                      <button type="submit" class="btn btn-xs btn-icon btn-outline-danger" title="Hapus Alat Ukur">
                        <i class="ri-delete-bin-6-line"></i>
                      </button>
                    </form>
                  </div>
                </td>
                <td>
                  <a href="{{ route('alat-ukur.show', $tool->id) }}" class="fw-bold font-monospace text-primary">
                    {{ $tool->asset_code }}
                  </a>
                </td>
                <td class="text-center">
                  @if($tool->category_type === 'KI')
                    <span class="badge bg-label-primary">KI</span>
                  @else
                    <span class="badge bg-label-warning">KE</span>
                  @endif
                </td>
                <td style="min-width: 170px; white-space: normal;">
                  <span class="fw-semibold text-dark">{{ $tool->name }}</span>
                </td>
                <td>{{ $tool->standard_spec ?: '-' }}</td>
                <td>{{ $tool->brand_model ?: '-' }}</td>
                <td class="font-monospace">{{ $tool->serial_number ?: '-' }}</td>
                <td>{{ $tool->calibrator_ref ?: '-' }}</td>
                <td>
                  @if($tool->location)
                    <span class="badge bg-label-secondary">{{ $tool->location }}</span>
                  @else
                    <span class="text-muted">-</span>
                  @endif
                </td>
                <td>{{ $tool->last_calibration_date ? $tool->last_calibration_date->format('d/m/Y') : '-' }}</td>
                <td>
                  @if($tool->next_calibration_date)
                    <div class="fw-semibold">{{ $tool->next_calibration_date->format('d/m/Y') }}</div>
                    <small class="badge {{ $dueMeta['badge_cls'] }}">{{ $dueMeta['label'] }}</small>
                  @else
                    <span class="text-muted">-</span>
                  @endif
                </td>
                <td class="text-center">
                  <span class="badge {{ $statusMeta['cls'] }}">{{ $statusMeta['label'] }}</span>
                </td>
                <td class="text-center">
                  @if($tool->photo_url)
                    <a href="javascript:void(0)" class="d-inline-block picture-preview-trigger" data-src="{{ $tool->photo_url }}" data-title="{{ $tool->asset_code }} — {{ $tool->name }}">
                      <img src="{{ $tool->photo_url }}" alt="{{ $tool->asset_code }}" class="rounded border" style="height: 36px; width: 36px; object-fit: cover;">
                    </a>
                  @else
                    <span class="text-muted">-</span>
                  @endif
                </td>
                <td style="max-width: 200px; white-space: normal;">
                  <small class="text-muted">{{ $tool->notes ?: '-' }}</small>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="14" class="text-center py-5 text-muted">
                  <i class="ri-inbox-line fs-2 d-block mb-2"></i>
                  Belum ada data alat ukur yang sesuai dengan filter.
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>

      <div class="card-footer border-top py-2">
        @if($tools->hasPages())
          {{ $tools->links() }}
        @else
          <small class="text-muted">Menampilkan {{ $tools->total() }} alat ukur</small>
        @endif
      </div>
    </div>
  </div>
</div>

{{-- Modal Lightbox Picture Preview --}}
<div class="modal fade" id="picturePreviewModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-md">
    <div class="modal-content">
      <div class="modal-header py-2 border-bottom">
        <h6 class="modal-title fw-bold" id="picturePreviewTitle">Picture Alat Ukur</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body text-center p-3">
        <img id="picturePreviewImg" src="" alt="Preview" class="img-fluid rounded">
      </div>
    </div>
  </div>
</div>
@endsection

@section('page-script')
<script>
  document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.picture-preview-trigger').forEach(function (el) {
      el.addEventListener('click', function () {
        document.getElementById('picturePreviewTitle').textContent = this.dataset.title || 'Picture Alat Ukur';
        document.getElementById('picturePreviewImg').src = this.dataset.src;
        const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('picturePreviewModal'));
        modal.show();
      });
    });

    document.querySelectorAll('.delete-tool-form').forEach(function (form) {
      form.addEventListener('submit', function (e) {
        const code = this.dataset.code;
        const name = this.dataset.name;
        const hist = parseInt(this.dataset.history || '0', 10);
        const msg = hist > 0
          ? `Alat ukur ${code} (${name}) memiliki ${hist} riwayat kalibrasi yang juga akan ikut terhapus. Yakin ingin menghapus?`
          : `Yakin ingin menghapus alat ukur ${code} (${name}) dari master data?`;

        if (typeof window.Swal !== 'undefined') {
          e.preventDefault();
          const targetForm = this;
          window.Swal.fire({
            title: 'Hapus Alat Ukur?',
            text: msg,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ff4c51',
            confirmButtonText: 'Ya, Hapus',
            cancelButtonText: 'Batal'
          }).then(function (result) {
            if (result.isConfirmed) {
              targetForm.submit();
            }
          });
        } else if (!confirm(msg)) {
          e.preventDefault();
        }
      });
    });
  });
</script>
@endsection
