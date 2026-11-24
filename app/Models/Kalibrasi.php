<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Kalibrasi extends Model
{
    use HasFactory;

    protected $table = 't_kalibrasi';

    protected $fillable = [
        'certificate_number',
        'alat_ukur_id',
        'calibration_type',
        'calibration_date',
        'next_due_date',
        'conclusion',
        'technician_id',
        'approver_id',
        'vendor_id',
        'vendor_name',
        'temperature',
        'humidity',
        'calibrator_used',
        'raw_attachment_pdf',
        'final_merged_pdf',
        'status',
        'rejection_note',
        'approved_at',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'calibration_date' => 'date',
        'next_due_date'    => 'date',
        'approved_at'      => 'datetime',
        'temperature'      => 'decimal:2',
        'humidity'         => 'decimal:2',
    ];

    /* ------------------------------------------------------------------ */
    /*  Scopes                                                             */
    /* ------------------------------------------------------------------ */

    public function scopePendingApproval($query)
    {
        return $query->where('status', 'PENDING_APPROVAL');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'COMPLETED');
    }

    /* ------------------------------------------------------------------ */
    /*  Accessors                                                          */
    /* ------------------------------------------------------------------ */

    public function getConclusionBadgeAttribute(): array
    {
        return match ($this->conclusion) {
            'PASS'           => ['label' => 'Laik Pakai (Pass)', 'cls' => 'bg-success text-white'],
            'PASS_WITH_NOTE' => ['label' => 'Laik dengan Catatan', 'cls' => 'bg-warning text-dark'],
            'FAIL'           => ['label' => 'Tidak Laik (Fail)', 'cls' => 'bg-danger text-white'],
            default          => ['label' => $this->conclusion ?? '-', 'cls' => 'bg-secondary text-white'],
        };
    }

    public function getStatusBadgeAttribute(): array
    {
        return match ($this->status) {
            'PENDING_APPROVAL' => ['label' => 'Menunggu Approval', 'cls' => 'bg-warning text-dark'],
            'COMPLETED'        => ['label' => 'Selesai (Sah)', 'cls' => 'bg-success text-white'],
            'REJECTED'         => ['label' => 'Ditolak', 'cls' => 'bg-danger text-white'],
            default            => ['label' => 'Draft', 'cls' => 'bg-secondary text-white'],
        };
    }

    /* ------------------------------------------------------------------ */
    /*  Relationships                                                      */
    /* ------------------------------------------------------------------ */

    public function alatUkur(): BelongsTo
    {
        return $this->belongsTo(AlatUkur::class, 'alat_ukur_id');
    }

    public function technician(): BelongsTo
    {
        return $this->belongsTo(User::class, 'technician_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_id');
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class, 'vendor_id');
    }
}
