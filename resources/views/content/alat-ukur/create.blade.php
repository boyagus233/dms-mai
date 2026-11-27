@extends('layouts/contentNavbarLayout')

@section('title', 'Tambah Alat Ukur - Master Data')

@section('content')
<div class="row">
  <div class="col-12 col-xl-10 mx-auto">
    <div class="card shadow-sm mb-4">
      <div class="card-header border-bottom py-3 d-flex align-items-center justify-content-between">
        <div>
          <h5 class="mb-0 fw-bold"><i class="ri-compass-3-line text-primary me-1"></i> Tambah Master Alat Ukur</h5>
          <small class="text-muted">Tambahkan data alat ukur baru ke master data kalibrasi</small>
        </div>
        <a href="{{ route('alat-ukur.index') }}" class="btn btn-outline-secondary btn-sm">
          <i class="ri-arrow-left-line me-1"></i> Kembali
        </a>
      </div>
      <div class="card-body pt-4">
        <form action="{{ route('alat-ukur.store') }}" method="POST" enctype="multipart/form-data">
          @csrf
          @include('content.alat-ukur._form')

          <div class="border-top pt-3 mt-4 d-flex justify-content-end gap-2">
            <a href="{{ route('alat-ukur.index') }}" class="btn btn-outline-secondary">Batal</a>
            <button type="submit" class="btn btn-primary d-flex align-items-center gap-1 shadow-sm">
              <i class="ri-save-line"></i>
              <span>Simpan Alat Ukur</span>
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
@endsection
