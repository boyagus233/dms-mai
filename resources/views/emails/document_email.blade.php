@component('mail::message')
Yth. Bapak/Ibu,

Melalui email ini, saya membagikan beberapa dokumen resmi dari Sistem Pengendalian Dokumen.<br>
Dokumen-dokumen ini adalah versi yang sudah disahkan dan terkait dengan {{ $documentDescription }}.<br>
<br>
Detail Dokumen yang Dibagikan:<br>
@if(!empty($documentLists))
  @foreach ($documentLists as $index => $detail)
  {{ $index + 1 }}. {{ $detail }}<br>
  @endforeach
@endif

Akses Dokumen Resmi: Untuk memastikan Anda selalu menggunakan versi yang terbaru, <br>

mohon akses dokumen melalui tautan resmi berikut:<br>
[Link Dokumen di Website Document Control Anda]

<br>
Mohon untuk tidak menyimpan salinan dokumen ini di luar folder yang telah ditentukan untuk menghindari penggunaan versi yang usang (obsolete).

Terima kasih atas kerja samanya dalam menjaga kepatuhan standar.

Hormat kami,

{{ $creatorName }}

Thank you.<br>
{{ config('app.name') }}
@endcomponent
