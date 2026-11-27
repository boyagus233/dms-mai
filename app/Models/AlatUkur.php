<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Storage;

class AlatUkur extends Model
{
    use HasFactory;

    protected $table = 'm_alat_ukur';

    public const STATUS_OPTIONS = [
        'ACTIVE'           => ['label' => 'Active', 'cls' => 'bg-success text-white'],
        'PENDING_APPROVAL' => ['label' => 'Pending Approval', 'cls' => 'bg-warning text-dark'],
        'OUT_OF_PREMISE'   => ['label' => 'Di Vendor (Out)', 'cls' => 'bg-info text-white'],
        'BROKEN'           => ['label' => 'Broken / Rusak', 'cls' => 'bg-danger text-white'],
        'RETIRED'          => ['label' => 'Retired / Non-Aktif', 'cls' => 'bg-secondary text-white'],
    ];

    protected $fillable = [
        'asset_code',
        'name',
        'category_type',
        'standard_spec',
        'brand_model',
        'serial_number',
        'calibrator_ref',
        'location',
        'calibration_interval_months',
        'last_calibration_date',
        'next_calibration_date',
        'current_status',
        'photo_path',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'last_calibration_date' => 'date',
        'next_calibration_date' => 'date',
        'calibration_interval_months' => 'integer',
    ];

    /* ------------------------------------------------------------------ */
    /*  Scopes                                                             */
    /* ------------------------------------------------------------------ */

    public function scopeActive($query)
    {
        return $query->where('current_status', 'ACTIVE');
    }

    public function scopeOutOfPremise($query)
    {
        return $query->where('current_status', 'OUT_OF_PREMISE');
    }

    public function scopePendingApproval($query)
    {
        return $query->where('current_status', 'PENDING_APPROVAL');
    }

    public function scopeSortUpcoming($query)
    {
        return $query->orderByRaw('next_calibration_date IS NULL ASC, next_calibration_date ASC');
    }

    /* ------------------------------------------------------------------ */
    /*  Accessors                                                          */
    /* ------------------------------------------------------------------ */

    public function getStatusMetaAttribute(): array
    {
        return self::STATUS_OPTIONS[$this->current_status] ?? [
            'label' => $this->current_status ?: '-',
            'cls'   => 'bg-label-secondary',
        ];
    }

    public function getPhotoUrlAttribute(): ?string
    {
        if (!$this->photo_path) {
            return null;
        }
        $normalized = ltrim($this->photo_path, '/');
        if (Storage::disk('public')->exists($normalized)) {
            return route('alat-ukur.photo', $this->id);
        }
        return null;
    }

    /**
     * Get due status metadata: urgency, days difference, badge CSS classes.
     */
    public function getDueMetaAttribute(): array
    {
        if (!$this->next_calibration_date) {
            return [
                'diff_days' => 999,
                'label'     => 'Belum Terjadwal',
                'badge_cls' => 'bg-label-secondary',
                'is_urgent' => false,
            ];
        }

        $today = Carbon::today();
        $due = Carbon::parse($this->next_calibration_date)->startOfDay();
        $diff = $today->diffInDays($due, false); // negative if overdue

        if ($diff < 0) {
            return [
                'diff_days' => $diff,
                'label'     => 'Lewat ' . abs($diff) . ' hari',
                'badge_cls' => 'bg-danger text-white',
                'is_urgent' => true,
            ];
        }

        if ($diff === 0) {
            return [
                'diff_days' => 0,
                'label'     => 'Jatuh Tempo Hari Ini',
                'badge_cls' => 'bg-danger text-white',
                'is_urgent' => true,
            ];
        }

        if ($diff <= 30) {
            return [
                'diff_days' => $diff,
                'label'     => $diff . ' hari lagi',
                'badge_cls' => 'bg-warning text-dark',
                'is_urgent' => true,
            ];
        }

        return [
            'diff_days' => $diff,
            'label'     => $diff . ' hari lagi',
            'badge_cls' => 'bg-label-success',
            'is_urgent' => false,
        ];
    }

    /* ------------------------------------------------------------------ */
    /*  Relationships                                                      */
    /* ------------------------------------------------------------------ */

    public function kalibrasiRecords(): HasMany
    {
        return $this->hasMany(Kalibrasi::class, 'alat_ukur_id')->orderBy('calibration_date', 'desc');
    }

    public function latestKalibrasi(): HasOne
    {
        return $this->hasOne(Kalibrasi::class, 'alat_ukur_id')->latestOfMany('calibration_date');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
