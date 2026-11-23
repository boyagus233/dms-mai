<?php

namespace App\Mail;

use App\Models\SuratJalan;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SuratJalanNotification extends Mailable
{
    use Queueable, SerializesModels;

    public const TYPE_SIGN_REQUEST = 'SIGN_REQUEST';
    public const TYPE_COMPLETED = 'COMPLETED';
    public const TYPE_REJECTED = 'REJECTED';

    public function __construct(
        public string $type,
        public SuratJalan $suratJalan,
        public string $recipientName,
        public string $documentLink,
        public ?string $slotLabel = null,
        public ?string $actorName = null,
        public ?string $reason = null,
    ) {
    }

    public function build()
    {
        $no = $this->suratJalan->sj_no;

        $subject = match ($this->type) {
            self::TYPE_SIGN_REQUEST => "[Surat Jalan] Permintaan Tanda Tangan ({$this->slotLabel}) - {$no}",
            self::TYPE_COMPLETED    => "[Surat Jalan] Selesai / Completed - {$no}",
            self::TYPE_REJECTED     => "[Surat Jalan] Ditolak / Rejected - {$no}",
            default                 => "[Surat Jalan] {$no}",
        };

        return $this->subject($subject)->markdown('emails.surat_jalan', [
            'type'          => $this->type,
            'sj'            => $this->suratJalan,
            'recipientName' => $this->recipientName,
            'documentLink'  => $this->documentLink,
            'slotLabel'     => $this->slotLabel,
            'actorName'     => $this->actorName,
            'reason'        => $this->reason,
        ]);
    }
}
