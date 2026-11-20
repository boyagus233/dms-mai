<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    public $table = "m_role";
    public $timestamps = true;
    use HasFactory;
    protected $fillable = [
        'code',
        'name',
        'status',
        'created_by',
        'updated_by',
    ];

    // Relationships
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function scopeActive($query)
    {
        return $query->where('status', true); // or 1 if stored as integer
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'm_user_role', 'role_id', 'user_id');
    }
}
