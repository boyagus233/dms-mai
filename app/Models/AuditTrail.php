<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditTrail extends Model
{
  public $table = "tr_audit_trails";
  public $timestamps = true;

  protected $fillable = [
    'document_id',
    'document_no',
    'document_name',
    'action',
    'employee_id',
    'employee_name',
    'departement_id',
    'departement_name',
  ];

  public function user()
  {
      return $this->belongsTo(User::class, 'user_id');
  }
  
}
