<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentRequest extends Model
{
  public $table = "trx_document_request";
  public $timestamps = true;
  protected $fillable = [
      'document_type',
      'document_classification',
      'document_function',
      'document_parent_no',
      'document_name',
      'document_no',
      'document_status',
      'status',
  ];


  // Relationships

  public function documentType()
  {
      return $this->belongsTo(DocumentType::class, 'document_type');
  }

  public function documentClassification()
  {
      return $this->belongsTo(DocumentClassification::class, 'document_classification');
  }

  public function createdBy()
  {
      return $this->belongsTo(User::class, 'created_by');
  }
  public function updatedBy()
  {
      return $this->belongsTo(User::class, 'updated_by');
  }
}
