<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
  use HasFactory, Notifiable;

  /**
   * The attributes that are mass assignable.
   *
   * @var array<int, string>
   */
  protected $fillable = [
    'user_name',
    'name',
    'email',
    'password',
  ];

  /**
   * The attributes that should be hidden for serialization.
   *
   * @var array<int, string>
   */
  protected $hidden = [
    'password',
    'remember_token',
  ];

  /**
   * Get the attributes that should be cast.
   *
   * @return array<string, string>
   */
  protected function casts(): array
  {
    return [
      'email_verified_at' => 'datetime',
      'password' => 'hashed',
    ];
  }

  public function employee()
  {
      return $this->belongsTo(Employee::class, 'employee_id');
  }

  public function roles()
  {
      return $this->belongsToMany(Role::class, 'm_user_role', 'user_id', 'role_id');
  }

  public function hasRole($role)
  {
      return $this->roles->contains('code', $role);
  }

  public function hasAnyRole(array $roles)
  {
      return $this->roles->pluck('code')->intersect($roles)->isNotEmpty();
  }
}
