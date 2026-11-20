<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
  public $table = "m_employee";
  public $timestamps = true;
  protected $fillable = [
      'nik',
      'name',
      'phone_number',
      'dob',
      'departement',
      'position',
      'status',
  ];

  public static function findByNik($nik)
  {
      return static::where('nik', $nik)->first();
  }

  // Relationships

  public function departements()
  {
      return $this->belongsTo(Departement::class, 'departement');
  }

  public function getDepartmentNamesAttribute()
  {
      $ids = explode(',', $this->departement_tags);
      $departments = Departement::whereIn('id', $ids)->pluck('name')->toArray();
      return implode(', ', $departments);
  }

  public function positions()
  {
      return $this->belongsTo(Position::class, 'position');
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
