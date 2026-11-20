<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentSignatureField extends Model
{
    use HasFactory;

    protected $table = 'document_signature_fields';

    protected $fillable = [
        'document_id',
        'document_signer_id',
        'field_type',
        'page_number',
        'x_percent',
        'y_percent',
        'width_percent',
        'height_percent',
    ];

    protected $casts = [
        'page_number' => 'integer',
        'x_percent' => 'float',
        'y_percent' => 'float',
        'width_percent' => 'float',
        'height_percent' => 'float',
    ];

    public function document()
    {
        return $this->belongsTo(AdhocDocument::class, 'document_id');
    }

    public function signer()
    {
        return $this->belongsTo(DocumentSigner::class, 'document_signer_id');
    }
}
