<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, TwoFactorAuthenticatable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'natricule',
        'firstname',
        'lastname',
        'name',
        'gender',
        'telephone',
        'profile',
        'email',
        'address',
        'school_id',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
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
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    /**
     * Check if user has a specific role among multiple roles.
     */
    public function hasAnyRole(array $roles): bool
    {
        return $this->hasAnyRole($roles);
    }

    /**
     * Get the school this user belongs to.
     */
    public function school()
    {
        return $this->belongsTo(School::class);
    }

    /**
     * Check if user is an administrator.
     */
    public function isAdministrator(): bool
    {
        return $this->hasRole('administrateur');
    }

    /**
     * Check if user is a teacher.
     */
    public function isTeacher(): bool
    {
        return $this->hasRole('enseignant');
    }

    /**
     * Check if user is accounting.
     */
    public function isAccounting(): bool
    {
        return $this->hasRole('comptabilité');
    }

    /**
     * Check if user is secretariat.
     */
    public function isSecretariat(): bool
    {
        return $this->hasRole('secrétariat');
    }

    /**
     * Check if user is a director.
     */
    public function isDirector(): bool
    {
        return $this->hasRole('directeur');
    }
}
