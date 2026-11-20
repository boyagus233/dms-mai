<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductRealizationProcess extends Model
{
  public $table = "m_product_realization_process";
  public $timestamps = true;

  protected $fillable = [
    'code',
    'name',
    'status',
  ];
}
