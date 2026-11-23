<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SuratJalanSigner extends Model
{
    public const STATUS_WAITING = 'WAITING';   // belum giliran
    public const STATUS_PENDING = 'PENDING';   // giliran tanda tangan
    public const STATUS_SIGNED = 'SIGNED';
    public const STATUS_REJECTED = 'REJECTED';

    public const STAMP_IMAGE = 'IMAGE';        // image stamp dari profil employee
    public const STAMP_QR = 'QR';              // QR stamp (fallback bila tidak ada image)
    public const STAMP_PAD = 'PAD';            // digambar via signature pad (slot Diterima)

    public $table = 'trx_surat_jalan_signers';

    protected $fillable = [
        'surat_jalan_id',
        'step',
        'slot',
        'user_id',
        'employee_id',
        'signer_name',
        'signer_position',
        'signer_departement',
        'status',
        'stamp_type',
        'stamp_path',
        'signed_at',
        'ip_address',
        'user_agent',
        'note',
    ];

    protected $casts = [
        'signed_at' => 'datetime',
    ];

    public function suratJalan()
    {
        return $this->belongsTo(SuratJalan::class, 'surat_jalan_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function slotLabel(): string
    {
        return config('surat_jalan.slots.' . $this->step . '.label', $this->slot);
    }

    public function isSigned(): bool
    {
        return $this->status === self::STATUS_SIGNED;
    }

    /** Teks yang di-encode ke QR stamp. */
    public function qrPayload(SuratJalan $sj): string
    {
        return implode("\n", [
            'SURAT JALAN ' . ($sj->sj_no ?? '-'),
            strtoupper($this->slotLabel()) . ': ' . $this->signer_name,
            'Jabatan: ' . ($this->signer_position ?? '-'),
            'Signed: ' . optional($this->signed_at)->format('d-m-Y H:i:s'),
            'Doc ID: ' . $sj->uuid,
        ]);
    }
}
