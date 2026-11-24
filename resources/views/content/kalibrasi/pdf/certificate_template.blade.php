<style>
    body {
        font-family: helvetica, sans-serif;
        font-size: 9pt;
        color: #1e293b;
        line-height: 1.4;
    }
    .header-table {
        width: 100%;
        border-bottom: 2px solid #0f172a;
        padding-bottom: 8px;
        margin-bottom: 12px;
    }
    .company-title {
        font-size: 14pt;
        font-weight: bold;
        color: #0f172a;
        letter-spacing: 0.5px;
    }
    .company-sub {
        font-size: 8pt;
        color: #64748b;
    }
    .cert-title {
        font-size: 13pt;
        font-weight: bold;
        color: #1e3a8a;
        text-align: center;
        margin-top: 6px;
        margin-bottom: 2px;
        letter-spacing: 1px;
    }
    .cert-subtitle {
        font-size: 8.5pt;
        font-style: italic;
        color: #475569;
        text-align: center;
        margin-bottom: 14px;
    }
    .cert-meta {
        width: 100%;
        margin-bottom: 14px;
        background-color: #f8fafc;
        border: 1px solid #cbd5e1;
        padding: 6px 10px;
    }
    .section-title {
        font-size: 9.5pt;
        font-weight: bold;
        color: #1e40af;
        border-bottom: 1px solid #94a3b8;
        padding-bottom: 3px;
        margin-top: 10px;
        margin-bottom: 6px;
        text-transform: uppercase;
    }
    .data-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 10px;
    }
    .data-table td {
        padding: 4px 6px;
        vertical-align: top;
        font-size: 8.5pt;
    }
    .data-table .label {
        width: 32%;
        font-weight: bold;
        color: #334155;
    }
    .data-table .sep {
        width: 3%;
        text-align: center;
    }
    .data-table .val {
        width: 65%;
        color: #0f172a;
    }
    .conclusion-box {
        border: 1.5px solid #10b981;
        background-color: #ecfdf5;
        padding: 8px 12px;
        margin-top: 8px;
        margin-bottom: 14px;
        text-align: center;
    }
    .conclusion-fail {
        border-color: #ef4444;
        background-color: #fef2f2;
    }
    .conclusion-note {
        border-color: #f59e0b;
        background-color: #fffbeb;
    }
    .conclusion-text {
        font-size: 11pt;
        font-weight: bold;
    }
    .sig-table {
        width: 100%;
        margin-top: 14px;
        border-collapse: collapse;
    }
    .sig-box {
        width: 48%;
        border: 1px solid #cbd5e1;
        background-color: #ffffff;
        text-align: center;
        padding: 8px;
    }
    .sig-role {
        font-weight: bold;
        font-size: 8.5pt;
        color: #1e293b;
        margin-bottom: 4px;
    }
    .sig-area {
        height: 65px;
        vertical-align: middle;
        text-align: center;
    }
    .sig-name {
        font-weight: bold;
        font-size: 9pt;
        border-top: 1px dotted #94a3b8;
        padding-top: 4px;
        color: #0f172a;
    }
    .sig-meta {
        font-size: 7.5pt;
        color: #64748b;
    }
    .footer-note {
        margin-top: 14px;
        font-size: 7.5pt;
        color: #64748b;
        border-top: 1px solid #e2e8f0;
        padding-top: 6px;
    }
</style>

{{-- Header Instansi --}}
<table class="header-table">
    <tr>
        <td width="70%">
            <div class="company-title">PT. METALART ASTRA INDONESIA</div>
            <div class="company-sub">Quality Assurance & Metrology Department | QMS Laboratory</div>
            <div class="company-sub">Kawasan Industri KIIC, Karawang Barat, Jawa Barat - Indonesia</div>
        </td>
        <td width="30%" align="right" style="vertical-align: middle;">
            <table style="border: 1px solid #0f172a; padding: 4px 8px; font-size: 7.5pt; text-align: left;">
                <tr><td><b>No. Dokumen</b></td><td>: FRM-QA-CAL-01</td></tr>
                <tr><td><b>Revisi</b></td><td>: 02</td></tr>
                <tr><td><b>Halaman</b></td><td>: 1 dari Lampiran</td></tr>
            </table>
        </td>
    </tr>
</table>

{{-- Judul Sertifikat --}}
<div class="cert-title">SERTIFIKAT KALIBRASI INTERNAL</div>
<div class="cert-subtitle">(INTERNAL CALIBRATION CERTIFICATE)</div>

{{-- Metadata Sertifikat --}}
<table class="cert-meta">
    <tr>
        <td width="55%"><b>Nomor Sertifikat:</b> <span style="font-family: courier, monospace; font-size: 10pt; color: #1e40af;"><b>{{ $kalibrasi->certificate_number }}</b></span></td>
        <td width="45%" align="right"><b>Tanggal Terbit:</b> {{ $kalibrasi->created_at ? $kalibrasi->created_at->format('d/m/Y') : date('d/m/Y') }}</td>
    </tr>
</table>

{{-- Bagian 1: Identitas Alat Ukur --}}
<div class="section-title">1. IDENTITAS ALAT UKUR (INSTRUMENT IDENTIFICATION)</div>
<table class="data-table">
    <tr>
        <td class="label">Nama Alat Ukur</td>
        <td class="sep">:</td>
        <td class="val"><b>{{ $kalibrasi->alatUkur->name }}</b></td>
    </tr>
    <tr>
        <td class="label">No. Inventaris / Kode Aset</td>
        <td class="sep">:</td>
        <td class="val"><span style="font-family: courier, monospace;"><b>{{ $kalibrasi->alatUkur->asset_code }}</b></span></td>
    </tr>
    <tr>
        <td class="label">Merk / Tipe / Model</td>
        <td class="sep">:</td>
        <td class="val">{{ $kalibrasi->alatUkur->brand_model ?: '-' }}</td>
    </tr>
    <tr>
        <td class="label">Nomor Seri (Serial No.)</td>
        <td class="sep">:</td>
        <td class="val">{{ $kalibrasi->alatUkur->serial_number ?: '-' }}</td>
    </tr>
    <tr>
        <td class="label">Kapasitas / Rentang Ukur</td>
        <td class="sep">:</td>
        <td class="val">{{ $kalibrasi->alatUkur->standard_spec ?: '-' }}</td>
    </tr>
    <tr>
        <td class="label">Lokasi / Area Pengguna</td>
        <td class="sep">:</td>
        <td class="val">{{ $kalibrasi->alatUkur->location ?: 'QA' }}</td>
    </tr>
</table>

{{-- Bagian 2: Data Pelaksanaan Kalibrasi --}}
<div class="section-title">2. PELAKSANAAN KALIBRASI (CALIBRATION EXECUTION)</div>
<table class="data-table">
    <tr>
        <td class="label">Tanggal Pelaksanaan</td>
        <td class="sep">:</td>
        <td class="val"><b>{{ $kalibrasi->calibration_date->format('d F Y') }}</b></td>
    </tr>
    <tr>
        <td class="label">Tanggal Kalibrasi Ulang (Due)</td>
        <td class="sep">:</td>
        <td class="val"><b style="color: #1e40af;">{{ $kalibrasi->next_due_date->format('d F Y') }}</b> (Interval: 12 Bulan)</td>
    </tr>
    <tr>
        <td class="label">Standar / Acuan Kalibrator</td>
        <td class="sep">:</td>
        <td class="val">{{ $kalibrasi->calibrator_used ?: ($kalibrasi->alatUkur->calibrator_ref ?: 'Caliper Checker / Ceramic Gauge Block') }}</td>
    </tr>
    <tr>
        <td class="label">Kondisi Lingkungan Lab</td>
        <td class="sep">:</td>
        <td class="val">Suhu: <b>{{ $kalibrasi->temperature ?: '20.0' }} &plusmn; 2 &deg;C</b> | Kelembaban: <b>{{ $kalibrasi->humidity ?: '55.0' }} &plusmn; 5 % RH</b></td>
    </tr>
</table>

{{-- Bagian 3: Kesimpulan Hasil --}}
<div class="section-title">3. KESIMPULAN HASIL (CALIBRATION CONCLUSION)</div>
@php
    $concClass = '';
    $concColor = '#065f46';
    if ($kalibrasi->conclusion === 'FAIL') {
        $concClass = 'conclusion-fail';
        $concColor = '#991b1b';
    } elseif ($kalibrasi->conclusion === 'PASS_WITH_NOTE') {
        $concClass = 'conclusion-note';
        $concColor = '#92400e';
    }
@endphp
<div class="conclusion-box {{ $concClass }}">
    <span style="font-size: 8pt; color: #475569; display: block; margin-bottom: 2px;">STATUS HASIL KALIBRASI:</span>
    <span class="conclusion-text" style="color: {{ $concColor }};">
        {{ $kalibrasi->conclusion_badge['label'] }}
    </span>
</div>

{{-- Bagian 4: Pengesahan & Tanda Tangan --}}
<div class="section-title">4. PENGESAHAN DOKUMEN (AUTHORIZATION)</div>
<table class="sig-table">
    <tr>
        {{-- Teknisi --}}
        <td class="sig-box" width="48%">
            <div class="sig-role">Dikalibrasi Oleh (Technician)</div>
            <div class="sig-area">
                @if($technicianSignPath && file_exists($technicianSignPath))
                    <img src="{{ $technicianSignPath }}" style="height: 50px; max-width: 140px;" />
                @else
                    <div style="padding-top: 15px; color: #0f172a; font-weight: bold; font-size: 9pt;">
                        [ DIGITAL SIGNED ]
                    </div>
                @endif
            </div>
            <div class="sig-name">{{ $kalibrasi->technician?->name ?: 'Teknisi Kalibrasi' }}</div>
            <div class="sig-meta">
                Tanggal: {{ $kalibrasi->calibration_date->format('d/m/Y') }}<br>
                NIK: {{ $kalibrasi->technician?->employee?->nik ?: '-' }}
            </div>
        </td>

        <td width="4%"></td>

        {{-- Approver / Atasan --}}
        <td class="sig-box" width="48%">
            <div class="sig-role">Disetujui Oleh (QA Manager / Supervisor)</div>
            <div class="sig-area">
                @if($isFinal && $approverSignPath && file_exists($approverSignPath))
                    <img src="{{ $approverSignPath }}" style="height: 50px; max-width: 140px;" />
                @elseif($isFinal)
                    <div style="padding-top: 15px; color: #047857; font-weight: bold; font-size: 9pt;">
                        &#10003; APPROVED ONLINE
                    </div>
                @else
                    <div style="padding-top: 18px; color: #f59e0b; font-size: 8.5pt; font-style: italic;">
                        [ Menunggu Approval Atasan ]
                    </div>
                @endif
            </div>
            <div class="sig-name">{{ $kalibrasi->approver?->name ?: 'Manager QA / Metrologi' }}</div>
            <div class="sig-meta">
                Status: {{ $isFinal ? 'Approved pada ' . ($kalibrasi->approved_at ? $kalibrasi->approved_at->format('d/m/Y H:i') : date('d/m/Y')) : 'Pending' }}<br>
                Posisi: {{ $kalibrasi->approver?->employee?->positions?->name ?: 'QA & Metrology Head' }}
            </div>
        </td>
    </tr>
</table>

{{-- Footer Note --}}
<div class="footer-note">
    <b>Catatan Penting:</b><br>
    1. Sertifikat ini sah dan mengikat mutu keabsahan alat ukur sejak tanggal pelaksanaan kalibrasi hingga tanggal jatuh tempo berikutnya.<br>
    2. Rincian tabel data pengukuran, nilai koreksi, dan kurva perhitungan ketidakpastian (uncertainty) terlampir utuh pada halaman-halaman berikutnya dari dokumen ini.
</div>
