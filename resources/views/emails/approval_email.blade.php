@component('mail::message')

@if ($isSendToCreator)

{{ $creatorName }},

{{ $approverName }} has approved your document ({{ $documentName }}) in the {{ $documentType }} category.

Please log in to the system to check the document.

@else
Hi {{ $approverName ?? 'Approver Name/Checker Name' }},

{{ $creatorName }} has just uploaded a new document ({{ $documentName }}) under the {{ $documentType }} category.

Kindly log in and complete the necessary check/approval action on the system as soon as possible.

@endif

@component('mail::button', ['url' => $documentLink])
View Document
@endcomponent

Thank you.<br>
{{ config('app.name') }}
@endcomponent
