<?php

namespace App\Models;

use App\Enums\GroupProcess;
use Illuminate\Database\Eloquent\Model;

class DocumentClassification extends Model
{
public $table = "m_document_classification";
  public $timestamps = true;

  protected $fillable = [
    'group_process',
    'code',
    'name',
    'status',
  ];

  protected $casts = [
      'group_process' => GroupProcess::class,
  ];

  public function scopeActive($query)
  {
      return $query->where('status', true); // or 1 if stored as integer
  }

  // public function getGroupProcessDisplayAttribute(): string
  // {
  //     $enum = GroupProcess::tryFrom($this->group_process);

  //     return $enum
  //         ? $enum->value . ' - ' . $enum->label()
  //         : $this->group_process; // fallback if label not found
  // }

  // public function getGroupEnumAttribute(): ?GroupProcess
  // {
  //     return GroupProcess::tryFrom($this->attributes['group_process'] ?? null);
  // }
}
