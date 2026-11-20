@component('mail::message')
# You've received a message

{{ $messageText }}

Thanks,<br>
{{ config('app.name') }}
@endcomponent
