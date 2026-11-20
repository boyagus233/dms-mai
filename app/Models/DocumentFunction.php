<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentFunction extends Model
{
  public $table = "m_document_function";
  public $timestamps = true;

  protected $fillable = [
    'code',
    'name',
    'status',
  ];

  public function scopeActive($query)
  {
      return $query->where('status', true); 
  }
}
