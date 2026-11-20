<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Signature Request</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f4f6f9; margin: 0; padding: 20px; color: #333; }
        .card { max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.08); overflow: hidden; }
        .header { background: #666cff; color: #ffffff; padding: 24px; text-align: center; }
        .header h2 { margin: 0; font-size: 22px; font-weight: 600; }
        .body { padding: 30px; line-height: 1.6; }
        .doc-box { background: #f8f9fa; border-left: 4px solid #666cff; padding: 15px 20px; margin: 20px 0; border-radius: 4px; }
        .btn { display: inline-block; background: #666cff; color: #ffffff !important; padding: 12px 28px; text-decoration: none; border-radius: 6px; font-weight: bold; margin-top: 15px; }
        .footer { padding: 15px 30px; background: #f8f9fa; text-align: center; font-size: 12px; color: #888; border-top: 1px solid #eee; }
    </style>
</head>
<body>
    <div class="card">
        <div class="header">
            <h2>E-Signature Request</h2>
        </div>
        <div class="body">
            <p>Halo, <strong>{{ $signer->user->name ?? 'User' }}</strong>,</p>
            <p>Anda telah ditugaskan untuk meninjau dan menandatangani dokumen berikut:</p>
            <div class="doc-box">
                <p style="margin: 0 0 8px 0;"><strong>Judul Dokumen:</strong> {{ $document->title }}</p>
                <p style="margin: 0 0 8px 0;"><strong>Pengunggah:</strong> {{ $document->creator->name ?? 'Internal User' }}</p>
                <p style="margin: 0;"><strong>Peran / Posisi:</strong> {{ $signer->role_name ?? 'Signer' }}</p>
            </div>
            <p>Silakan klik tautan di bawah ini untuk melihat pratinjau dokumen dan memberikan tanda tangan Anda:</p>
            <div style="text-align: center; margin: 30px 0;">
                <a href="{{ $reviewUrl }}" class="btn">Tinjau & Tanda Tangani Dokumen</a>
            </div>
            <p style="font-size: 13px; color: #666;"><em>Catatan: Jika Anda belum login ke aplikasi, Anda akan diarahkan ke halaman login internal terlebih dahulu.</em></p>
        </div>
        <div class="footer">
            Sistem Dokumen Manajemen & E-Sign Internal &copy; {{ date('Y') }}
        </div>
    </div>
</body>
</html>
