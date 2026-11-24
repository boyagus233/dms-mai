@extends('layouts/contentNavbarLayout')

@section('title', 'Edit Vendor - Master Data')

@section('content')
<div class="row">
  <div class="col-12 col-lg-9 mx-auto">
    <div class="card shadow-sm mb-4">
      <div class="card-header border-bottom py-3 d-flex align-items-center justify-content-between">
        <h5 class="mb-0 fw-bold"><i class="ri-pencil-line text-primary me-1"></i> Edit Master Vendor: {{ $vendor->name }}</h5>
        <a href="{{ route('vendor.index') }}" class="btn btn-outline-secondary btn-sm">
          <i class="ri-arrow-left-line me-1"></i> Kembali
        </a>
      </div>
      <div class="card-body pt-4">
        <form action="{{ route('vendor.update', $vendor->id) }}" method="POST">
          @csrf
          @method('PUT')

          <div class="row g-3">
            <div class="col-md-4">
              <label class="form-label fw-semibold" for="code">Kode Vendor <span class="text-danger">*</span></label>
              <input type="text" class="form-control @error('code') is-invalid @enderror" id="code" name="code" value="{{ old('code', $vendor->code) }}" required />
              @error('code')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            <div class="col-md-8">
              <label class="form-label fw-semibold" for="name">Nama Perusahaan / Vendor <span class="text-danger">*</span></label>
              <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $vendor->name) }}" required />
              @error('name')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            <div class="col-md-12">
              <label class="form-label" for="official_name">Nama Resmi (Official Name)</label>
              <input type="text" class="form-control @error('official_name') is-invalid @enderror" id="official_name" name="official_name" value="{{ old('official_name', $vendor->official_name) }}" />
              @error('official_name')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            <div class="col-md-6">
              <label class="form-label" for="pic_name">Nama PIC / Kontak</label>
              <input type="text" class="form-control @error('pic_name') is-invalid @enderror" id="pic_name" name="pic_name" value="{{ old('pic_name', $vendor->pic_name) }}" />
              @error('pic_name')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            <div class="col-md-6">
              <label class="form-label" for="phone">Nomor Telepon</label>
              <input type="text" class="form-control @error('phone') is-invalid @enderror" id="phone" name="phone" value="{{ old('phone', $vendor->phone) }}" />
              @error('phone')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            <div class="col-md-6">
              <label class="form-label" for="email">Alamat Email</label>
              <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email', $vendor->email) }}" />
              @error('email')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            <div class="col-md-3">
              <label class="form-label" for="fax">Nomor FAX</label>
              <input type="text" class="form-control @error('fax') is-invalid @enderror" id="fax" name="fax" value="{{ old('fax', $vendor->fax) }}" />
            </div>

            <div class="col-md-3">
              <label class="form-label" for="postal_code">Kode Pos</label>
              <input type="text" class="form-control @error('postal_code') is-invalid @enderror" id="postal_code" name="postal_code" value="{{ old('postal_code', $vendor->postal_code) }}" />
            </div>

            <div class="col-12">
              <label class="form-label" for="address">Alamat Lengkap</label>
              <textarea class="form-control @error('address') is-invalid @enderror" id="address" name="address" rows="3">{{ old('address', $vendor->address) }}</textarea>
            </div>

            <div class="col-12">
              <label class="form-label" for="notes">Catatan Tambahan (Notes)</label>
              <textarea class="form-control @error('notes') is-invalid @enderror" id="notes" name="notes" rows="2">{{ old('notes', $vendor->notes) }}</textarea>
            </div>

            <div class="col-md-6">
              <label class="form-label fw-semibold d-block">Tipe Relasi</label>
              <div class="form-check form-check-inline mt-1">
                <input class="form-check-input" type="checkbox" id="is_supplier" name="is_supplier" value="1" {{ old('is_supplier', $vendor->is_supplier) ? 'checked' : '' }} />
                <label class="form-check-label" for="is_supplier">Supplier</label>
              </div>
              <div class="form-check form-check-inline mt-1">
                <input class="form-check-input" type="checkbox" id="is_customer" name="is_customer" value="1" {{ old('is_customer', $vendor->is_customer) ? 'checked' : '' }} />
                <label class="form-check-label" for="is_customer">Customer</label>
              </div>
            </div>

            <div class="col-md-6">
              <label class="form-label fw-semibold d-block">Status Keaktifan <span class="text-danger">*</span></label>
              <div class="form-check form-check-inline mt-1">
                <input class="form-check-input" type="radio" name="status" id="status_active" value="1" {{ old('status', $vendor->status) == 1 ? 'checked' : '' }} />
                <label class="form-check-label text-success fw-bold" for="status_active">Aktif (Active)</label>
              </div>
              <div class="form-check form-check-inline mt-1">
                <input class="form-check-input" type="radio" name="status" id="status_inactive" value="0" {{ old('status', $vendor->status) === 0 ? 'checked' : '' }} />
                <label class="form-check-label text-secondary" for="status_inactive">Non-aktif (Inactive)</label>
              </div>
            </div>

            <div class="col-12 border-top pt-3 d-flex justify-content-end gap-2">
              <a href="{{ route('vendor.index') }}" class="btn btn-outline-secondary">Batal</a>
              <button type="submit" class="btn btn-primary d-flex align-items-center gap-1">
                <i class="ri-save-line"></i>
                <span>Simpan Perubahan</span>
              </button>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
@endsection
