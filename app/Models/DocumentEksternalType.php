<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentEksternalType extends Model
{

  public $table = "m_document_eksternal_type";
  public $timestamps = true;

  protected $fillable = [
    'code',
    'name',
    'created_by',
  ];

  public function setCodeAttribute($value)
  {
      $this->attributes['code'] = strtoupper($value);
  }

}
