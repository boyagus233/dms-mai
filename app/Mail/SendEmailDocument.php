<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SendEmailDocument extends Mailable
{
    use Queueable, SerializesModels;

    public $subjectText;
    public $documentLists;
    public $documentDescription;
    public $creatorName;
    public $attachmentPath;

    public function __construct($subjectText, $documentLists, $documentDescription, $creatorName, $attachmentPath = null)
    {
        $this->subjectText = $subjectText;
        $this->documentLists = $documentLists;
        $this->documentDescription = $documentDescription;
        $this->creatorName = $creatorName;
        $this->attachmentPath = $attachmentPath;
    }

    public function build()
    {
        $email = $this->subject($this->subjectText)
                      ->markdown('emails.document_email')
                      ->with([
                          'documentLists' => $this->documentLists,
                          'documentDescription' => $this->documentDescription,
                          'creatorName' => $this->creatorName,
                      ]);

        if ($this->attachmentPath) {
            $email->attach($this->attachmentPath);
        }

        return $email;
    }
}
