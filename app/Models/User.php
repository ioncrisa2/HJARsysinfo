<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use Filament\Panel;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Notifications\Notifiable;
use Filament\Models\Contracts\FilamentUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Jeffgreco13\FilamentBreezy\Traits\TwoFactorAuthenticatable;

class User extends Authenticatable implements FilamentUser
{
    use HasFactory, Notifiable, HasRoles,TwoFactorAuthenticatable;

    protected string $guard_name = 'web';
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'deactivated_at',
        'avatar_url',
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
            'deactivated_at' => 'datetime',
        ];
    }

    public function pembanding()
    {
        return $this->hasMany(Pembanding::class, 'created_by');
    }

    public function getFilamentAvatarUrl(): ?string
    {
        if (! $this->avatar_url) {
            return null;
        }

        return asset('storage/' . $this->avatar_url);
    }

    public function canAccessPanel(Panel $panel): bool
    {
        if ($this->deactivated_at) {
            return false;
        }

        return true;
    }

    public function scopeActive(Builder $query): void
    {
        $query->whereNull('deactivated_at');
    }

    public function scopeInactive(Builder $query): void
    {
        $query->whereNotNull('deactivated_at');
    }
}
