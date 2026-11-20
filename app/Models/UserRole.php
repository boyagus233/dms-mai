<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserRole extends Model
{
    public $table = "m_user_role";
    public $timestamps = false;
    use HasFactory;
    protected $fillable = [
        'user_id',
        'role_id',
    ];

}
