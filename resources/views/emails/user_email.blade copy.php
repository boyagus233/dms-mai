@component('mail::message')
# You've received a message
Hi Approver Name/Checker Name,

{{ $creatorName }} has just uploaded a new document ({{ $documentName }}) under the {{ $documentType }} category.

Kindly log in and complete the necessary check/approval action on the system as soon as possible.

Link to document: [Link ke dokumen]

Thank you.


Thank you.<br>
{{ config('app.name') }}
@endcomponent
