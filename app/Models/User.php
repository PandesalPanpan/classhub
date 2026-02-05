<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser, MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
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

    /**
     * Control which users can access which Filament panels.
     *
     * Panel IDs in this app:
     * - 'admin' panel: accessible to Class Representative, Admin, and Superadmin.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        if ($panel->getId() === 'admin') {
            return $this->hasAnyRole([
                'Admin',
                'Superadmin',
            ]);
        }

        if ($panel->getId() === 'app') {
            return $this->hasAnyRole([
                'Class Representative',
                'Admin',
                'Superadmin',
            ]);
        }

        return false;
    }

    public function requestedSchedules(): HasMany
    {
        return $this->hasMany(Schedule::class, 'requester_id');
    }

    protected static function booted(): void
    {
        static::created(function (User $user) {
            if ($user->roles->isNotEmpty()) {
                return;
            }
            $user->assignRole('Class Representative');
        });
    }
}
