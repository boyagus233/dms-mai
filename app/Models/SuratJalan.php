<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SuratJalan extends Model
{
    public const DOCUMENT_TYPE = 'SURAT_JALAN';

    public const STATUS_DRAFT = 'DRAFT';
    public const STATUS_IN_PROGRESS = 'IN_PROGRESS';
    public const STATUS_COMPLETED = 'COMPLETED';
    public const STATUS_REJECTED = 'REJECTED';

    public $table = 'trx_surat_jalan';

    protected $fillable = [
        'uuid',
        'sj_no',
        'departement_id',
        'location',
        'sj_date',
        'kepada_tipe',
        'kepada',
        'no_kendaraan',
        'kategori',
        'kategori_lain',
        'status',
        'current_step',
        'rejected_reason',
        'rejected_by',
        'rejected_at',
        'submitted_at',
        'completed_at',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'kategori'     => 'array',
        'sj_date'      => 'date',
        'rejected_at'  => 'datetime',
        'submitted_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function items()
    {
        return $this->hasMany(SuratJalanItem::class, 'surat_jalan_id')->orderBy('line_no');
    }

    public function signers()
    {
        return $this->hasMany(SuratJalanSigner::class, 'surat_jalan_id')->orderBy('step');
    }

    public function departement()
    {
        return $this->belongsTo(Departement::class, 'departement_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function rejecter()
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function auditLogs()
    {
        return $this->hasMany(DocumentAuditLog::class, 'document_id')
            ->where('document_type', self::DOCUMENT_TYPE)
            ->orderBy('created_at')
            ->orderBy('id');
    }

    /**
     * Surat Jalan yang sedang menunggu tanda tangan $user:
     * kotak aktif ditujukan langsung ke dia, atau kotak grup (mis. Pemeriksa HR-GA) milik
     * departemennya yang belum diambil siapa pun — kecuali ia sudah menandatangani kotak lain
     * di Surat Jalan yang sama (1 orang = 1 kotak).
     */
    public function scopeAwaitingActionBy($query, User $user)
    {
        $deptId = $user->employee?->departement;

        return $query->where('status', self::STATUS_IN_PROGRESS)
            ->where(function ($w) use ($user, $deptId) {
                $w->whereHas('signers', fn ($q) => $q->where('status', SuratJalanSigner::STATUS_PENDING)->where('user_id', $user->id));

                if ($deptId) {
                    $w->orWhere(fn ($g) => $g
                        ->whereHas('signers', fn ($q) => $q->where('status', SuratJalanSigner::STATUS_PENDING)
                            ->whereNull('user_id')->where('group_departement_id', $deptId))
                        ->whereDoesntHave('signers', fn ($q) => $q->where('user_id', $user->id)
                            ->where('status', SuratJalanSigner::STATUS_SIGNED)));
                }
            });
    }

    public function signerForStep(int $step): ?SuratJalanSigner
    {
        return $this->signers->firstWhere('step', $step);
    }

    public function currentSigner(): ?SuratJalanSigner
    {
        return $this->signers->firstWhere('status', SuratJalanSigner::STATUS_PENDING);
    }

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function statusLabel(): string
    {
        return [
            self::STATUS_DRAFT       => 'Draft',
            self::STATUS_IN_PROGRESS => 'In Progress',
            self::STATUS_COMPLETED   => 'Completed',
            self::STATUS_REJECTED    => 'Rejected',
        ][$this->status] ?? $this->status;
    }

    public function statusBadgeClass(): string
    {
        return [
            self::STATUS_DRAFT       => 'bg-label-secondary',
            self::STATUS_IN_PROGRESS => 'bg-label-warning',
            self::STATUS_COMPLETED   => 'bg-label-success',
            self::STATUS_REJECTED    => 'bg-label-danger',
        ][$this->status] ?? 'bg-label-secondary';
    }

    public function displayNumber(): string
    {
        return $this->sj_no ?: '(nomor terbit saat submit)';
    }
}
