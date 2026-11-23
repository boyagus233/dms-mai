<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class AdhocDocument extends Model
{
    use HasFactory;

    public const DOCUMENT_TYPE = 'ADHOC';

    protected $table = 'documents';

    protected $fillable = [
        'uuid',
        'title',
        'description',
        'original_file_name',
        'original_file_path',
        'signed_file_path',
        'original_file_hash',
        'signed_file_hash',
        'file_size',
        'page_count',
        'workflow_type',
        'status',
        'created_by',
    ];

    protected $casts = [
        'file_size' => 'integer',
        'page_count' => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function signers()
    {
        return $this->hasMany(DocumentSigner::class, 'document_id')->orderBy('order_index', 'asc');
    }

    public function fields()
    {
        return $this->hasMany(DocumentSignatureField::class, 'document_id');
    }

    public function auditLogs()
    {
        // document_audit_logs dipakai bersama beberapa modul (ADHOC, SURAT_JALAN, ...),
        // jadi wajib difilter per document_type agar log modul lain dengan id sama tidak ikut.
        return $this->hasMany(DocumentAuditLog::class, 'document_id')
            ->where(fn ($q) => $q->where('document_type', self::DOCUMENT_TYPE)->orWhereNull('document_type'))
            ->orderBy('created_at', 'asc')
            ->orderBy('id', 'asc');
    }

    public function isCompleted(): bool
    {
        return $this->status === 'COMPLETED';
    }

    public function isRejected(): bool
    {
        return $this->status === 'REJECTED';
    }

    public function isInProgress(): bool
    {
        return $this->status === 'IN_PROGRESS';
    }
}
