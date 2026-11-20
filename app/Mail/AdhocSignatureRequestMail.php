<?php

namespace App\Mail;

use App\Models\AdhocDocument;
use App\Models\DocumentSigner;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AdhocSignatureRequestMail extends Mailable
{
    use Queueable, SerializesModels;

    public $document;
    public $signer;
    public $reviewUrl;

    public function __construct(AdhocDocument $document, DocumentSigner $signer)
    {
        $this->document = $document;
        $this->signer = $signer;
        $this->reviewUrl = route('adhoc-document.review', ['uuid' => $document->uuid]);
    }

    public function build()
    {
        return $this->subject('Signature Request: ' . $this->document->title)
            ->view('emails.adhoc_signature_request');
    }
}
