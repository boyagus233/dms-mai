@extends('layouts/contentNavbarLayout')

@php
  $isEdit = (bool) $sj;
  $items = old('items', $isEdit
      ? $sj->items->map(fn ($i) => ['jenis_barang' => $i->jenis_barang, 'qty' => rtrim(rtrim((string) $i->qty, '0'), '.'), 'satuan' => $i->satuan, 'keterangan' => $i->keterangan])->all()
      : [['jenis_barang' => '', 'qty' => '', 'satuan' => 'PCS', 'keterangan' => '']]);
  $kategoriSel = old('kategori', $isEdit ? (array) $sj->kategori : []);
  $signerSel = [
      2 => old('signer_2', $isEdit ? $sj->signerForStep(2)?->user_id : null),
      3 => old('signer_3', $isEdit ? $sj->signerForStep(3)?->user_id : null),
      4 => old('signer_4', $isEdit ? $sj->signerForStep(4)?->user_id : null),
  ];
  $optLabel = fn ($u) => $u['name'] . ($u['position'] ? ' — ' . $u['position'] : '') . ($u['dept'] ? ' (' . $u['dept'] . ')' : '') . ' · ' . $u['nik'];
@endphp

@section('title', $isEdit ? 'Edit Draft Surat Jalan' : 'Buat Surat Jalan')

@section('styles')
  <link href="/vendor/select2/dist/css/select2.min.css?v=20240303114000" rel="stylesheet">
  <style>
    .sj-section-title { font-size:.8rem; font-weight:700; letter-spacing:.06em; text-transform:uppercase; color:#8a8d93; margin-bottom:.75rem; }
    #items-table td { vertical-align: top; }
    #items-table .line-no { width:48px; text-align:center; padding-top:.65rem; font-weight:600; }
    .kategori-grid { display:grid; grid-template-columns:repeat(auto-fill, minmax(220px, 1fr)); gap:.5rem 1rem; }
    .select2-container { width:100% !important; }
    .signer-card { border:1px dashed #d9dee3; border-radius:.5rem; padding:.75rem; height:100%; }
  </style>
@endsection

@section('content')
<form method="POST" id="sj-form" action="{{ $isEdit ? route('surat-jalan.update', $sj->uuid) : route('surat-jalan.store') }}" novalidate>
  @csrf
  @if ($isEdit) @method('PUT') @endif
  <input type="hidden" name="action" id="form-action" value="draft">

  <div class="card mb-4">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
      <div>
        <h5 class="mb-0">{{ $isEdit ? 'Edit Draft Surat Jalan' : 'Buat Surat Jalan' }}</h5>
        <small class="text-muted">Form barang keluar — nomor & tanggal terbit otomatis saat submit</small>
      </div>
      <a href="{{ $isEdit ? route('surat-jalan.show', $sj->uuid) : route('surat-jalan.index') }}" class="btn btn-sm btn-outline-secondary">Kembali</a>
    </div>

    <div class="card-body">
      @if ($errors->any())
        <div class="alert alert-danger">
          <div class="fw-semibold mb-1">Periksa kembali isian form:</div>
          <ul class="mb-0 ps-3">
            @foreach (collect($errors->all())->unique() as $error)<li>{{ $error }}</li>@endforeach
          </ul>
        </div>
      @endif

      {{-- A. HEADER --}}
      <div class="sj-section-title">A. Header Dokumen</div>
      <div class="row g-3 mb-4">
        <div class="col-md-4">
          <label class="form-label">No. Surat Jalan</label>
          <input type="text" class="form-control" id="sj-no-preview" readonly
                 value="{{ $isEdit && $sj->sj_no ? $sj->sj_no : '' }}" placeholder="Otomatis saat submit">
          <small class="text-muted">Format: SJ/<span id="dept-code-preview">DEPT</span>/{{ now()->format('Y/m') }}/XXXX</small>
        </div>
        <div class="col-md-4">
          <label class="form-label">Lokasi &amp; Tanggal</label>
          <input type="text" class="form-control" readonly value="{{ config('surat_jalan.default_location') }}, {{ now()->format('d-m-Y') }}">
          <small class="text-muted">Tanggal pengajuan diisi otomatis saat submit</small>
        </div>
        <div class="col-md-4">
          <label class="form-label" for="departement_id">Departemen Pemohon <span class="text-danger">*</span></label>
          <select name="departement_id" id="departement_id" class="form-select @error('departement_id') is-invalid @enderror" required>
            <option value="">— Pilih Departemen —</option>
            @foreach ($departements as $d)
              <option value="{{ $d->id }}" data-code="{{ $d->code }}" @selected((string) old('departement_id', $defaultDepartementId) === (string) $d->id)>{{ $d->name }} ({{ $d->code }})</option>
            @endforeach
          </select>
        </div>

        <div class="col-md-3">
          <label class="form-label" for="kepada_tipe">Tipe Tujuan</label>
          <select name="kepada_tipe" id="kepada_tipe" class="form-select">
            <option value="">— Pilih —</option>
            @foreach (config('surat_jalan.kepada_tipe') as $code => $label)
              <option value="{{ $code }}" @selected(old('kepada_tipe', $sj?->kepada_tipe) === $code)>{{ $label }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-5">
          <label class="form-label" for="kepada">Kepada <span class="text-danger">*</span></label>
          <input type="text" name="kepada" id="kepada" list="kepada-list" maxlength="150" required
                 class="form-control @error('kepada') is-invalid @enderror"
                 value="{{ old('kepada', $sj?->kepada) }}" placeholder="Nama supplier / customer / vendor tujuan">
          <datalist id="kepada-list">
            @foreach ($kepadaSuggestions as $k)<option value="{{ $k }}">@endforeach
          </datalist>
        </div>
        <div class="col-md-4">
          <label class="form-label" for="no_kendaraan">No. Kendaraan</label>
          <input type="text" name="no_kendaraan" id="no_kendaraan" maxlength="30" class="form-control text-uppercase"
                 value="{{ old('no_kendaraan', $sj?->no_kendaraan) }}" placeholder="Opsional, mis. T 1234 AB">
          <small class="text-muted">Kosongkan jika dibawa kurir / tangan</small>
        </div>
      </div>

      {{-- B. KATEGORI --}}
      <div class="sj-section-title">B. Kategori / Tujuan Pengeluaran Barang <span class="text-danger">*</span></div>
      <div class="kategori-grid mb-2 @error('kategori') is-invalid @enderror">
        @foreach (config('surat_jalan.kategori') as $code => $label)
          <div class="form-check">
            <input class="form-check-input kategori-check" type="checkbox" name="kategori[]" value="{{ $code }}" id="kat-{{ $code }}"
                   @checked(in_array($code, $kategoriSel, true))>
            <label class="form-check-label" for="kat-{{ $code }}">{{ $label }}</label>
          </div>
        @endforeach
      </div>
      <div class="row mb-4" id="kategori-lain-wrap" style="{{ in_array('LAIN_LAIN', $kategoriSel, true) ? '' : 'display:none' }}">
        <div class="col-md-6">
          <input type="text" name="kategori_lain" id="kategori_lain" maxlength="150"
                 class="form-control @error('kategori_lain') is-invalid @enderror"
                 value="{{ old('kategori_lain', $sj?->kategori_lain) }}" placeholder="Keterangan kategori Lain-lain">
        </div>
      </div>
      <div class="mb-4"></div>

      {{-- C. ITEM BARANG --}}
      <div class="d-flex justify-content-between align-items-center">
        <div class="sj-section-title mb-0">C. Rincian Barang</div>
        <button type="button" class="btn btn-sm btn-outline-primary" id="add-row"><i class="ri-add-line me-1"></i>Tambah Baris</button>
      </div>
      <div class="table-responsive mt-2 mb-4">
        <table class="table table-bordered table-sm mb-0" id="items-table">
          <thead class="table-light">
            <tr>
              <th class="text-center" style="width:48px">NO</th>
              <th style="min-width:220px">JENIS BARANG <span class="text-danger">*</span></th>
              <th style="width:120px">QTY <span class="text-danger">*</span></th>
              <th style="width:120px">SATUAN</th>
              <th style="min-width:200px">KETERANGAN</th>
              <th style="width:48px"></th>
            </tr>
          </thead>
          <tbody id="items-body">
            @foreach ($items as $i => $item)
              <tr class="item-row">
                <td class="line-no">{{ $loop->iteration }}</td>
                <td><input type="text" class="form-control form-control-sm @error("items.$i.jenis_barang") is-invalid @enderror" data-field="jenis_barang" list="barang-list" maxlength="200" required value="{{ $item['jenis_barang'] ?? '' }}"></td>
                <td><input type="number" class="form-control form-control-sm text-end @error("items.$i.qty") is-invalid @enderror" data-field="qty" min="0.01" step="any" required value="{{ $item['qty'] ?? '' }}"></td>
                <td><input type="text" class="form-control form-control-sm text-uppercase" data-field="satuan" list="satuan-list" maxlength="20" value="{{ $item['satuan'] ?? '' }}"></td>
                <td><input type="text" class="form-control form-control-sm" data-field="keterangan" maxlength="255" value="{{ $item['keterangan'] ?? '' }}" placeholder="Kondisi / spesifikasi"></td>
                <td class="text-center"><button type="button" class="btn btn-sm btn-icon btn-text-danger remove-row" title="Hapus baris"><i class="ri-delete-bin-line"></i></button></td>
              </tr>
            @endforeach
          </tbody>
        </table>
        <datalist id="barang-list">@foreach ($jenisBarangSuggestions as $b)<option value="{{ $b }}">@endforeach</datalist>
        <datalist id="satuan-list">@foreach (config('surat_jalan.satuan') as $s)<option value="{{ $s }}">@endforeach</datalist>
      </div>

      {{-- D. SIGNER --}}
      <div class="sj-section-title">D. Penanda Tangan (urutan approval)</div>
      <div class="row g-3">
        <div class="col-md-6 col-xl-3">
          <div class="signer-card">
            <div class="fw-semibold">1. Diserahkan</div>
            <small class="text-muted d-block mb-2">Pemohon / PIC — otomatis ter-sign saat submit</small>
            <div class="fw-semibold">{{ $creatorProfile['name'] }}</div>
            <small class="d-block text-muted">{{ $creatorProfile['position'] ?? '-' }}{{ $creatorProfile['departement'] ? ' · ' . $creatorProfile['departement'] : '' }}</small>
            @if ($creatorHasStamp)
              <span class="badge bg-label-success mt-2">Image stamp tersedia</span>
            @else
              <span class="badge bg-label-warning mt-2" title="Belum ada gambar tanda tangan di profil Employee">Akan memakai QR stamp</span>
            @endif
          </div>
        </div>
        @foreach ([2 => ['Mengetahui', 'Dept. Head', $userOptions], 3 => ['Pemeriksa', 'Personal HRD / GA', $pemeriksaOptions], 4 => ['Diterima', 'Penerima / Security Gate', $userOptions]] as $step => [$label, $hint, $options])
          <div class="col-md-6 col-xl-3">
            <div class="signer-card">
              <label class="fw-semibold d-block" for="signer_{{ $step }}">{{ $step }}. {{ $label }} <span class="text-danger">*</span></label>
              <small class="text-muted d-block mb-2">{{ $hint }}</small>
              <select name="signer_{{ $step }}" id="signer_{{ $step }}" class="form-select signer-select @error('signer_' . $step) is-invalid @enderror" required>
                <option value="">— Pilih {{ $label }} —</option>
                @foreach ($options as $u)
                  <option value="{{ $u['id'] }}" @selected((string) $signerSel[$step] === (string) $u['id'])>{{ $optLabel($u) }}</option>
                @endforeach
              </select>
            </div>
          </div>
        @endforeach
      </div>
    </div>

    <div class="card-footer d-flex flex-wrap justify-content-end gap-2 border-top pt-4">
      <button type="submit" class="btn btn-outline-secondary" data-action="draft"><i class="ri-save-line me-1"></i>Simpan Draft</button>
      <button type="submit" class="btn btn-primary" data-action="submit"><i class="ri-send-plane-line me-1"></i>Submit &amp; Sign</button>
    </div>
  </div>
</form>

<template id="row-template">
  <tr class="item-row">
    <td class="line-no"></td>
    <td><input type="text" class="form-control form-control-sm" data-field="jenis_barang" list="barang-list" maxlength="200" required></td>
    <td><input type="number" class="form-control form-control-sm text-end" data-field="qty" min="0.01" step="any" required></td>
    <td><input type="text" class="form-control form-control-sm text-uppercase" data-field="satuan" list="satuan-list" maxlength="20" value="PCS"></td>
    <td><input type="text" class="form-control form-control-sm" data-field="keterangan" maxlength="255" placeholder="Kondisi / spesifikasi"></td>
    <td class="text-center"><button type="button" class="btn btn-sm btn-icon btn-text-danger remove-row" title="Hapus baris"><i class="ri-delete-bin-line"></i></button></td>
  </tr>
</template>
@endsection

@section('scripts')
<script type="module" src="/vendor/select2/dist/js/select2.full.min.js?v=20240303114000"></script>
<script type="module">
  $('.signer-select').select2({ placeholder: '— Pilih —', allowClear: true, width: '100%' });
</script>
<script>
  (function () {
    const body = document.getElementById('items-body');
    const tpl = document.getElementById('row-template');

    // Penomoran ulang NO + nama input items[i][field] setiap ada perubahan baris
    function reindex() {
      body.querySelectorAll('tr.item-row').forEach((tr, i) => {
        tr.querySelector('.line-no').textContent = i + 1;
        tr.querySelectorAll('[data-field]').forEach(inp => inp.name = `items[${i}][${inp.dataset.field}]`);
      });
      const rows = body.querySelectorAll('tr.item-row').length;
      body.querySelectorAll('.remove-row').forEach(b => b.disabled = rows <= 1);
    }

    document.getElementById('add-row').addEventListener('click', () => {
      body.appendChild(tpl.content.cloneNode(true));
      reindex();
      body.lastElementChild.querySelector('[data-field="jenis_barang"]').focus();
    });

    body.addEventListener('click', (e) => {
      const btn = e.target.closest('.remove-row');
      if (!btn) return;
      btn.closest('tr').remove();
      reindex();
    });

    // Enter di kolom terakhir → tambah baris baru (input cepat)
    body.addEventListener('keydown', (e) => {
      if (e.key === 'Enter' && e.target.dataset.field === 'keterangan') {
        e.preventDefault();
        const tr = e.target.closest('tr');
        if (tr === body.lastElementChild) document.getElementById('add-row').click();
        else tr.nextElementSibling.querySelector('[data-field="jenis_barang"]').focus();
      }
    });

    // Kategori Lain-lain → tampilkan input keterangan
    const lain = document.getElementById('kat-LAIN_LAIN');
    const lainWrap = document.getElementById('kategori-lain-wrap');
    lain.addEventListener('change', () => {
      lainWrap.style.display = lain.checked ? '' : 'none';
      if (lain.checked) document.getElementById('kategori_lain').focus();
    });

    // Preview kode departemen pada format nomor
    const dept = document.getElementById('departement_id');
    const deptCode = document.getElementById('dept-code-preview');
    const syncDept = () => {
      const opt = dept.options[dept.selectedIndex];
      deptCode.textContent = (opt && opt.dataset.code) ? opt.dataset.code.replace(/[^A-Za-z0-9]/g, '').toUpperCase() : 'DEPT';
    };
    dept.addEventListener('change', syncDept);
    syncDept();

    // Tombol Draft / Submit
    const form = document.getElementById('sj-form');
    let clicked = 'draft';
    form.querySelectorAll('button[type="submit"]').forEach(b => b.addEventListener('click', () => clicked = b.dataset.action));

    form.addEventListener('submit', (e) => {
      document.getElementById('form-action').value = clicked;
      reindex();

      if (!document.querySelector('.kategori-check:checked')) {
        e.preventDefault();
        alert('Pilih minimal satu Kategori / Tujuan Pengeluaran Barang.');
        return;
      }
      if (clicked === 'submit') {
        if (!form.checkValidity()) {
          e.preventDefault();
          form.reportValidity();
          return;
        }
        if (!confirm('Submit Surat Jalan sekarang?\n\nNomor akan diterbitkan, stamp Anda (Diserahkan) otomatis terpasang, dan email notifikasi dikirim ke Dept. Head. Dokumen tidak dapat diubah lagi.')) {
          e.preventDefault();
          return;
        }
      }
      form.querySelectorAll('button[type="submit"]').forEach(b => b.disabled = true);
    });

    reindex();
  })();
</script>
@endsection
