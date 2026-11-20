<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SendEmail extends Mailable
{
    use Queueable, SerializesModels;

    public $subjectText;
    public $creatorName;
    public $approverName;
    public $documentName;
    public $documentType;
    public $documentLink;
    public $isSendToCreator;
    public $messageText;
    public $attachmentPath;

    public function __construct($subjectText, $creatorName, $approverName, $documentName, $documentType, $documentLink, $isSendToCreator, $attachmentPath = null)
    {
        $this->subjectText = $subjectText;
        $this->creatorName = $creatorName;
        $this->approverName = $approverName;
        $this->documentName = $documentName;
        $this->documentType = $documentType;
        $this->documentLink = $documentLink;
        $this->isSendToCreator = $isSendToCreator;
        $this->attachmentPath = $attachmentPath;
    }

    public function build()
    {
        $email = $this->subject($this->subjectText)
                      ->markdown('emails.approval_email')
                      ->with([
                          'creatorName' => $this->creatorName,
                          'approverName' => $this->approverName,
                          'documentName' => $this->documentName,
                          'documentType' => $this->documentType,
                          'documentLink' => $this->documentLink,
                          'isSendToCreator' => $this->isSendToCreator,
                      ]);

        if ($this->attachmentPath) {
            $email->attach($this->attachmentPath);
        }

        return $email;
    }
}
