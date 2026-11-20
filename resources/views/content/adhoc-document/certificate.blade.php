<style>
    body {
        font-family: helvetica, sans-serif;
        color: #2b2b2b;
        font-size: 10pt;
        line-height: 1.4;
    }
    .header-table {
        width: 100%;
        border-bottom: 2px solid #666cff;
        padding-bottom: 10px;
        margin-bottom: 15px;
    }
    .title {
        font-size: 18pt;
        font-weight: bold;
        color: #666cff;
        margin: 0;
    }
    .subtitle {
        font-size: 10pt;
        color: #777;
    }
    .badge {
        font-weight: bold;
        padding: 4px 10px;
        color: #ffffff;
        font-size: 9pt;
        text-align: center;
    }
    .section-title {
        font-size: 11pt;
        font-weight: bold;
        color: #333333;
        border-bottom: 1px solid #e0e0e0;
        padding-bottom: 4px;
        margin-top: 15px;
        margin-bottom: 8px;
    }
    table.data-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 15px;
    }
    table.data-table th {
        background-color: #f4f5f9;
        font-weight: bold;
        border: 1px solid #d9d9d9;
        padding: 6px;
        font-size: 8.5pt;
        color: #444;
    }
    table.data-table td {
        border: 1px solid #d9d9d9;
        padding: 5px 6px;
        font-size: 8pt;
    }
    .hash-code {
        font-family: courier, monospace;
        font-size: 7pt;
        color: #444;
        word-break: break-all;
    }
    .footer {
        font-size: 7.5pt;
        color: #888;
        border-top: 1px solid #e0e0e0;
        padding-top: 6px;
        margin-top: 25px;
        text-align: center;
    }
</style>

<table class="header-table" cellpadding="0" cellspacing="0">
    <tr>
        <td width="70%">
            <div class="title">Certificate of Completion</div>
            <div class="subtitle">Official Audit Trail &amp; Electronic Signature Record</div>
        </td>
        <td width="30%" align="right">
            <span style="font-weight: bold; font-size: 11pt; color: {{ $statusColor }};">STATUS: {{ $document->status }}</span><br>
            <span style="font-size: 8pt; color: #777;">Generated on: {{ now()->format('d/m/Y H:i:s') }}</span>
        </td>
    </tr>
</table>

<div class="section-title">1. Document Information &amp; Digital Integrity</div>
<table cellpadding="4" cellspacing="0" style="width: 100%; font-size: 8.5pt;">
    <tr>
        <td width="22%"><strong>Document Title:</strong></td>
        <td width="78%">{{ $document->title }}</td>
    </tr>
    <tr>
        <td><strong>Document UUID:</strong></td>
        <td>{{ $document->uuid }}</td>
    </tr>
    <tr>
        <td><strong>Workflow Mode:</strong></td>
        <td>{{ $document->workflow_type }} ({{ $document->workflow_type === 'SEQUENTIAL' ? 'Urutan Berurutan' : 'Bersamaan / Paralel' }})</td>
    </tr>
    <tr>
        <td><strong>Uploaded By:</strong></td>
        <td>{{ $document->creator->name ?? 'N/A' }} ({{ $document->creator->email ?? '-' }}) on {{ $document->created_at ? $document->created_at->format('d/m/Y H:i:s') : '-' }}</td>
    </tr>
    <tr>
        <td><strong>Original File:</strong></td>
        <td>{{ $document->original_file_name }} ({{ number_format($document->file_size / 1024, 1) }} KB, {{ $document->page_count }} Halaman)</td>
    </tr>
    <tr>
        <td><strong>Original SHA-256:</strong></td>
        <td class="hash-code">{{ $document->original_file_hash ?: '-' }}</td>
    </tr>
    <tr>
        <td><strong>Signed SHA-256:</strong></td>
        <td class="hash-code">{{ $document->signed_file_hash ?: '(Belum selesai / dalam proses)' }}</td>
    </tr>
</table>

<div class="section-title">2. Signer Summary (Daftar Penanda Tangan)</div>
<table class="data-table" cellpadding="5" cellspacing="0">
    <thead>
        <tr>
            <th width="8%" align="center">Urutan</th>
            <th width="26%">Nama Penanda Tangan</th>
            <th width="20%">Peran / Jabatan</th>
            <th width="16%">Status</th>
            <th width="15%">Waktu Sign</th>
            <th width="15%">IP Address</th>
        </tr>
    </thead>
    <tbody>
        @foreach($document->signers as $signer)
        <tr>
            <td align="center">#{{ $signer->order_index }}</td>
            <td>
                <strong>{{ $signer->user->name ?? 'User' }}</strong><br>
                <span style="font-size: 7pt; color: #555;">NIK/ID: {{ ($signer->user && $signer->user->employee && $signer->user->employee->nik) ? $signer->user->employee->nik : ($signer->user->user_name ?? '-') }}</span><br>
                <span style="font-size: 7pt; color: #777;">{{ $signer->user->email ?? '-' }}</span>
            </td>
            <td>{{ $signer->role_name ?: 'Signer' }}</td>
            <td>
                @if($signer->status === 'SIGNED')
                    <span style="color: #28c76f; font-weight: bold;">SIGNED</span>
                @elseif($signer->status === 'REJECTED')
                    <span style="color: #ea5455; font-weight: bold;">REJECTED</span>
                @elseif($signer->status === 'PENDING')
                    <span style="color: #ff9f43; font-weight: bold;">PENDING</span>
                @else
                    <span style="color: #82868b;">WAITING</span>
                @endif
            </td>
            <td>{{ $signer->signed_at ? $signer->signed_at->format('d/m/Y H:i:s') : '-' }}</td>
            <td>{{ $signer->ip_address ?: '-' }}</td>
        </tr>
        @endforeach
    </tbody>
</table>

<div class="section-title">3. Chronological Event Log (Riwayat Audit Terinci)</div>
<table class="data-table" cellpadding="4" cellspacing="0">
    <thead>
        <tr>
            <th width="18%">Waktu (WIB)</th>
            <th width="16%">Aksi / Event</th>
            <th width="22%">Pelaku (Actor)</th>
            <th width="14%">IP Address</th>
            <th width="30%">Keterangan &amp; Metadata</th>
        </tr>
    </thead>
    <tbody>
        @forelse($document->auditLogs as $log)
        <tr>
            <td>{{ $log->created_at->format('d/m/Y H:i:s') }}</td>
            <td><strong>{{ $log->action }}</strong></td>
            <td>{{ $log->actor_name }}<br><span style="font-size: 7pt; color: #666;">{{ $log->actor_email }}</span></td>
            <td>{{ $log->ip_address ?: '-' }}</td>
            <td style="font-size: 7.5pt;">{{ $log->description ?: '-' }}</td>
        </tr>
        @empty
        <tr>
            <td colspan="5" align="center">Belum ada catatan log aktivitas.</td>
        </tr>
        @endforelse
    </tbody>
</table>

<div class="footer">
    Dokumen ini adalah Sertifikat Penyelesaian (Certificate of Completion) resmi yang diterbitkan secara otomatis oleh Sistem E-Sign Internal SYS-DOCS.<br>
    Integritas isi dan tanda tangan digital terverifikasi dengan enkripsi hash SHA-256 dan catatan audit tak terhapuskan pada database internal.
</div>
