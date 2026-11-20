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
  #sig-pad { width:100%; height:180px; border:1px dashed #b4b8bd; border-radius:.5rem; background:#fff; touch-action:none; cursor:crosshair; }
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
        <form method="POST" action="{{ route('surat-jalan.submit', $sj->uuid) }}"
              onsubmit="return confirm('Submit Surat Jalan sekarang?\n\nNomor diterbitkan, stamp Diserahkan terpasang, dan email dikirim ke Dept. Head.')">
          @csrf
          <button class="btn btn-sm btn-primary"><i class="ri-send-plane-line me-1"></i>Submit &amp; Sign</button>
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

{{-- Panel aksi signer --}}
@if ($canSign)
  <div class="card mb-4 border border-warning">
    <div class="card-body">
      <div class="d-flex flex-wrap justify-content-between gap-3">
        <div>
          <h5 class="mb-1">Giliran Anda: <span class="text-warning">{{ $currentSigner->slotLabel() }}</span></h5>
          <div class="text-muted">Periksa isi Surat Jalan di bawah. Saat Anda klik <b>Approve / Sign</b>, stamp Anda otomatis ter-load di kotak <b>{{ $currentSigner->slotLabel() }}</b>.</div>
        </div>
        <div class="text-center">
          @if ($myStampUrl)
            <img src="{{ $myStampUrl }}" class="my-stamp" alt="stamp saya">
            <div><small class="text-muted">Image stamp profil Anda</small></div>
          @else
            <span class="badge bg-label-warning">Profil belum punya gambar tanda tangan → QR stamp</span>
            <div><small class="text-muted">QR berisi Nama, Jabatan &amp; Tanggal sign</small></div>
          @endif
        </div>
      </div>

      <form method="POST" action="{{ route('surat-jalan.approve', $sj->uuid) }}" id="approve-form" class="mt-3">
        @csrf
        <input type="hidden" name="signature_pad" id="signature_pad_input">

        @if ($currentSigner->step === 4)
          <div class="mb-3">
            <div class="form-check form-check-inline">
              <input class="form-check-input" type="radio" name="sign_mode" id="mode-stamp" value="stamp" checked>
              <label class="form-check-label" for="mode-stamp">Gunakan stamp akun saya</label>
            </div>
            <div class="form-check form-check-inline">
              <input class="form-check-input" type="radio" name="sign_mode" id="mode-pad" value="pad">
              <label class="form-check-label" for="mode-pad">Tanda tangan di layar (serah terima)</label>
            </div>
            <div id="pad-wrap" class="mt-2" style="display:none; max-width:520px">
              <canvas id="sig-pad"></canvas>
              <div class="d-flex justify-content-between mt-1">
                <small class="text-muted">Gores tanda tangan penerima di area di atas</small>
                <button type="button" class="btn btn-sm btn-text-secondary" id="pad-clear">Hapus</button>
              </div>
            </div>
          </div>
        @endif

        <div class="d-flex flex-wrap gap-2">
          <button type="submit" class="btn btn-success"><i class="ri-check-double-line me-1"></i>Approve / Sign</button>
          <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#rejectModal"><i class="ri-close-line me-1"></i>Reject</button>
        </div>
      </form>
    </div>
  </div>

  <div class="modal fade" id="rejectModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
      <form method="POST" action="{{ route('surat-jalan.reject', $sj->uuid) }}" class="modal-content">
        @csrf
        <div class="modal-header">
          <h5 class="modal-title">Tolak Surat Jalan</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <p class="text-muted mb-2">Proses approval akan <b>dihentikan</b> dan status berubah menjadi REJECTED. Pemohon akan menerima email pemberitahuan.</p>
          <label class="form-label" for="reason">Alasan penolakan <span class="text-danger">*</span></label>
          <textarea name="reason" id="reason" rows="4" class="form-control" required minlength="5" maxlength="1000">{{ old('reason') }}</textarea>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-danger">Reject</button>
        </div>
      </form>
    </div>
  </div>
@elseif ($currentSigner && $sj->status === 'IN_PROGRESS')
  <div class="alert alert-warning">
    <i class="ri-time-line me-1"></i>Menunggu tanda tangan <b>{{ $currentSigner->signer_name }}</b> ({{ $currentSigner->slotLabel() }}).
  </div>
@endif

<div class="row g-4">
  <div class="col-xl-8">
    @include('content.surat-jalan._sheet', ['sj' => $sj, 'qrSvgs' => $qrSvgs])
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
@if ($canSign)
<script>
  (function () {
    const form = document.getElementById('approve-form');
    const canvas = document.getElementById('sig-pad');
    const input = document.getElementById('signature_pad_input');
    let drawn = false;

    if (canvas) {
      const wrap = document.getElementById('pad-wrap');
      const ctx = canvas.getContext('2d');

      const resize = () => {
        const ratio = Math.max(window.devicePixelRatio || 1, 1);
        canvas.width = canvas.offsetWidth * ratio;
        canvas.height = canvas.offsetHeight * ratio;
        ctx.setTransform(ratio, 0, 0, ratio, 0, 0);
        ctx.lineWidth = 2.2; ctx.lineCap = 'round'; ctx.lineJoin = 'round'; ctx.strokeStyle = '#0b2a6f';
        drawn = false;
      };

      document.querySelectorAll('input[name="sign_mode"]').forEach(r => r.addEventListener('change', () => {
        const pad = document.getElementById('mode-pad').checked;
        wrap.style.display = pad ? '' : 'none';
        if (pad) resize();
      }));

      let drawing = false, last = null;
      const pos = (e) => { const r = canvas.getBoundingClientRect(); return { x: e.clientX - r.left, y: e.clientY - r.top }; };
      canvas.addEventListener('pointerdown', (e) => { drawing = true; last = pos(e); canvas.setPointerCapture(e.pointerId); });
      canvas.addEventListener('pointermove', (e) => {
        if (!drawing) return;
        const p = pos(e);
        ctx.beginPath(); ctx.moveTo(last.x, last.y); ctx.lineTo(p.x, p.y); ctx.stroke();
        last = p; drawn = true;
      });
      ['pointerup', 'pointercancel', 'pointerleave'].forEach(ev => canvas.addEventListener(ev, () => drawing = false));
      document.getElementById('pad-clear').addEventListener('click', resize);
    }

    form.addEventListener('submit', (e) => {
      const padMode = document.getElementById('mode-pad')?.checked;
      if (padMode) {
        if (!drawn) { e.preventDefault(); alert('Silakan gores tanda tangan pada area signature pad terlebih dahulu.'); return; }
        input.value = canvas.toDataURL('image/png');
      } else {
        input.value = '';
      }
      if (!confirm('Approve & tanda tangani Surat Jalan ini sekarang?')) { e.preventDefault(); return; }
      form.querySelector('button[type="submit"]').disabled = true;
    });

    @if ($errors->has('reason'))
      document.addEventListener('DOMContentLoaded', () => new bootstrap.Modal(document.getElementById('rejectModal')).show());
    @endif
  })();
</script>
@endif
@endsection
