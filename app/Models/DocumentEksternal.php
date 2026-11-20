<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentEksternal extends Model
{
  public $table = "trx_document_eksternal";
  public $timestamps = true;
  protected $fillable = [
      'document_eksternal_type',
      'document_no',
      'document_name',
      'revisi',
      'status',
      'file_name'
  ];

  public function createdBy()
  {
      return $this->belongsTo(User::class, 'created_by');
  }
  public function updatedBy()
  {
      return $this->belongsTo(User::class, 'updated_by');
  }
}
