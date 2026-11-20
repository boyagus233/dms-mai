<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ManagementProcess extends Model
{
  public $table = "m_management_process";
  public $timestamps = true;

  protected $fillable = [
    'code',
    'name',
    'status',
  ];

  public function scopeActive($query)
  {
      return $query->where('status', true); // or 1 if stored as integer
  }
}
