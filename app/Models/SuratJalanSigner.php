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
        'group_departement_id',
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

    public function groupDepartement()
    {
        return $this->belongsTo(Departement::class, 'group_departement_id');
    }

    /** Kotak milik grup departemen (mis. Pemeriksa = semua member HR-GA). */
    public function isGroup(): bool
    {
        return !empty($this->group_departement_id);
    }

    /** Kotak grup yang belum dieksekusi siapa pun (belum ada user_id). */
    public function isOpenGroup(): bool
    {
        return $this->isGroup() && empty($this->user_id);
    }

    /**
     * Kotak yang dapat ditangani $user: ditujukan langsung ke dia, atau kotak grup
     * yang belum diambil siapa pun dan departemennya = departemen utama $user.
     * Aturan "1 orang hanya 1 kotak per Surat Jalan" dicek terpisah di service.
     */
    public function scopeActionableBy($query, User $user)
    {
        $deptId = $user->employee?->departement;

        return $query->where(function ($q) use ($user, $deptId) {
            $q->where('user_id', $user->id);
            if ($deptId) {
                $q->orWhere(fn ($g) => $g->whereNull('user_id')->where('group_departement_id', $deptId));
            }
        });
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
