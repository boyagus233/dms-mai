<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LogResetPasswordToken extends Model
{
  public $table = "log_reset_password_token";
  public $timestamps = true;
  protected $fillable = [
      'email',
      'consumed',
      'expired',
      'expiry_date',
      'token'
  ];
}
