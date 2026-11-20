<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentSigner extends Model
{
    use HasFactory;

    protected $table = 'document_signers';

    protected $fillable = [
        'document_id',
        'user_id',
        'order_index',
        'role_name',
        'status',
        'signed_at',
        'rejected_at',
        'reject_reason',
        'signature_path',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'order_index' => 'integer',
        'signed_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    public function document()
    {
        return $this->belongsTo(AdhocDocument::class, 'document_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function fields()
    {
        return $this->hasMany(DocumentSignatureField::class, 'document_signer_id');
    }

    public function isPending(): bool
    {
        return $this->status === 'PENDING';
    }

    public function isSigned(): bool
    {
        return $this->status === 'SIGNED';
    }

    public function isRejected(): bool
    {
        return $this->status === 'REJECTED';
    }
}
