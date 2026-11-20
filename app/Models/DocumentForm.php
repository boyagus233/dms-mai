<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentForm extends Model
{

  public $table = "m_document_form";
  public $timestamps = true;

  protected $fillable = [
    'name',
    'status',
  ];

  public function scopeActive($query)
  {
      return $query->where('status', true); // or 1 if stored as integer
  }

}
