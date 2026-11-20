{{-- Preview HTML lembar Surat Jalan (meniru layout PDF resmi) --}}
@php
  $company = config('surat_jalan.company');
  $kategoriSelected = (array) $sj->kategori;
  $minRows = 8;
@endphp
<style>
  .sj-sheet { background:#fff; color:#141414; border:1px solid #d9dee3; box-shadow:0 2px 10px rgba(0,0,0,.06); padding:22px 24px; font-family: Helvetica, Arial, sans-serif; position:relative; overflow:hidden; }
  .sj-sheet .sj-watermark { position:absolute; inset:0; display:flex; align-items:center; justify-content:center; pointer-events:none; font-size:72px; font-weight:800; transform:rotate(-30deg); opacity:.07; letter-spacing:4px; white-space:nowrap; }
  .sj-head { display:flex; gap:12px; align-items:center; border-bottom:3px double #222; padding-bottom:6px; }
  .sj-head img { height:44px; width:auto; }
  .sj-head .co-name { font-weight:700; font-size:16px; }
  .sj-head .co-addr { font-size:11px; color:#444; white-space:pre-line; }
  .sj-top { display:flex; gap:16px; margin-top:10px; }
  .sj-top .left { flex:1 1 auto; min-width:0; }
  .sj-title { text-align:center; font-weight:800; font-size:22px; text-decoration:underline; letter-spacing:1px; }
  .sj-no { text-align:center; font-weight:700; font-size:13px; margin-bottom:8px; }
  .sj-field { display:flex; font-size:13px; margin-bottom:4px; }
  .sj-field .lbl { width:78px; flex:0 0 78px; }
  .sj-field .val { flex:1; font-weight:700; border-bottom:1px solid #333; min-width:0; overflow-wrap:anywhere; }
  .sj-check { flex:0 0 230px; border:1px solid #222; font-size:12px; align-self:flex-start; }
  .sj-check .ttl { background:#f0f0f0; border-bottom:1px solid #222; text-align:center; font-weight:700; padding:3px; }
  .sj-check .opt { display:flex; gap:6px; padding:2px 6px; align-items:flex-start; }
  .sj-check .box { width:13px; height:13px; border:1px solid #222; flex:0 0 13px; margin-top:2px; display:flex; align-items:center; justify-content:center; font-size:11px; line-height:1; font-weight:700; }
  .sj-check .opt.on { font-weight:700; }
  .sj-table { width:100%; border-collapse:collapse; margin-top:12px; font-size:12.5px; }
  .sj-table th, .sj-table td { border:1px solid #222; padding:4px 6px; vertical-align:middle; }
  .sj-table th { background:#ebebeb; text-align:center; }
  .sj-table td.blank { height:26px; }
  .sj-sign { display:grid; grid-template-columns:repeat(4, 1fr); margin-top:14px; border:1px solid #222; }
  .sj-sign .slot { border-right:1px solid #222; display:flex; flex-direction:column; min-width:0; }
  .sj-sign .slot:last-child { border-right:0; }
  .sj-sign .slot .ttl { background:#ebebeb; border-bottom:1px solid #222; text-align:center; font-weight:700; font-size:12.5px; padding:4px; }
  .sj-sign .slot .stamp { height:110px; display:flex; align-items:center; justify-content:center; padding:6px; border-bottom:1px solid #222; }
  .sj-sign .slot .stamp img { max-width:100%; max-height:100%; object-fit:contain; }
  .sj-sign .slot .stamp .qr { display:flex; align-items:center; gap:6px; }
  .sj-sign .slot .stamp .qr svg { width:84px; height:84px; }
  .sj-sign .slot .stamp .qr .txt { font-size:9px; color:#166534; font-weight:700; line-height:1.25; }
  .sj-sign .slot .stamp .wait { font-size:11px; font-style:italic; color:#999; text-align:center; }
  .sj-sign .slot .stamp .pending { color:#b45309; font-style:normal; font-weight:600; }
  .sj-sign .slot .stamp .rej { color:#dc2626; font-weight:800; font-size:15px; }
  .sj-sign .slot .nm { text-align:center; font-weight:700; font-size:11.5px; padding:3px 4px 0; overflow-wrap:anywhere; }
  .sj-sign .slot .ps { text-align:center; font-size:10.5px; color:#444; }
  .sj-sign .slot .dt { text-align:center; font-size:10.5px; color:#444; padding-bottom:4px; }
  .sj-foot { font-size:10px; color:#777; margin-top:8px; }
  @media (max-width: 767.98px) {
    .sj-sheet { padding:14px; }
    .sj-top { flex-direction:column; }
    .sj-check { flex-basis:auto; }
    .sj-sign { grid-template-columns:repeat(2, 1fr); }
    .sj-sign .slot:nth-child(2) { border-right:0; }
    .sj-sign .slot:nth-child(-n+2) { border-bottom:1px solid #222; }
  }
</style>

<div class="sj-sheet">
  @if ($sj->status !== \App\Models\SuratJalan::STATUS_COMPLETED)
    <div class="sj-watermark" style="color: {{ $sj->status === 'REJECTED' ? '#dc2626' : '#555' }}">
      {{ $sj->status === 'IN_PROGRESS' ? 'IN PROGRESS' : $sj->status }}
    </div>
  @endif

  <div class="sj-head">
    @if (is_file(public_path($company['logo'])))
      <img src="{{ asset($company['logo']) }}" alt="logo">
    @endif
    <div>
      <div class="co-name">{{ $company['name'] }}</div>
      <div class="co-addr">{{ $company['address'] }}@if($company['phone'])&#10;Telp. {{ $company['phone'] }}@endif</div>
    </div>
  </div>

  <div class="sj-top">
    <div class="left">
      <div class="sj-title">SURAT JALAN</div>
      <div class="sj-no">NO : {{ $sj->sj_no ?? '( DRAFT )' }}</div>
      <div class="sj-field"><span>{{ $sj->location }}, {{ ($sj->sj_date ?? now())->format('d-m-Y') }}</span></div>
      <div class="sj-field"><span class="lbl">Kepada</span><span class="me-1">:</span>
        <span class="val">{{ $sj->kepada }}@if($sj->kepada_tipe) ({{ config('surat_jalan.kepada_tipe.' . $sj->kepada_tipe) }})@endif</span></div>
      <div class="sj-field"><span class="lbl">No. Kend</span><span class="me-1">:</span><span class="val">{{ $sj->no_kendaraan ?: '-' }}</span></div>
    </div>
    <div class="sj-check">
      <div class="ttl">Keperluan / Tujuan Barang</div>
      @foreach (config('surat_jalan.kategori') as $code => $label)
        @php $on = in_array($code, $kategoriSelected, true); @endphp
        <div class="opt {{ $on ? 'on' : '' }}">
          <span class="box">{!! $on ? '&#10003;' : '' !!}</span>
          <span>{{ $label }}@if($code === 'LAIN_LAIN' && $on && $sj->kategori_lain): {{ $sj->kategori_lain }}@endif</span>
        </div>
      @endforeach
    </div>
  </div>

  <div class="table-responsive">
    <table class="sj-table">
      <thead>
        <tr><th style="width:6%">NO</th><th style="width:46%">JENIS BARANG</th><th style="width:16%">QTY</th><th>KETERANGAN</th></tr>
      </thead>
      <tbody>
        @foreach ($sj->items as $item)
          <tr>
            <td class="text-center">{{ $item->line_no }}</td>
            <td>{{ $item->jenis_barang }}</td>
            <td class="text-end">{{ $item->qtyFormatted() }}</td>
            <td>{{ $item->keterangan }}</td>
          </tr>
        @endforeach
        @for ($i = $sj->items->count(); $i < $minRows; $i++)
          <tr><td class="blank"></td><td></td><td></td><td></td></tr>
        @endfor
      </tbody>
    </table>
  </div>

  <div class="sj-sign">
    @foreach (config('surat_jalan.slots') as $step => $slot)
      @php $signer = $sj->signerForStep($step); @endphp
      <div class="slot" id="slot-{{ $step }}">
        <div class="ttl">{{ $slot['label'] }}</div>
        <div class="stamp">
          @if ($signer && $signer->isSigned())
            @if ($signer->stamp_path)
              <img src="{{ route('surat-jalan.stamp', [$sj->uuid, $step]) }}" alt="stamp {{ $slot['label'] }}">
            @elseif (isset($qrSvgs[$step]))
              <div class="qr">{!! $qrSvgs[$step] !!}<div class="txt">DIGITALLY<br>SIGNED<br><span style="color:#555;font-weight:400">{{ $signer->signed_at->format('d-m-Y') }}<br>{{ $signer->signed_at->format('H:i:s') }}</span></div></div>
            @endif
          @elseif ($signer && $signer->status === 'REJECTED')
            <span class="rej">REJECTED</span>
          @elseif ($signer && $signer->status === 'PENDING')
            <span class="wait pending">Menunggu tanda tangan</span>
          @else
            <span class="wait">&nbsp;</span>
          @endif
        </div>
        <div class="nm">( {{ $signer?->signer_name ?? '..................' }} )</div>
        <div class="ps">{{ $signer?->signer_position ?: $slot['role'] }}</div>
        <div class="dt">{{ $signer && $signer->isSigned() ? $signer->signed_at->format('d-m-Y H:i:s') : 'Tgl: ...........' }}</div>
      </div>
    @endforeach
  </div>

  <div class="sj-foot">Dokumen elektronik — pengganti Lembar 1 (Pemilik Barang), Lembar 2 (Penerima), Lembar 3 (Security). Doc ID: {{ $sj->uuid }}</div>
</div>
