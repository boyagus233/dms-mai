<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vendor extends Model
{
    use HasFactory;

    public $table = 'm_vendor';

    protected $fillable = [
        'code',
        'name',
        'official_name',
        'address',
        'postal_code',
        'phone',
        'fax',
        'pic_name',
        'email',
        'notes',
        'is_supplier',
        'is_customer',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_supplier' => 'boolean',
        'is_customer' => 'boolean',
        'status' => 'integer',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }
}
