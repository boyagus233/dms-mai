<?php

namespace App\Models;

use App\Enums\ProcessType;
use Illuminate\Database\Eloquent\Model;

class ProcessMapping extends Model
{
  public $table = "m_process_mapping";
  public $timestamps = true;

  protected $casts = [
    'process_type' => ProcessType::class,
  ];
  protected $fillable = [
    'process_type',
    'code',
    'name',
    'status',
  ];
}
