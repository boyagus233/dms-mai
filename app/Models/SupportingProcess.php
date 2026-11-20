<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupportingProcess extends Model
{
  public $table = "m_supporting_process";
  public $timestamps = true;

  protected $fillable = [
    'code',
    'name',
    'status',
  ];
}
