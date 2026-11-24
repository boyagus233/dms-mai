@extends('layouts/contentNavbarLayout')

@section('title', 'Input Kalibrasi Internal')

@section('content')
<div class="row">
  <div class="col-12 col-lg-10 mx-auto">
    <div class="card shadow-sm border-0 mb-4">
      <div class="card-header border-bottom py-3 d-flex align-items-center justify-content-between">
        <h5 class="mb-0 fw-bold">
          <i class="ri-tools-line text-primary me-1"></i> Form Pengajuan Kalibrasi Internal
        </h5>
        <a href="{{ route('kalibrasi.index') }}" class="btn btn-outline-secondary btn-sm">
          <i class="ri-arrow-left-line me-1"></i> Kembali
        </a>
      </div>

      <div class="card-body pt-4">
        {{-- Box Info Alat Ukur (Auto-Filled) --}}
        <div class="p-3 bg-light rounded-3 border mb-4">
          <div class="d-flex justify-content-between align-items-center mb-2">
            <span class="badge bg-primary fs-6 font-monospace">{{ $alatUkur->asset_code }}</span>
            <span class="badge bg-label-info"><i class="ri-settings-4-line me-1"></i>Kalibrasi Internal (KI)</span>
          </div>
          <h4 class="mb-2 fw-bold text-dark">{{ $alatUkur->name }}</h4>
          <div class="row g-2 text-muted small">
            <div class="col-6 col-md-3">
              <span class="d-block text-secondary">Merk / Model:</span>
              <strong class="text-dark">{{ $alatUkur->brand_model ?: '-' }}</strong>
            </div>
            <div class="col-6 col-md-3">
              <span class="d-block text-secondary">Nomor Seri (SN):</span>
              <strong class="text-dark">{{ $alatUkur->serial_number ?: '-' }}</strong>
            </div>
            <div class="col-6 col-md-3">
              <span class="d-block text-secondary">Rentang / Spek:</span>
              <strong class="text-dark">{{ $alatUkur->standard_spec ?: '-' }}</strong>
            </div>
            <div class="col-6 col-md-3">
              <span class="d-block text-secondary">Area / Lokasi:</span>
              <strong class="text-dark">{{ $alatUkur->location ?: 'QA Lab' }}</strong>
            </div>
          </div>
        </div>

        <form action="{{ route('kalibrasi.store-internal', $alatUkur->id) }}" method="POST" enctype="multipart/form-data">
          @csrf

          <div class="row g-3">
            {{-- Tanggal Kalibrasi & Next Due Date --}}
            <div class="col-md-6">
              <label class="form-label fw-semibold" for="calibration_date">Tanggal Pelaksanaan Kalibrasi <span class="text-danger">*</span></label>
              <input type="date" class="form-control @error('calibration_date') is-invalid @enderror" 
                     id="calibration_date" name="calibration_date" value="{{ old('calibration_date', $defaultCalDate) }}" required />
              @error('calibration_date')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            <div class="col-md-6">
              <label class="form-label fw-semibold" for="next_due_date">Tanggal Kalibrasi Ulang (Due Date) <span class="text-danger">*</span></label>
              <input type="date" class="form-control @error('next_due_date') is-invalid @enderror" 
                     id="next_due_date" name="next_due_date" value="{{ old('next_due_date', $defaultNextDue) }}" required />
              <small class="text-muted">Sistem otomatis menghitung +1 tahun (12 bulan) ke depan.</small>
              @error('next_due_date')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            {{-- Kesimpulan Hasil --}}
            <div class="col-12">
              <label class="form-label fw-semibold">Kesimpulan Hasil Kalibrasi <span class="text-danger">*</span></label>
              <div class="row g-2">
                <div class="col-md-4">
                  <div class="form-check p-3 border rounded @if(old('conclusion', 'PASS') === 'PASS') border-success bg-light @endif">
                    <input class="form-check-input" type="radio" name="conclusion" id="concPass" value="PASS" 
                           @checked(old('conclusion', 'PASS') === 'PASS') required>
                    <label class="form-check-label fw-bold text-success" for="concPass">
                      <i class="ri-checkbox-circle-line me-1"></i> Laik Pakai (Pass)
                    </label>
                    <small class="d-block text-muted mt-1">Alat memenuhi toleransi dan siap digunakan di lini produksi.</small>
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="form-check p-3 border rounded @if(old('conclusion') === 'PASS_WITH_NOTE') border-warning bg-light @endif">
                    <input class="form-check-input" type="radio" name="conclusion" id="concNote" value="PASS_WITH_NOTE" 
                           @checked(old('conclusion') === 'PASS_WITH_NOTE')>
                    <label class="form-check-label fw-bold text-warning" for="concNote">
                      <i class="ri-alert-line me-1"></i> Laik dengan Catatan
                    </label>
                    <small class="d-block text-muted mt-1">Alat dapat dipakai dengan faktor koreksi tertentu.</small>
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="form-check p-3 border rounded @if(old('conclusion') === 'FAIL') border-danger bg-light @endif">
                    <input class="form-check-input" type="radio" name="conclusion" id="concFail" value="FAIL" 
                           @checked(old('conclusion') === 'FAIL')>
                    <label class="form-check-label fw-bold text-danger" for="concFail">
                      <i class="ri-close-circle-line me-1"></i> Tidak Laik Pakai (Fail)
                    </label>
                    <small class="d-block text-muted mt-1">Penyimpangan di luar batas toleransi maksimum.</small>
                  </div>
                </div>
              </div>
              @error('conclusion')
                <div class="text-danger small mt-1">{{ $message }}</div>
              @enderror
            </div>

            {{-- Standar Acuan Kalibrator --}}
            <div class="col-md-6">
              <label class="form-label" for="calibrator_used">Alat Standar Kalibrator Acuan</label>
              <input type="text" class="form-control @error('calibrator_used') is-invalid @enderror" 
                     id="calibrator_used" name="calibrator_used" 
                     value="{{ old('calibrator_used', $alatUkur->calibrator_ref ?: 'Caliper Checker / Ceramic Gauge Block') }}" />
              @error('calibrator_used')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            {{-- Kondisi Lingkungan Lab --}}
            <div class="col-md-3">
              <label class="form-label" for="temperature">Suhu Ruangan (&deg;C)</label>
              <div class="input-group">
                <input type="number" step="0.1" class="form-control @error('temperature') is-invalid @enderror" 
                       id="temperature" name="temperature" value="{{ old('temperature', '20.0') }}" />
                <span class="input-group-text">&deg;C</span>
              </div>
              @error('temperature')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            <div class="col-md-3">
              <label class="form-label" for="humidity">Kelembaban (% RH)</label>
              <div class="input-group">
                <input type="number" step="0.1" class="form-control @error('humidity') is-invalid @enderror" 
                       id="humidity" name="humidity" value="{{ old('humidity', '55.0') }}" />
                <span class="input-group-text">%</span>
              </div>
              @error('humidity')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            {{-- Upload PDF Lembar Hitungan Manual Teknisi --}}
            <div class="col-12">
              <div class="p-3 bg-lighter border rounded-3">
                <label class="form-label fw-bold text-dark" for="attachment_pdf">
                  <i class="ri-file-pdf-2-line text-danger me-1"></i> Upload Lampiran Hasil Pengukuran Manual (PDF) <span class="text-danger">*</span>
                </label>
                <input type="file" class="form-control @error('attachment_pdf') is-invalid @enderror" 
                       id="attachment_pdf" name="attachment_pdf" accept="application/pdf" required />
                <small class="text-muted d-block mt-1">
                  Unggah file PDF hasil hitungan lembar kerja, tabel titik ukur, atau kurva kalibrasi (Maks. 15MB). Sistem akan otomatis menggabungkan file ini menjadi <b>Halaman 2 dst.</b> pada sertifikat final.
                </small>
                @error('attachment_pdf')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
            </div>

            {{-- Auto-Routing Info Box --}}
            <div class="col-12">
              <div class="alert alert-info d-flex align-items-center mb-0 py-3" role="alert">
                <i class="ri-git-branch-line fs-3 me-3 text-info"></i>
                <div>
                  <h6 class="mb-0 fw-bold text-info">Auto-Routing Persetujuan (Tanpa Memilih Atasan)</h6>
                  <p class="mb-0 small text-dark">
                    Setelah tombol submit ditekan, sistem otomatis meneruskan pengajuan ini ke atasan Anda:
                    <b>{{ $approver?->name ?: 'QA & Metrology Manager' }}</b>
                    ({{ $approver?->employee?->positions?->name ?: 'QA Manager' }}) untuk mendapatkan review & persetujuan stempel digital 1-Click.
                  </p>
                </div>
              </div>
            </div>

            {{-- Buttons --}}
            <div class="col-12 mt-4 d-flex justify-content-between align-items-center">
              <a href="{{ route('kalibrasi.index') }}" class="btn btn-outline-secondary">
                <i class="ri-close-line me-1"></i> Batal
              </a>
              <button type="submit" class="btn btn-primary fw-bold shadow-sm">
                <i class="ri-send-plane-fill me-1"></i> Submit Pengajuan &amp; Kirim ke Atasan
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
