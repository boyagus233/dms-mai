@component('mail::message')

{{ $creatorName }},

{{ $approverName }} has rejected your document ({{ $documentName }}) in the {{ $documentType }} category.

Please log in to the system to check and update the document.

@component('mail::button', ['url' => $documentLink])
View Document
@endcomponent

Thank you.<br>
{{ config('app.name') }}
@endcomponent
