<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Position extends Model
{
  public $table = "m_position";
  public $timestamps = true;

  protected $fillable = [
    'code',
    'name',
    'status',
  ];
}
