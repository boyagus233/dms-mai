@extends('layouts/contentNavbarLayout')

@section('title', 'Input Kalibrasi Internal')

@section('styles')
<style>
  .conclusion-card {
    border: 2px solid #e7e7e8 !important;
    background-color: #fff;
    cursor: pointer;
    border-radius: 0.5rem;
  }
  .conclusion-card:hover {
    border-color: #c4c4c7 !important;
    background-color: #fafbfc;
  }
  .conclusion-card.active-pass {
    border-color: #56ca00 !important;
    background-color: rgba(86, 202, 0, 0.06) !important;
    box-shadow: 0 2px 6px rgba(86, 202, 0, 0.15);
  }
  .conclusion-card.active-note {
    border-color: #ffb400 !important;
    background-color: rgba(255, 180, 0, 0.06) !important;
    box-shadow: 0 2px 6px rgba(255, 180, 0, 0.15);
  }
  .conclusion-card.active-fail {
    border-color: #ff4c51 !important;
    background-color: rgba(255, 76, 81, 0.06) !important;
    box-shadow: 0 2px 6px rgba(255, 76, 81, 0.15);
  }
</style>
@endsection

@section('content')
<div class="row">
  <div class="col-12 col-xl-10 mx-auto">
    <div class="card shadow-sm border-0 mb-4">
      <div class="card-header border-bottom py-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div class="d-flex align-items-center gap-2">
          <div class="avatar avatar-sm me-1">
            <span class="avatar-initial rounded-circle bg-label-primary text-primary">
              <i class="ri-tools-line fs-5"></i>
            </span>
          </div>
          <div>
            <h5 class="mb-0 fw-bold">Form Pengajuan Kalibrasi Internal</h5>
            <small class="text-muted">Isi ringkasan hasil kalibrasi dan unggah lembar ukur teknisi</small>
          </div>
        </div>
        <a href="{{ route('kalibrasi.index') }}" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1 shadow-sm">
          <i class="ri-arrow-left-line"></i>
          <span>Kembali</span>
        </a>
      </div>

      <div class="card-body pt-4">
        {{-- Box Info Alat Ukur (Auto-Filled) --}}
        <div class="p-3 bg-lighter rounded-3 border mb-4">
          <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
            <span class="badge bg-primary fs-6 font-monospace px-3 py-2 shadow-sm">{{ $alatUkur->asset_code }}</span>
            <span class="badge bg-label-info py-2 px-3"><i class="ri-settings-4-line me-1"></i> Kalibrasi Internal (KI)</span>
          </div>
          <h4 class="mb-3 fw-bold text-dark">{{ $alatUkur->name }}</h4>
          <div class="row g-3 text-muted">
            <div class="col-6 col-md-3">
              <div class="d-flex align-items-center gap-2">
                <i class="ri-price-tag-3-line text-secondary fs-5"></i>
                <div>
                  <small class="d-block text-secondary">Merk / Model</small>
                  <strong class="text-dark">{{ $alatUkur->brand_model ?: '-' }}</strong>
                </div>
              </div>
            </div>
            <div class="col-6 col-md-3">
              <div class="d-flex align-items-center gap-2">
                <i class="ri-barcode-line text-secondary fs-5"></i>
                <div>
                  <small class="d-block text-secondary">Nomor Seri (SN)</small>
                  <strong class="text-dark">{{ $alatUkur->serial_number ?: '-' }}</strong>
                </div>
              </div>
            </div>
            <div class="col-6 col-md-3">
              <div class="d-flex align-items-center gap-2">
                <i class="ri-ruler-2-line text-secondary fs-5"></i>
                <div>
                  <small class="d-block text-secondary">Rentang / Spek</small>
                  <strong class="text-dark">{{ $alatUkur->standard_spec ?: '-' }}</strong>
                </div>
              </div>
            </div>
            <div class="col-6 col-md-3">
              <div class="d-flex align-items-center gap-2">
                <i class="ri-map-pin-2-line text-secondary fs-5"></i>
                <div>
                  <small class="d-block text-secondary">Area / Lokasi</small>
                  <strong class="text-dark">{{ $alatUkur->location ?: 'QA Lab' }}</strong>
                </div>
              </div>
            </div>
          </div>
        </div>

        <form action="{{ route('kalibrasi.store-internal', $alatUkur->id) }}" method="POST" enctype="multipart/form-data">
          @csrf

          <div class="row g-3">
            {{-- Tanggal Kalibrasi & Next Due Date --}}
            <div class="col-md-6">
              <label class="form-label fw-semibold" for="calibration_date">Tanggal Pelaksanaan Kalibrasi <span class="text-danger">*</span></label>
              <div class="input-group">
                <span class="input-group-text"><i class="ri-calendar-event-line"></i></span>
                <input type="date" class="form-control @error('calibration_date') is-invalid @enderror" 
                       id="calibration_date" name="calibration_date" value="{{ old('calibration_date', $defaultCalDate) }}" required />
              </div>
              @error('calibration_date')
                <div class="text-danger small mt-1">{{ $message }}</div>
              @enderror
            </div>

            <div class="col-md-6">
              <label class="form-label fw-semibold" for="next_due_date">Tanggal Kalibrasi Ulang (Due Date) <span class="text-danger">*</span></label>
              <div class="input-group">
                <span class="input-group-text"><i class="ri-calendar-check-line"></i></span>
                <input type="date" class="form-control @error('next_due_date') is-invalid @enderror" 
                       id="next_due_date" name="next_due_date" value="{{ old('next_due_date', $defaultNextDue) }}" required />
              </div>
              <small class="text-muted d-block mt-1"><i class="ri-information-line me-1"></i> Otomatis disarankan +1 tahun, dapat diubah manual sesuai kebutuhan.</small>
              @error('next_due_date')
                <div class="text-danger small mt-1">{{ $message }}</div>
              @enderror
            </div>

            {{-- Kesimpulan Hasil Kalibrasi (Custom Radio Cards) --}}
            <div class="col-12 mt-4">
              <label class="form-label fw-semibold mb-2">
                Kesimpulan Hasil Kalibrasi <span class="text-danger">*</span>
              </label>
              <div class="row g-3" id="conclusionOptions">
                {{-- Option 1: Pass --}}
                <div class="col-md-4">
                  <label class="conclusion-card card h-100 p-3 text-decoration-none" for="concPass">
                    <div class="d-flex align-items-center mb-1">
                      <input class="form-check-input m-0 me-2" type="radio" name="conclusion" id="concPass" value="PASS" 
                             @checked(old('conclusion', 'PASS') === 'PASS') required style="width: 1.25rem; height: 1.25rem; cursor: pointer;">
                      <span class="fw-bold text-success fs-6">
                        <i class="ri-checkbox-circle-fill me-1"></i> Laik Pakai (Pass)
                      </span>
                    </div>
                    <small class="text-muted d-block" style="padding-left: 2rem;">
                      Alat memenuhi toleransi dan siap digunakan di lini produksi.
                    </small>
                  </label>
                </div>

                {{-- Option 2: Pass with Note --}}
                <div class="col-md-4">
                  <label class="conclusion-card card h-100 p-3 text-decoration-none" for="concNote">
                    <div class="d-flex align-items-center mb-1">
                      <input class="form-check-input m-0 me-2" type="radio" name="conclusion" id="concNote" value="PASS_WITH_NOTE" 
                             @checked(old('conclusion') === 'PASS_WITH_NOTE') style="width: 1.25rem; height: 1.25rem; cursor: pointer;">
                      <span class="fw-bold text-warning fs-6">
                        <i class="ri-alert-fill me-1"></i> Laik dengan Catatan
                      </span>
                    </div>
                    <small class="text-muted d-block" style="padding-left: 2rem;">
                      Alat dapat dipakai dengan faktor koreksi tertentu.
                    </small>
                  </label>
                </div>

                {{-- Option 3: Fail --}}
                <div class="col-md-4">
                  <label class="conclusion-card card h-100 p-3 text-decoration-none" for="concFail">
                    <div class="d-flex align-items-center mb-1">
                      <input class="form-check-input m-0 me-2" type="radio" name="conclusion" id="concFail" value="FAIL" 
                             @checked(old('conclusion') === 'FAIL') style="width: 1.25rem; height: 1.25rem; cursor: pointer;">
                      <span class="fw-bold text-danger fs-6">
                        <i class="ri-close-circle-fill me-1"></i> Tidak Laik Pakai (Fail)
                      </span>
                    </div>
                    <small class="text-muted d-block" style="padding-left: 2rem;">
                      Penyimpangan di luar batas toleransi maksimum.
                    </small>
                  </label>
                </div>
              </div>
              @error('conclusion')
                <div class="text-danger small mt-1">{{ $message }}</div>
              @enderror
            </div>

            {{-- Standar Acuan Kalibrator --}}
            <div class="col-md-6 mt-4">
              <label class="form-label fw-semibold" for="calibrator_used">Alat Standar Kalibrator Acuan</label>
              <div class="input-group">
                <span class="input-group-text"><i class="ri-compass-3-line"></i></span>
                <input type="text" class="form-control @error('calibrator_used') is-invalid @enderror" 
                       id="calibrator_used" name="calibrator_used" 
                       value="{{ old('calibrator_used', $alatUkur->calibrator_ref ?: 'Caliper Checker / Ceramic Gauge Block') }}" 
                       placeholder="Contoh: Caliper Checker / Ceramic Gauge Block" />
              </div>
              @error('calibrator_used')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            {{-- Kondisi Lingkungan Lab --}}
            <div class="col-md-3 mt-4">
              <label class="form-label fw-semibold" for="temperature">Suhu Ruangan (&deg;C)</label>
              <div class="input-group">
                <span class="input-group-text"><i class="ri-temp-hot-line"></i></span>
                <input type="number" step="0.1" class="form-control @error('temperature') is-invalid @enderror" 
                       id="temperature" name="temperature" value="{{ old('temperature', '20.0') }}" />
                <span class="input-group-text">&deg;C</span>
              </div>
              @error('temperature')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            <div class="col-md-3 mt-4">
              <label class="form-label fw-semibold" for="humidity">Kelembaban (% RH)</label>
              <div class="input-group">
                <span class="input-group-text"><i class="ri-drop-line"></i></span>
                <input type="number" step="0.1" class="form-control @error('humidity') is-invalid @enderror" 
                       id="humidity" name="humidity" value="{{ old('humidity', '55.0') }}" />
                <span class="input-group-text">%</span>
              </div>
              @error('humidity')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            {{-- Upload PDF Lembar Hitungan Manual Teknisi --}}
            <div class="col-12 mt-4">
              <label class="form-label fw-semibold text-dark mb-1" for="attachment_pdf">
                Upload Lampiran Hasil Pengukuran Manual (PDF) <span class="text-danger">*</span>
              </label>
              
              <div class="p-4 border-2 border-dashed rounded-3 text-center position-relative bg-lighter" id="dropzoneContainer" style="border-style: dashed; border-color: #c4c4c7;">
                <input type="file" class="position-absolute top-0 start-0 w-100 h-100 opacity-0" 
                       id="attachment_pdf" name="attachment_pdf" accept="application/pdf" required style="cursor: pointer; z-index: 5;" />
                
                <div id="uploadPrompt">
                  <div class="mb-2">
                    <span class="avatar avatar-md rounded-circle bg-label-primary p-2">
                      <i class="ri-upload-cloud-2-line fs-2 text-primary"></i>
                    </span>
                  </div>
                  <h6 class="fw-bold mb-1 text-dark">Klik atau Seret Berkas PDF ke Sini</h6>
                  <p class="text-muted small mb-2">Format wajib <b>.pdf</b> (Maksimal 15MB). Berkas ini otomatis digabungkan sebagai Halaman 2 dst. pada sertifikat.</p>
                  <button type="button" class="btn btn-outline-primary btn-sm px-3" style="pointer-events: none;">
                    <i class="ri-folder-open-line me-1"></i> Pilih Berkas PDF
                  </button>
                </div>

                <div id="uploadSuccess" class="d-none align-items-center justify-content-between p-3 bg-white border rounded text-start position-relative shadow-sm" style="z-index: 6;">
                  <div class="d-flex align-items-center gap-3">
                    <i class="ri-file-pdf-2-fill text-danger fs-1"></i>
                    <div>
                      <div class="fw-bold text-dark text-break" id="displayFileName">file_name.pdf</div>
                      <small class="text-muted" id="displayFileSize">0 KB</small>
                      <span class="badge bg-label-success ms-2"><i class="ri-check-line me-1"></i> Berkas Terpilih</span>
                    </div>
                  </div>
                  <button type="button" class="btn btn-sm btn-outline-danger" id="btnResetFile">
                    <i class="ri-refresh-line me-1"></i> Ganti Berkas
                  </button>
                </div>
              </div>

              @error('attachment_pdf')
                <div class="text-danger small mt-1">{{ $message }}</div>
              @enderror
            </div>

            {{-- Auto-Routing Info Card --}}
            <div class="col-12 mt-4">
              <div class="card bg-label-primary border-0 p-3 shadow-none">
                <div class="d-flex align-items-center flex-wrap gap-3">
                  <div class="avatar avatar-md flex-shrink-0">
                    <span class="avatar-initial rounded-circle bg-primary text-white">
                      <i class="ri-git-branch-line fs-4"></i>
                    </span>
                  </div>
                  <div class="flex-grow-1">
                    <div class="d-flex align-items-center gap-2 mb-1">
                      <span class="badge bg-primary">Auto-Routing Persetujuan</span>
                      <span class="fw-semibold text-primary small">Tanpa Perlu Memilih Atasan Manual</span>
                    </div>
                    <p class="mb-0 text-dark small">
                      Setelah tombol submit ditekan, pengajuan ini otomatis diteruskan ke antrean atasan:
                      <strong>{{ $approver?->name ?: 'Admin' }}</strong>
                      ({{ $approver?->employee?->positions?->name ?: 'QA & Metrology Manager' }}) untuk mendapatkan review lembar gabungan dan persetujuan stempel digital 1-Click.
                    </p>
                  </div>
                </div>
              </div>
            </div>

            {{-- Action Buttons --}}
            <div class="col-12 mt-4 pt-3 d-flex justify-content-between align-items-center flex-wrap gap-2 border-top">
              <a href="{{ route('kalibrasi.index') }}" class="btn btn-outline-secondary px-4">
                <i class="ri-close-line me-1"></i> Batal
              </a>
              <button type="submit" class="btn btn-primary px-4 fw-bold shadow-sm d-flex align-items-center gap-2">
                <i class="ri-send-plane-fill"></i>
                <span>Submit Pengajuan &amp; Kirim ke Atasan</span>
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
  // Dynamic card highlight based on selected radio
  function updateConclusionCards() {
    document.querySelectorAll('.conclusion-card').forEach(card => {
      card.classList.remove('active-pass', 'active-note', 'active-fail');
    });

    const selected = document.querySelector('input[name="conclusion"]:checked');
    if (selected) {
      const card = selected.closest('.conclusion-card');
      if (card) {
        if (selected.value === 'PASS') card.classList.add('active-pass');
        else if (selected.value === 'PASS_WITH_NOTE') card.classList.add('active-note');
        else if (selected.value === 'FAIL') card.classList.add('active-fail');
      }
    }
  }

  document.querySelectorAll('input[name="conclusion"]').forEach(radio => {
    radio.addEventListener('change', updateConclusionCards);
  });
  updateConclusionCards();

  // Next Due auto-calculation on calibration date change
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

  // Modern File Upload Drag/Drop Preview
  const fileInput = document.getElementById('attachment_pdf');
  const uploadPrompt = document.getElementById('uploadPrompt');
  const uploadSuccess = document.getElementById('uploadSuccess');
  const displayFileName = document.getElementById('displayFileName');
  const displayFileSize = document.getElementById('displayFileSize');
  const btnResetFile = document.getElementById('btnResetFile');

  if (fileInput) {
    fileInput.addEventListener('change', function() {
      if (this.files && this.files[0]) {
        const file = this.files[0];
        displayFileName.textContent = file.name;
        const sizeKb = (file.size / 1024).toFixed(1);
        displayFileSize.textContent = sizeKb > 1024 ? (sizeKb / 1024).toFixed(2) + ' MB' : sizeKb + ' KB';
        uploadPrompt.classList.add('d-none');
        uploadSuccess.classList.remove('d-none');
        uploadSuccess.classList.add('d-flex');
      }
    });

    btnResetFile?.addEventListener('click', function(e) {
      e.preventDefault();
      fileInput.value = '';
      uploadSuccess.classList.add('d-none');
      uploadSuccess.classList.remove('d-flex');
      uploadPrompt.classList.remove('d-none');
    });
  }
</script>
@endsection
