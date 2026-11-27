@php
  $tool = $alatUkur ?? null;
  $isEdit = (bool) $tool;
  $currentCategory = old('category_type', $tool?->category_type ?? 'KI');
  $currentStatus = old('current_status', $tool?->current_status ?? 'ACTIVE');
  $lastDateVal = old('last_calibration_date', $tool?->last_calibration_date?->format('Y-m-d'));
  $nextDateVal = old('next_calibration_date', $tool?->next_calibration_date?->format('Y-m-d'));
  $intervalVal = old('calibration_interval_months', $tool?->calibration_interval_months ?? 12);
@endphp

@if($errors->any())
  <div class="alert alert-danger mb-4">
    <div class="fw-semibold mb-1"><i class="ri-error-warning-line me-1"></i> Periksa kembali isian form:</div>
    <ul class="mb-0 ps-3">
      @foreach($errors->all() as $error)
        <li>{{ $error }}</li>
      @endforeach
    </ul>
  </div>
@endif

<div class="row g-3">
  {{-- 1. No. Alat Ukur & 2. KI/KE --}}
  <div class="col-md-4">
    <label class="form-label fw-semibold" for="asset_code">No. Alat Ukur <span class="text-danger">*</span></label>
    <input type="text"
           class="form-control font-monospace @error('asset_code') is-invalid @enderror"
           id="asset_code"
           name="asset_code"
           value="{{ old('asset_code', $tool?->asset_code) }}"
           placeholder="Contoh: C-1-001-00"
           required
           autofocus />
    @error('asset_code')
      <div class="invalid-feedback">{{ $message }}</div>
    @enderror
  </div>

  <div class="col-md-4">
    <label class="form-label fw-semibold" for="category_type">KI / KE (Jenis Kalibrasi) <span class="text-danger">*</span></label>
    <select name="category_type" id="category_type" class="form-select @error('category_type') is-invalid @enderror" required>
      <option value="KI" @selected($currentCategory === 'KI')>KI &mdash; Kalibrasi Internal</option>
      <option value="KE" @selected($currentCategory === 'KE')>KE &mdash; Kalibrasi Eksternal</option>
    </select>
    @error('category_type')
      <div class="invalid-feedback">{{ $message }}</div>
    @enderror
  </div>

  <div class="col-md-4">
    <label class="form-label fw-semibold" for="current_status">Status Alat <span class="text-danger">*</span></label>
    <select name="current_status" id="current_status" class="form-select @error('current_status') is-invalid @enderror" required>
      @foreach($statuses as $code => $meta)
        <option value="{{ $code }}" @selected($currentStatus === $code)>{{ $meta['label'] }}</option>
      @endforeach
    </select>
    @error('current_status')
      <div class="invalid-feedback">{{ $message }}</div>
    @enderror
  </div>

  {{-- 3. Alat Ukur & 4. Standard --}}
  <div class="col-md-7">
    <label class="form-label fw-semibold" for="name">Alat Ukur (Nama Alat) <span class="text-danger">*</span></label>
    <input type="text"
           class="form-control @error('name') is-invalid @enderror"
           id="name"
           name="name"
           value="{{ old('name', $tool?->name) }}"
           placeholder="Contoh: DIGIMATIC CALIPER"
           required />
    @error('name')
      <div class="invalid-feedback">{{ $message }}</div>
    @enderror
  </div>

  <div class="col-md-5">
    <label class="form-label fw-semibold" for="standard_spec">Standard / Rentang Ukur</label>
    <input type="text"
           class="form-control @error('standard_spec') is-invalid @enderror"
           id="standard_spec"
           name="standard_spec"
           value="{{ old('standard_spec', $tool?->standard_spec) }}"
           placeholder="Contoh: 300/0.01 atau 0-150 mm" />
    @error('standard_spec')
      <div class="invalid-feedback">{{ $message }}</div>
    @enderror
  </div>

  {{-- 5. Model, 6. No.Serial, 7. Kalibrator, 8. Area --}}
  <div class="col-md-4">
    <label class="form-label fw-semibold" for="brand_model">Model / Merk</label>
    <input type="text"
           class="form-control @error('brand_model') is-invalid @enderror"
           id="brand_model"
           name="brand_model"
           value="{{ old('brand_model', $tool?->brand_model) }}"
           placeholder="Contoh: CD-30C / Mitutoyo" />
    @error('brand_model')
      <div class="invalid-feedback">{{ $message }}</div>
    @enderror
  </div>

  <div class="col-md-4">
    <label class="form-label fw-semibold" for="serial_number">No. Serial</label>
    <input type="text"
           class="form-control @error('serial_number') is-invalid @enderror"
           id="serial_number"
           name="serial_number"
           value="{{ old('serial_number', $tool?->serial_number) }}"
           placeholder="Contoh: 1191500 / B18104981" />
    @error('serial_number')
      <div class="invalid-feedback">{{ $message }}</div>
    @enderror
  </div>

  <div class="col-md-4">
    <label class="form-label fw-semibold" for="location">Area / Lokasi</label>
    <input type="text"
           class="form-control @error('location') is-invalid @enderror"
           id="location"
           name="location"
           list="area-datalist"
           value="{{ old('location', $tool?->location) }}"
           placeholder="Contoh: QA, 4500 PR, DIES SHOP" />
    <datalist id="area-datalist">
      @foreach($areas as $area)
        <option value="{{ $area }}">
      @endforeach
    </datalist>
    @error('location')
      <div class="invalid-feedback">{{ $message }}</div>
    @enderror
  </div>

  <div class="col-md-6">
    <label class="form-label fw-semibold" for="calibrator_ref">Kalibrator Acuan</label>
    <input type="text"
           class="form-control @error('calibrator_ref') is-invalid @enderror"
           id="calibrator_ref"
           name="calibrator_ref"
           list="calibrator-datalist"
           value="{{ old('calibrator_ref', $tool?->calibrator_ref) }}"
           placeholder="Contoh: Caliper Checker / Ceramic Block Gauge" />
    <datalist id="calibrator-datalist">
      @foreach($calibrators as $cal)
        <option value="{{ $cal }}">
      @endforeach
    </datalist>
    @error('calibrator_ref')
      <div class="invalid-feedback">{{ $message }}</div>
    @enderror
  </div>

  <div class="col-md-2">
    <label class="form-label fw-semibold" for="calibration_interval_months">Interval (Bln)</label>
    <input type="number"
           class="form-control @error('calibration_interval_months') is-invalid @enderror"
           id="calibration_interval_months"
           name="calibration_interval_months"
           min="1"
           max="120"
           value="{{ $intervalVal }}" />
    @error('calibration_interval_months')
      <div class="invalid-feedback">{{ $message }}</div>
    @enderror
  </div>

  {{-- 9. Last Kalibrasi & 10. Next Kalibrasi --}}
  <div class="col-md-2">
    <label class="form-label fw-semibold" for="last_calibration_date">Last Kalibrasi</label>
    <input type="date"
           class="form-control @error('last_calibration_date') is-invalid @enderror"
           id="last_calibration_date"
           name="last_calibration_date"
           value="{{ $lastDateVal }}" />
    @error('last_calibration_date')
      <div class="invalid-feedback">{{ $message }}</div>
    @enderror
  </div>

  <div class="col-md-2">
    <label class="form-label fw-semibold" for="next_calibration_date">Next Kalibrasi</label>
    <input type="date"
           class="form-control @error('next_calibration_date') is-invalid @enderror"
           id="next_calibration_date"
           name="next_calibration_date"
           value="{{ $nextDateVal }}" />
    @error('next_calibration_date')
      <div class="invalid-feedback">{{ $message }}</div>
    @enderror
  </div>

  {{-- 12. Picture --}}
  <div class="col-md-6">
    <label class="form-label fw-semibold" for="picture">Picture (Foto Alat Ukur)</label>
    <input type="file"
           class="form-control @error('picture') is-invalid @enderror"
           id="picture"
           name="picture"
           accept="image/png,image/jpeg,image/webp" />
    <small class="text-muted d-block mt-1">Format JPG, PNG, atau WebP (Maks. 5 MB).</small>
    @error('picture')
      <div class="invalid-feedback">{{ $message }}</div>
    @enderror

    <div class="mt-2 d-flex align-items-center gap-3 flex-wrap">
      @if($isEdit && $tool->photo_url)
        <div id="existing-photo-wrap" class="border rounded p-2 bg-light d-flex align-items-center gap-2">
          <img src="{{ $tool->photo_url }}" alt="{{ $tool->name }}" class="rounded" style="height: 64px; width: 64px; object-fit: cover;">
          <div>
            <div class="small fw-semibold">Foto Saat Ini</div>
            <div class="form-check mt-1">
              <input class="form-check-input" type="checkbox" id="remove_picture" name="remove_picture" value="1">
              <label class="form-check-label small text-danger" for="remove_picture">Hapus foto</label>
            </div>
          </div>
        </div>
      @endif

      <div id="new-photo-preview-wrap" class="border rounded p-2 bg-light d-none align-items-center gap-2">
        <img id="new-photo-preview" src="" alt="Preview" class="rounded" style="height: 64px; width: 64px; object-fit: cover;">
        <small class="text-success fw-semibold">Foto baru siap diunggah</small>
      </div>
    </div>
  </div>

  {{-- 13. Note --}}
  <div class="col-md-6">
    <label class="form-label fw-semibold" for="notes">Note / Catatan</label>
    <textarea class="form-control @error('notes') is-invalid @enderror"
              id="notes"
              name="notes"
              rows="4"
              placeholder="Catatan tambahan kondisi atau riwayat alat...">{{ old('notes', $tool?->notes) }}</textarea>
    @error('notes')
      <div class="invalid-feedback">{{ $message }}</div>
    @enderror
  </div>
</div>

@section('page-script')
<script>
  (function () {
    const lastInput = document.getElementById('last_calibration_date');
    const nextInput = document.getElementById('next_calibration_date');
    const intervalInput = document.getElementById('calibration_interval_months');
    let nextTouched = Boolean(nextInput && nextInput.value);

    if (nextInput) {
      nextInput.addEventListener('input', function () {
        nextTouched = this.value !== '';
      });
    }

    function autoCalculateNextDate() {
      if (!lastInput || !nextInput || !lastInput.value || nextTouched) return;
      const months = parseInt(intervalInput?.value || '12', 10) || 12;
      const parts = lastInput.value.split('-').map(Number);
      if (parts.length !== 3) return;
      const dt = new Date(parts[0], parts[1] - 1, parts[2]);
      dt.setMonth(dt.getMonth() + months);
      const y = dt.getFullYear();
      const m = String(dt.getMonth() + 1).padStart(2, '0');
      const d = String(dt.getDate()).padStart(2, '0');
      nextInput.value = `${y}-${m}-${d}`;
    }

    lastInput?.addEventListener('change', autoCalculateNextDate);
    intervalInput?.addEventListener('change', autoCalculateNextDate);

    const picInput = document.getElementById('picture');
    const previewWrap = document.getElementById('new-photo-preview-wrap');
    const previewImg = document.getElementById('new-photo-preview');
    picInput?.addEventListener('change', function () {
      if (this.files && this.files[0]) {
        const reader = new FileReader();
        reader.onload = function (e) {
          previewImg.src = e.target.result;
          previewWrap.classList.remove('d-none');
          previewWrap.classList.add('d-flex');
        };
        reader.readAsDataURL(this.files[0]);
      } else {
        previewWrap.classList.add('d-none');
        previewWrap.classList.remove('d-flex');
      }
    });
  })();
</script>
@endsection
