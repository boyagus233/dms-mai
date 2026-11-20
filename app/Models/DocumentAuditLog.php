<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use LogicException;

/**
 * Log kronologis & immutable untuk seluruh modul e-sign.
 * Baris yang sudah tercatat tidak boleh diubah / dihapus.
 */
class DocumentAuditLog extends Model
{
    public $table = 'document_audit_logs';

    // Hanya created_at, tanpa updated_at (immutable).
    const UPDATED_AT = null;

    protected $fillable = [
        'document_type',
        'document_id',
        'action',
        'description',
        'user_id',
        'actor_name',
        'actor_email',
        'actor_position',
        'ip_address',
        'user_agent',
        'metadata',
        'created_at',
    ];

    protected $casts = [
        'metadata'   => 'array',
        'created_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::updating(function () {
            throw new LogicException('document_audit_logs bersifat immutable dan tidak boleh diubah.');
        });

        static::deleting(function () {
            throw new LogicException('document_audit_logs bersifat immutable dan tidak boleh dihapus.');
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** Relasi ke AdhocDocument (dipakai modul adhoc). */
    public function adhocDocument()
    {
        return $this->belongsTo(AdhocDocument::class, 'document_id');
    }
}
