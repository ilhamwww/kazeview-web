<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Filament\Models\Contracts\FilamentUser;

class User extends Authenticatable implements FilamentUser
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
        'email_verified_at',
        'subscription_expires_at',
        'is_approved',
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
            'subscription_expires_at' => 'datetime',
            'password' => 'hashed',
            'is_approved' => 'boolean',
        ];
    }

    public function loginHistories(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(LoginHistory::class)->orderBy('login_at', 'desc');
    }

    public function favorites(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Favorite::class)->orderBy('created_at', 'desc');
    }

    public function watchHistories(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(WatchHistory::class)->orderBy('updated_at', 'desc');
    }

    /**
     * Check if the user's subscription has expired.
     */
    public function isSubscriptionExpired(): bool
    {
        if (is_null($this->subscription_expires_at)) {
            return false; // No expiry set = unlimited
        }

        return $this->subscription_expires_at->isPast();
    }

    /**
     * Check if the user has an active subscription.
     */
    public function hasActiveSubscription(): bool
    {
        return !$this->isSubscriptionExpired();
    }

    public function canAccessPanel(\Filament\Panel $panel): bool
    {
        return true;
    }

    public function canImpersonate()
    {
        return $this->hasRole('super_admin');
    }
}
