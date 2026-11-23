@component('mail::message')
Halo {{ $recipientName }},

@if ($type === \App\Mail\SuratJalanNotification::TYPE_SIGN_REQUEST)
Anda diminta untuk melakukan **Approve / Sign** sebagai **{{ $slotLabel }}** pada Surat Jalan berikut:
@elseif ($type === \App\Mail\SuratJalanNotification::TYPE_COMPLETED)
Seluruh proses tanda tangan Surat Jalan berikut telah **selesai (COMPLETED)**. Dokumen PDF resmi sudah dapat diunduh.
@elseif ($type === \App\Mail\SuratJalanNotification::TYPE_REJECTED)
Surat Jalan berikut **DITOLAK** oleh {{ $actorName }}.
@endif

@component('mail::table')
| | |
|:--|:--|
| No. Surat Jalan | {{ $sj->sj_no }} |
| Tanggal | {{ optional($sj->sj_date)->format('d-m-Y') }} |
| Kepada | {{ $sj->kepada }} |
| Pemohon | {{ $sj->creator?->name }} |
| Jumlah Item | {{ $sj->items()->count() }} baris |
@endcomponent

@if ($type === \App\Mail\SuratJalanNotification::TYPE_REJECTED && $reason)
**Alasan penolakan:** {{ $reason }}
@endif

@component('mail::button', ['url' => $documentLink])
{{ $type === \App\Mail\SuratJalanNotification::TYPE_SIGN_REQUEST ? 'Review & Approve' : 'Lihat Dokumen' }}
@endcomponent

Tautan di atas akan meminta Anda login ke aplikasi terlebih dahulu bila sesi belum aktif.

Terima kasih,<br>
{{ config('app.name') }}
@endcomponent
