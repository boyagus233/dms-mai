@extends('layouts/contentNavbarLayout')

@section('title', 'Penerimaan Barang Kembali - Kalibrasi Eksternal')

@section('content')
<div class="row">
  <div class="col-12 col-lg-9 mx-auto">
    <div class="card shadow-sm border-0 mb-4">
      <div class="card-header border-bottom py-3 d-flex align-items-center justify-content-between">
        <h5 class="mb-0 fw-bold">
          <i class="ri-checkbox-circle-line text-success me-1"></i> Penerimaan Barang Kembali — Kalibrasi Eksternal
        </h5>
        <a href="{{ route('kalibrasi.pending-return') }}" class="btn btn-outline-secondary btn-sm">
          <i class="ri-arrow-left-line me-1"></i> Kembali
        </a>
      </div>

      <div class="card-body pt-4">
        {{-- Box Info Alat Ukur --}}
        <div class="p-3 bg-light rounded-3 border mb-4">
          <div class="d-flex justify-content-between align-items-center mb-1">
            <span class="badge bg-warning text-dark font-monospace">{{ $alatUkur->asset_code }}</span>
            <span class="badge bg-label-secondary">Alat Fisik Kembali</span>
          </div>
          <h4 class="mb-1 fw-bold text-dark">{{ $alatUkur->name }}</h4>
          <div class="text-muted small">
            Merk: <b>{{ $alatUkur->brand_model ?: '-' }}</b> | SN: <b>{{ $alatUkur->serial_number ?: '-' }}</b> | Lokasi: <b>{{ $alatUkur->location }}</b>
          </div>
        </div>

        <form action="{{ route('kalibrasi.store-return', $alatUkur->id) }}" method="POST" enctype="multipart/form-data">
          @csrf

          <div class="row g-3">
            {{-- Vendor Selection --}}
            <div class="col-md-6">
              <label class="form-label fw-semibold" for="vendor_id">Laboratorium / Vendor Kalibrasi <span class="text-danger">*</span></label>
              <select name="vendor_id" id="vendor_id" class="form-select @error('vendor_id') is-invalid @enderror" required>
                <option value="">-- Pilih Vendor Kalibrasi --</option>
                @foreach($vendors as $v)
                  <option value="{{ $v->id }}" @selected(old('vendor_id') == $v->id)>
                    {{ $v->code }} - {{ $v->name }}
                  </option>
                @endforeach
              </select>
              @error('vendor_id')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            <div class="col-md-6">
              <label class="form-label fw-semibold" for="certificate_number">Nomor Sertifikat dari Vendor <span class="text-danger">*</span></label>
              <input type="text" class="form-control @error('certificate_number') is-invalid @enderror" 
                     id="certificate_number" name="certificate_number" value="{{ old('certificate_number') }}" 
                     placeholder="Contoh: KAN-CAL-2026-9901" required />
              @error('certificate_number')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            {{-- Dates --}}
            <div class="col-md-6">
              <label class="form-label fw-semibold" for="calibration_date">Tanggal Kalibrasi (Sesuai Sertifikat Vendor) <span class="text-danger">*</span></label>
              <input type="date" class="form-control @error('calibration_date') is-invalid @enderror" 
                     id="calibration_date" name="calibration_date" value="{{ old('calibration_date', $defaultCalDate) }}" required />
              @error('calibration_date')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            <div class="col-md-6">
              <label class="form-label fw-semibold" for="next_due_date">Tanggal Kalibrasi Ulang Berikutnya (Next Due) <span class="text-danger">*</span></label>
              <input type="date" class="form-control @error('next_due_date') is-invalid @enderror" 
                     id="next_due_date" name="next_due_date" value="{{ old('next_due_date', $defaultNextDue) }}" required />
              <small class="text-muted">Otomatis dihitung +1 tahun (12 bulan) untuk reminder jadwal berikutnya.</small>
              @error('next_due_date')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            {{-- Kesimpulan --}}
            <div class="col-12">
              <label class="form-label fw-semibold">Hasil Keputusan Sertifikat Vendor <span class="text-danger">*</span></label>
              <div class="row g-2">
                <div class="col-md-4">
                  <div class="form-check p-3 border rounded @if(old('conclusion', 'PASS') === 'PASS') border-success bg-light @endif">
                    <input class="form-check-input" type="radio" name="conclusion" id="concPass" value="PASS" 
                           @checked(old('conclusion', 'PASS') === 'PASS') required>
                    <label class="form-check-label fw-bold text-success" for="concPass">
                      <i class="ri-checkbox-circle-line me-1"></i> Laik Pakai (Pass)
                    </label>
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="form-check p-3 border rounded @if(old('conclusion') === 'PASS_WITH_NOTE') border-warning bg-light @endif">
                    <input class="form-check-input" type="radio" name="conclusion" id="concNote" value="PASS_WITH_NOTE" 
                           @checked(old('conclusion') === 'PASS_WITH_NOTE')>
                    <label class="form-check-label fw-bold text-warning" for="concNote">
                      <i class="ri-alert-line me-1"></i> Laik dengan Catatan
                    </label>
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="form-check p-3 border rounded @if(old('conclusion') === 'FAIL') border-danger bg-light @endif">
                    <input class="form-check-input" type="radio" name="conclusion" id="concFail" value="FAIL" 
                           @checked(old('conclusion') === 'FAIL')>
                    <label class="form-check-label fw-bold text-danger" for="concFail">
                      <i class="ri-close-circle-line me-1"></i> Tidak Laik (Fail)
                    </label>
                  </div>
                </div>
              </div>
            </div>

            {{-- Upload PDF Sertifikat Vendor --}}
            <div class="col-12">
              <div class="p-3 bg-lighter border rounded-3">
                <label class="form-label fw-bold text-dark" for="vendor_cert_pdf">
                  <i class="ri-file-pdf-2-line text-danger me-1"></i> Upload File PDF Sertifikat Resmi Vendor <span class="text-danger">*</span>
                </label>
                <input type="file" class="form-control @error('vendor_cert_pdf') is-invalid @enderror" 
                       id="vendor_cert_pdf" name="vendor_cert_pdf" accept="application/pdf" required />
                <small class="text-muted d-block mt-1">Unggah scan PDF sertifikat sah yang diterbitkan oleh laboratorium vendor (Maks. 15MB).</small>
                @error('vendor_cert_pdf')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
            </div>

            {{-- Bypass Approval Info Box --}}
            <div class="col-12">
              <div class="alert alert-success d-flex align-items-center mb-0 py-3" role="alert">
                <i class="ri-shield-check-line fs-3 me-3 text-success"></i>
                <div>
                  <h6 class="mb-0 fw-bold text-success">Bypass Approval (Auto-Close)</h6>
                  <p class="mb-0 small text-dark">
                    Sertifikat kalibrasi dari vendor eksternal berakreditasi KAN/ISO 17025 langsung dinyatakan <b>SAH &amp; COMPLETED</b> tanpa perlu dikirim ke atasan lagi. Status alat ukur otomatis aktif kembali.
                  </p>
                </div>
              </div>
            </div>

            {{-- Buttons --}}
            <div class="col-12 mt-4 d-flex justify-content-between align-items-center">
              <a href="{{ route('kalibrasi.pending-return') }}" class="btn btn-outline-secondary">
                <i class="ri-close-line me-1"></i> Batal
              </a>
              <button type="submit" class="btn btn-success fw-bold shadow-sm">
                <i class="ri-check-double-line me-1"></i> Simpan &amp; Aktifkan Alat Kembali
              </button>
            </div>

          </div>
        </form>
      </div>
    </div>
  </div>
</div>
@endsection

@section('page-script')
<script>
  document.getElementById('calibration_date')?.addEventListener('change', function() {
    const val = this.value;
    if (val) {
      const dt = new Date(val);
      dt.setFullYear(dt.getFullYear() + 1);
      const y = dt.getFullYear();
      const m = String(dt.getMonth() + 1).padStart(2, '0');
      const d = String(dt.getDate()).padStart(2, '0');
      document.getElementById('next_due_date').value = `${y}-${m}-${d}`;
    }
  });
</script>
@endsection
