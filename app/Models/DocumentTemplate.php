<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentTemplate extends Model
{

  public $table = "m_document_template";
  public $timestamps = true;

  protected $fillable = [
    'document_name',
    'description',
    'file_name',
    'created_by',
  ];

}
