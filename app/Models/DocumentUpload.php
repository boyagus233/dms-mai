<?php

namespace App\Models;
use App\Enums\DocumentStatus;
use Illuminate\Database\Eloquent\Model;

class DocumentUpload extends Model
{
  public $table = "trx_document_upload";
  public $timestamps = true;
  protected $fillable = [
      'document_level',
      'management_process',
      'document_no_lvl_1',
      'document_no',
      'document_name',
      'assign_to',
      'document_status',
  ];

  protected $casts = [
      'document_status' => DocumentStatus::class,
  ];

  public static function findByNik($nik)
  {
      return static::where('nik', $nik)->first();
  }

  // Relationships

  public function documentType()
  {
      return $this->belongsTo(DocumentType::class, 'document_type');
  }
  public function documentLevel()
  {
      return $this->belongsTo(DocumentLevel::class, 'document_level');
  }

  public function ManagementProcess()
  {
      return $this->belongsTo(ManagementProcess::class, 'management_process');
  }
  public function processMapping()
  {
      return $this->belongsTo(ProcessMapping::class, 'process_code');
  }
  public function departements()
  {
      return $this->belongsTo(Departement::class, 'departement');
  }

  public function employee()
  {
      return $this->belongsTo(Employee::class, 'assign_to');
  }

  public function employee2nd()
  {
      return $this->belongsTo(Employee::class, 'assign_to_2');
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
