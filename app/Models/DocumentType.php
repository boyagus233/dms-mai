<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentType extends Model
{

  public $table = "m_document_type";
  public $timestamps = true;

  protected $fillable = [
    'code',
    'document_type',
    'status',
  ];

  public function scopeActive($query)
  {
      return $query->where('status', true); // or 1 if stored as integer
  }
}
