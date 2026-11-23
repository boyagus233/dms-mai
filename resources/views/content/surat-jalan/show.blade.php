@extends('layouts/contentNavbarLayout')

@section('title', 'Surat Jalan ' . ($sj->sj_no ?? 'Draft'))

@section('styles')
<style>
  .sj-timeline { list-style:none; padding:0; margin:0; }
  .sj-timeline li { display:flex; gap:.75rem; padding:.6rem 0; border-bottom:1px solid #eef0f2; }
  .sj-timeline li:last-child { border-bottom:0; }
  .sj-timeline .dot { width:30px; height:30px; flex:0 0 30px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-weight:700; font-size:.8rem; }
  .sj-log { max-height:520px; overflow-y:auto; }
  .sj-log .item { border-left:2px solid #e4e6e8; padding:0 0 .8rem .8rem; position:relative; }
  .sj-log .item::before { content:''; position:absolute; left:-6px; top:4px; width:10px; height:10px; border-radius:50%; background:var(--c, #8a8d93); }
  .sig-pad-wrap { position:relative; }
  #sig-pad { display:block; width:100%; height:200px; border:2px dashed #b4b8bd; border-radius:.5rem; background:#fff; touch-action:none; cursor:crosshair; }
  .sig-pad-hint { position:absolute; left:0; right:0; bottom:14px; text-align:center; font-size:.8rem; color:#a1a4a8; pointer-events:none; }
  .sig-pad-wrap.has-ink .sig-pad-hint { display:none; }
  .my-stamp { max-height:90px; max-width:100%; object-fit:contain; background:#fff; border:1px solid #eef0f2; border-radius:.375rem; padding:.25rem; }
</style>
@endsection

@section('content')
@php
  use App\Services\DocumentAuditLogger;
  use App\Services\SuratJalanPdfService;
  $dotClass = ['SIGNED' => 'bg-success text-white', 'PENDING' => 'bg-warning text-white', 'REJECTED' => 'bg-danger text-white', 'WAITING' => 'bg-label-secondary'];
  $logColor = [
    'DOCUMENT_CREATED' => '#696cff', 'SUBMITTED_SIGNED' => '#16a34a', 'APPROVED_SIGNED' => '#16a34a', 'COMPLETED' => '#15803d',
    'REJECTED' => '#dc2626', 'NOTIFICATION_SENT' => '#0ea5e9', 'NOTIFICATION_FAILED' => '#f97316', 'NOTIFICATION_SKIPPED' => '#f59e0b',
    'DOCUMENT_VIEWED' => '#8a8d93',
  ];
@endphp

@if ($errors->any())
  <div class="alert alert-danger">@foreach (collect($errors->all())->unique() as $e)<div>{{ $e }}</div>@endforeach</div>
@endif

{{-- Header --}}
<div class="card mb-4">
  <div class="card-body d-flex flex-wrap justify-content-between align-items-start gap-3">
    <div>
      <div class="d-flex align-items-center gap-2 flex-wrap">
        <h4 class="mb-0">{{ $sj->sj_no ?? 'Draft Surat Jalan' }}</h4>
        <span class="badge {{ $sj->statusBadgeClass() }}">{{ $sj->statusLabel() }}</span>
      </div>
      <small class="text-muted">
        Dibuat oleh {{ $sj->creator?->name }} · {{ $sj->created_at->format('d-m-Y H:i') }}
        @if ($sj->submitted_at) · Submit {{ $sj->submitted_at->format('d-m-Y H:i') }} @endif
        @if ($sj->completed_at) · Selesai {{ $sj->completed_at->format('d-m-Y H:i') }} @endif
      </small>
    </div>
    <div class="d-flex flex-wrap gap-2">
      <a href="{{ route('surat-jalan.index') }}" class="btn btn-sm btn-outline-secondary">Kembali</a>
      @if ($canEdit)
        <a href="{{ route('surat-jalan.edit', $sj->uuid) }}" class="btn btn-sm btn-outline-primary"><i class="ri-pencil-line me-1"></i>Edit Draft</a>
        <form method="POST" action="{{ route('surat-jalan.destroy', $sj->uuid) }}" onsubmit="return confirm('Hapus draft ini?')">
          @csrf @method('DELETE')
          <button class="btn btn-sm btn-outline-danger"><i class="ri-delete-bin-line me-1"></i>Hapus</button>
        </form>
      @endif
      <a href="{{ route('surat-jalan.pdf', $sj->uuid) }}" target="_blank" class="btn btn-sm btn-outline-primary"><i class="ri-eye-line me-1"></i>Preview PDF</a>
      <a href="{{ route('surat-jalan.pdf', ['suratJalan' => $sj->uuid, 'download' => 1]) }}" class="btn btn-sm {{ $sj->status === 'COMPLETED' ? 'btn-success' : 'btn-outline-success' }}">
        <i class="ri-download-2-line me-1"></i>Download PDF Surat Jalan
      </a>
      <a href="{{ route('surat-jalan.audit-history', $sj->uuid) }}" target="_blank" class="btn btn-sm btn-outline-dark"><i class="ri-history-line me-1"></i>Cetak Audit History</a>
    </div>
  </div>
</div>

@if ($sj->status === 'REJECTED')
  <div class="alert alert-danger">
    <div class="fw-semibold"><i class="ri-close-circle-line me-1"></i>Ditolak oleh {{ $sj->rejecter?->name }} pada {{ $sj->rejected_at?->format('d-m-Y H:i:s') }}</div>
    <div>Alasan: {{ $sj->rejected_reason }}</div>
  </div>
@endif

{{-- Status giliran tanda tangan (aksi tanda tangan ada di tombol pada kotak lembar Surat Jalan) --}}
@if ($signableStep)
  @php $mySlot = config('surat_jalan.slots.' . $signableStep . '.label'); @endphp
  <div class="alert alert-primary d-flex flex-wrap align-items-center justify-content-between gap-2">
    <div>
      <i class="ri-quill-pen-line me-1"></i>
      @if ($signableStep === 1)
        Draft siap dikirim. Tanda tangani kotak <b>{{ $mySlot }}</b> untuk submit Surat Jalan ke Dept. Head.
      @else
        Giliran Anda menandatangani kotak <b>{{ $mySlot }}</b>. Periksa isi Surat Jalan, lalu klik tombol di kotak tersebut.
      @endif
    </div>
    <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#signModal">
      Tanda tangan sekarang
    </button>
  </div>
@elseif ($currentSigner && $sj->status === 'IN_PROGRESS')
  <div class="alert alert-warning">
    <i class="ri-time-line me-1"></i>Menunggu tanda tangan <b>{{ $currentSigner->signer_name }}</b> ({{ $currentSigner->slotLabel() }}).
  </div>
@endif

@if ($signableStep)
  @php
    $isSubmit = $signableStep === 1;
    $signErrorKeys = ['sign_mode', 'signature_pad', 'save_to_profile', 'confirm', 'surat_jalan'];
  @endphp
  <div class="modal fade" id="signModal" tabindex="-1" aria-labelledby="signModalTitle" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <div>
            <h5 class="modal-title" id="signModalTitle">Tanda Tangan &mdash; {{ $mySlot }}</h5>
            <small class="text-muted">{{ $myProfile['name'] }}{{ $myProfile['position'] ? ' · ' . $myProfile['position'] : '' }}</small>
          </div>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
        </div>

        {{-- Pane 1: tanda tangan --}}
        <form method="POST" id="sign-form" class="sign-pane" data-pane="sign"
              action="{{ $isSubmit ? route('surat-jalan.submit', $sj->uuid) : route('surat-jalan.approve', $sj->uuid) }}">
          @csrf
          <input type="hidden" name="signature_pad" id="signature_pad_input">
          <input type="hidden" name="sign_mode" id="sign_mode_input" value="{{ $myStampUrl ? 'profile' : 'pad' }}">

          <div class="modal-body pt-2">
            @if ($errors->hasAny($signErrorKeys))
              <div class="alert alert-danger py-2 small mb-3">
                @foreach ($signErrorKeys as $k)
                  @foreach ($errors->get($k) as $msg)<div>{{ $msg }}</div>@endforeach
                @endforeach
              </div>
            @endif

            <ul class="nav nav-tabs nav-fill mb-3" role="tablist">
              <li class="nav-item" role="presentation">
                <button type="button" class="nav-link {{ $myStampUrl ? '' : 'active' }}" data-bs-toggle="tab" data-bs-target="#sign-tab-pad" data-mode="pad" role="tab">
                  <i class="ri-pencil-line me-1"></i>Gambar di layar
                </button>
              </li>
              <li class="nav-item" role="presentation">
                <button type="button" class="nav-link {{ $myStampUrl ? 'active' : '' }}" data-bs-toggle="tab" data-bs-target="#sign-tab-profile" data-mode="profile" role="tab">
                  <i class="ri-user-line me-1"></i>Stamp profil
                </button>
              </li>
            </ul>

            <div class="tab-content p-0">
              <div class="tab-pane fade {{ $myStampUrl ? '' : 'show active' }}" id="sign-tab-pad" role="tabpanel">
                <div class="sig-pad-wrap">
                  <canvas id="sig-pad" aria-label="Area tanda tangan"></canvas>
                  <span class="sig-pad-hint" id="sig-pad-hint">Gores tanda tangan Anda di sini</span>
                </div>
                <div class="d-flex justify-content-between align-items-center mt-2">
                  <div class="form-check mb-0">
                    <input class="form-check-input" type="checkbox" name="save_to_profile" value="1" id="save_to_profile">
                    <label class="form-check-label small" for="save_to_profile">Simpan sebagai tanda tangan profil saya</label>
                  </div>
                  <button type="button" class="btn btn-sm btn-text-secondary" id="pad-clear"><i class="ri-eraser-line me-1"></i>Ulangi</button>
                </div>
              </div>

              <div class="tab-pane fade {{ $myStampUrl ? 'show active' : '' }}" id="sign-tab-profile" role="tabpanel">
                <div class="text-center border rounded p-3">
                  @if ($myStampUrl)
                    <img src="{{ $myStampUrl }}" class="my-stamp" alt="Tanda tangan profil {{ $myProfile['name'] }}">
                    <div class="small text-success mt-2"><i class="ri-checkbox-circle-line me-1"></i>Tanda tangan dari profil Anda akan dipasang di kotak {{ $mySlot }}.</div>
                  @else
                    <i class="ri-qr-code-line fs-1 text-muted"></i>
                    <div class="small text-muted mt-1">Profil Anda belum punya gambar tanda tangan. Kotak akan diisi <b>QR stamp</b> (Nama, Jabatan &amp; waktu tanda tangan).</div>
                  @endif
                </div>
              </div>
            </div>

            <div class="form-check mt-3">
              <input class="form-check-input" type="checkbox" name="confirm" value="1" id="sign-confirm" required>
              <label class="form-check-label small" for="sign-confirm">
                Saya telah memeriksa isi Surat Jalan ini dan menyetujuinya sebagai <b>{{ $mySlot }}</b>.
              </label>
            </div>
            @if ($isSubmit)
              <small class="text-muted d-block mt-2">Setelah ditandatangani, nomor Surat Jalan terbit, dokumen tidak dapat diubah lagi, dan permintaan tanda tangan dikirim ke Dept. Head.</small>
            @endif
          </div>

          <div class="modal-footer">
            @unless ($isSubmit)
              <button type="button" class="btn btn-outline-danger me-auto" data-show-pane="reject"><i class="ri-close-line me-1"></i>Tolak</button>
            @endunless
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
            <button type="submit" class="btn btn-primary" id="sign-submit"><i class="ri-quill-pen-line me-1"></i>{{ $isSubmit ? 'Tanda Tangan & Submit' : 'Tanda Tangan & Setujui' }}</button>
          </div>
        </form>

        {{-- Pane 2: tolak (hanya untuk signer approval) --}}
        @unless ($isSubmit)
          <form method="POST" action="{{ route('surat-jalan.reject', $sj->uuid) }}" class="sign-pane d-none" data-pane="reject">
            @csrf
            <div class="modal-body pt-2">
              <p class="text-muted small mb-2">Proses approval akan <b>dihentikan</b> dan status berubah menjadi REJECTED. Pemohon akan menerima pemberitahuan.</p>
              <label class="form-label" for="reason">Alasan penolakan <span class="text-danger">*</span></label>
              <textarea name="reason" id="reason" rows="4" class="form-control @error('reason') is-invalid @enderror" required minlength="5" maxlength="1000">{{ old('reason') }}</textarea>
              @error('reason')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-outline-secondary me-auto" data-show-pane="sign"><i class="ri-arrow-left-line me-1"></i>Kembali</button>
              <button type="submit" class="btn btn-danger">Tolak Surat Jalan</button>
            </div>
          </form>
        @endunless
      </div>
    </div>
  </div>
@endif

<div class="row g-4">
  <div class="col-xl-8">
    @include('content.surat-jalan._sheet', ['sj' => $sj, 'qrSvgs' => $qrSvgs, 'signableStep' => $signableStep])
  </div>

  <div class="col-xl-4">
    <div class="card mb-4">
      <div class="card-header pb-2"><h6 class="mb-0">Alur Tanda Tangan</h6></div>
      <div class="card-body pt-0">
        <ul class="sj-timeline">
          @foreach ($sj->signers as $s)
            <li>
              <span class="dot {{ $dotClass[$s->status] ?? 'bg-label-secondary' }}">
                @if ($s->status === 'SIGNED')<i class="ri-check-line"></i>@elseif ($s->status === 'REJECTED')<i class="ri-close-line"></i>@else{{ $s->step }}@endif
              </span>
              <div class="min-w-0">
                <div class="fw-semibold">{{ $s->slotLabel() }} <small class="text-muted fw-normal">· {{ $s->status }}</small></div>
                <div>{{ $s->signer_name }}</div>
                <small class="text-muted d-block">{{ $s->signer_position ?? '-' }}{{ $s->signer_departement ? ' · ' . $s->signer_departement : '' }}</small>
                @if ($s->signed_at)
                  <small class="text-muted d-block">{{ $s->signed_at->format('d-m-Y H:i:s') }} · {{ $s->ip_address }} · {{ SuratJalanPdfService::describeUserAgent($s->user_agent) }}</small>
                @endif
                @if ($s->note)<small class="text-danger d-block">"{{ $s->note }}"</small>@endif
              </div>
            </li>
          @endforeach
        </ul>
      </div>
    </div>

    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center pb-2">
        <h6 class="mb-0">Audit Trail</h6>
        <a href="{{ route('surat-jalan.audit-history', $sj->uuid) }}" target="_blank" class="small">Cetak PDF</a>
      </div>
      <div class="card-body pt-2 sj-log">
        @forelse ($logs as $log)
          <div class="item" style="--c: {{ $logColor[$log->action] ?? '#8a8d93' }}">
            <div class="fw-semibold small">{{ DocumentAuditLogger::label($log->action) }}</div>
            <div class="small">{{ $log->description }}</div>
            <small class="text-muted">{{ $log->created_at->format('d-m-Y H:i:s') }} · {{ $log->actor_name ?? 'System' }}{{ $log->ip_address ? ' · ' . $log->ip_address : '' }}</small>
          </div>
        @empty
          <small class="text-muted">Belum ada aktivitas.</small>
        @endforelse
      </div>
    </div>
  </div>
</div>
@endsection

@section('scripts')
@if ($signableStep)
<script>
  (function () {
    const modalEl = document.getElementById('signModal');
    const form = document.getElementById('sign-form');
    const canvas = document.getElementById('sig-pad');
    const wrap = canvas.closest('.sig-pad-wrap');
    const padInput = document.getElementById('signature_pad_input');
    const modeInput = document.getElementById('sign_mode_input');
    const ctx = canvas.getContext('2d');
    let drawn = false, drawing = false, last = null;

    /* ---------- Signature pad ---------- */
    function resetPad() {
      const ratio = Math.max(window.devicePixelRatio || 1, 1);
      const w = canvas.offsetWidth, h = canvas.offsetHeight;
      if (w < 10 || h < 10) return;              // masih tersembunyi
      canvas.width = w * ratio;
      canvas.height = h * ratio;
      ctx.setTransform(ratio, 0, 0, ratio, 0, 0);
      ctx.lineWidth = 2.4; ctx.lineCap = 'round'; ctx.lineJoin = 'round'; ctx.strokeStyle = '#0b2a6f'; ctx.fillStyle = '#0b2a6f';
      drawn = false;
      wrap.classList.remove('has-ink');
    }

    const pos = (e) => { const r = canvas.getBoundingClientRect(); return { x: e.clientX - r.left, y: e.clientY - r.top }; };
    canvas.addEventListener('pointerdown', (e) => {
      e.preventDefault();
      drawing = true; last = pos(e);
      canvas.setPointerCapture(e.pointerId);
      ctx.beginPath(); ctx.arc(last.x, last.y, 1.2, 0, Math.PI * 2); ctx.fill();
      drawn = true; wrap.classList.add('has-ink');
    });
    canvas.addEventListener('pointermove', (e) => {
      if (!drawing) return;
      const p = pos(e);
      ctx.beginPath(); ctx.moveTo(last.x, last.y); ctx.lineTo(p.x, p.y); ctx.stroke();
      last = p;
    });
    ['pointerup', 'pointercancel'].forEach(ev => canvas.addEventListener(ev, () => { drawing = false; }));
    document.getElementById('pad-clear').addEventListener('click', resetPad);

    /** Potong kanvas ke area coretan (+ margin) agar stamp terisi penuh di kotak tanda tangan. */
    function trimmedDataUrl() {
      const { width, height } = canvas;
      const data = ctx.getImageData(0, 0, width, height).data;
      let minX = width, minY = height, maxX = -1, maxY = -1;
      for (let y = 0; y < height; y++) {
        for (let x = 0; x < width; x++) {
          if (data[(y * width + x) * 4 + 3] > 0) {
            if (x < minX) minX = x; if (x > maxX) maxX = x;
            if (y < minY) minY = y; if (y > maxY) maxY = y;
          }
        }
      }
      if (maxX < 0) return null;
      const pad = 12;
      minX = Math.max(0, minX - pad); minY = Math.max(0, minY - pad);
      maxX = Math.min(width - 1, maxX + pad); maxY = Math.min(height - 1, maxY + pad);
      const out = document.createElement('canvas');
      out.width = maxX - minX + 1; out.height = maxY - minY + 1;
      out.getContext('2d').drawImage(canvas, minX, minY, out.width, out.height, 0, 0, out.width, out.height);
      return out.toDataURL('image/png');
    }

    /* ---------- Tab mode: gambar / stamp profil ---------- */
    modalEl.querySelectorAll('[data-mode]').forEach(tab => {
      tab.addEventListener('shown.bs.tab', () => {
        modeInput.value = tab.dataset.mode;
        if (tab.dataset.mode === 'pad' && !drawn) resetPad();
      });
    });

    /* ---------- Pane tanda tangan / tolak ---------- */
    function showPane(name) {
      modalEl.querySelectorAll('.sign-pane').forEach(p => p.classList.toggle('d-none', p.dataset.pane !== name));
      if (name === 'reject') document.getElementById('reason')?.focus();
    }
    modalEl.querySelectorAll('[data-show-pane]').forEach(b => b.addEventListener('click', () => showPane(b.dataset.showPane)));

    modalEl.addEventListener('shown.bs.modal', () => { if (modeInput.value === 'pad' && !drawn) resetPad(); });
    modalEl.addEventListener('hidden.bs.modal', () => showPane('sign'));

    /* ---------- Submit tanda tangan ---------- */
    form.addEventListener('submit', (e) => {
      if (modeInput.value === 'pad') {
        const url = drawn ? trimmedDataUrl() : null;
        if (!url) { e.preventDefault(); alert('Silakan gambar tanda tangan Anda di area yang tersedia.'); return; }
        padInput.value = url;
      } else {
        padInput.value = '';
      }
      if (!document.getElementById('sign-confirm').checked) {
        e.preventDefault(); alert('Centang konfirmasi bahwa Anda telah memeriksa isi Surat Jalan.'); return;
      }
      document.getElementById('sign-submit').disabled = true;
    });

    /* ---------- Buka otomatis (link email ?sign=1, redirect dari form, atau ada error validasi) ---------- */
    @php
      $reopenPane = $errors->has('reason') ? 'reject'
        : ($errors->hasAny(['sign_mode', 'signature_pad', 'save_to_profile', 'confirm', 'surat_jalan']) ? 'sign'
        : ($autoOpenSign ? 'sign' : null));
    @endphp
    @if ($reopenPane)
      window.addEventListener('load', () => {
        if (!window.bootstrap) return;
        bootstrap.Modal.getOrCreateInstance(modalEl).show();
        showPane(@json($reopenPane));
      });
    @endif
  })();
</script>
@endif
@endsection
