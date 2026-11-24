@extends('layouts/contentNavbarLayout')

@section('title', 'Detail Vendor - Master Data')

@section('content')
<div class="row">
  <div class="col-12 col-lg-9 mx-auto">
    <div class="card shadow-sm mb-4">
      <div class="card-header border-bottom py-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
        <h5 class="mb-0 fw-bold">
          <i class="ri-store-2-line text-primary me-1"></i> Detail Master Vendor: {{ $vendor->name }}
        </h5>
        <div class="d-flex align-items-center gap-2">
          <a href="{{ route('vendor.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="ri-arrow-left-line me-1"></i> Kembali
          </a>
          <a href="{{ route('vendor.edit', $vendor->id) }}" class="btn btn-primary btn-sm">
            <i class="ri-pencil-line me-1"></i> Edit Vendor
          </a>
        </div>
      </div>

      <div class="card-body pt-4">
        {{-- Header Summary Box --}}
        <div class="p-3 bg-light rounded-3 mb-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
          <div>
            <span class="badge bg-primary text-uppercase px-2 py-1 fs-6 mb-1">{{ $vendor->code }}</span>
            <h4 class="mb-1 fw-bold text-dark">{{ $vendor->name }}</h4>
            @if($vendor->official_name && $vendor->official_name !== $vendor->name)
              <p class="text-muted mb-0 small"><i class="ri-building-line me-1"></i>{{ $vendor->official_name }}</p>
            @endif
          </div>
          <div class="text-end">
            <div class="mb-1">
              @if($vendor->status == 1)
                <span class="badge bg-success"><i class="ri-checkbox-circle-line me-1"></i>Active</span>
              @else
                <span class="badge bg-danger"><i class="ri-close-circle-line me-1"></i>Inactive</span>
              @endif
            </div>
            <div>
              @if($vendor->is_supplier)
                <span class="badge bg-label-info me-1">Supplier</span>
              @endif
              @if($vendor->is_customer)
                <span class="badge bg-label-warning me-1">Customer</span>
              @endif
              @if(!$vendor->is_supplier && !$vendor->is_customer)
                <span class="badge bg-label-secondary">Vendor</span>
              @endif
            </div>
          </div>
        </div>

        {{-- Details Sections --}}
        <div class="row g-4">
          <div class="col-md-6">
            <h6 class="fw-bold text-muted text-uppercase small border-bottom pb-2 mb-3">
              <i class="ri-contacts-line me-1 text-primary"></i> Kontak & PIC
            </h6>
            <div class="mb-3">
              <label class="form-label text-muted small mb-0">Nama PIC / Kontak</label>
              <div class="fw-semibold text-dark">{{ $vendor->pic_name ?: '-' }}</div>
            </div>
            <div class="mb-3">
              <label class="form-label text-muted small mb-0">Nomor Telepon</label>
              <div class="fw-semibold text-dark">{{ $vendor->phone ?: '-' }}</div>
            </div>
            <div class="mb-3">
              <label class="form-label text-muted small mb-0">Nomor Fax</label>
              <div class="fw-semibold text-dark">{{ $vendor->fax ?: '-' }}</div>
            </div>
            <div class="mb-3">
              <label class="form-label text-muted small mb-0">Alamat Email</label>
              <div class="fw-semibold text-dark">
                @if($vendor->email)
                  <a href="mailto:{{ $vendor->email }}">{{ $vendor->email }}</a>
                @else
                  -
                @endif
              </div>
            </div>
          </div>

          <div class="col-md-6">
            <h6 class="fw-bold text-muted text-uppercase small border-bottom pb-2 mb-3">
              <i class="ri-map-pin-2-line me-1 text-primary"></i> Alamat & Lokasi
            </h6>
            <div class="mb-3">
              <label class="form-label text-muted small mb-0">Alamat Lengkap</label>
              <div class="fw-semibold text-dark" style="white-space: pre-line;">{{ $vendor->address ?: '-' }}</div>
            </div>
            <div class="mb-3">
              <label class="form-label text-muted small mb-0">Kode Pos</label>
              <div class="fw-semibold text-dark">{{ $vendor->postal_code ?: '-' }}</div>
            </div>
          </div>

          @if($vendor->notes)
          <div class="col-12">
            <h6 class="fw-bold text-muted text-uppercase small border-bottom pb-2 mb-3">
              <i class="ri-sticky-note-line me-1 text-primary"></i> Catatan Tambahan
            </h6>
            <div class="p-3 bg-lighter rounded border text-secondary" style="white-space: pre-line;">
              {{ $vendor->notes }}
            </div>
          </div>
          @endif

          <div class="col-12">
            <div class="border-top pt-3 d-flex justify-content-between text-muted small">
              <span>Dibuat: {{ $vendor->created_at ? $vendor->created_at->format('d/m/Y H:i') : '-' }}</span>
              <span>Terakhir Diperbarui: {{ $vendor->updated_at ? $vendor->updated_at->format('d/m/Y H:i') : '-' }}</span>
            </div>
          </div>
        </div>

      </div>
    </div>
  </div>
</div>
@endsection
