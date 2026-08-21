<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\PhoneNumber;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasAvatar;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser, HasAvatar
{
    use HasFactory, HasRoles, Notifiable, SoftDeletes;

    protected $fillable = [
        'name', 'email', 'password', 'phone', 'avatar',
        'locale', 'provider', 'provider_id', 'is_active',
        'email_verified_at',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Phone numbers are the primary login identifier, so they are always stored
     * in the canonical 01XXXXXXXXX form. Unrecognised input is kept verbatim so
     * admin-entered landlines are not silently discarded.
     */
    public function setPhoneAttribute(?string $value): void
    {
        $value = $value === null ? null : trim($value);

        $this->attributes['phone'] = ($value === null || $value === '')
            ? null
            : (PhoneNumber::normalize($value) ?? $value);
    }

    /** Email is optional; blank input must be stored as NULL to satisfy the unique index. */
    public function setEmailAttribute(?string $value): void
    {
        $value = $value === null ? null : strtolower(trim($value));

        $this->attributes['email'] = ($value === null || $value === '') ? null : $value;
    }

    public function hasEmail(): bool
    {
        return filled($this->email);
    }

    /** Best available human identifier for the account — used in headers and admin lists. */
    public function displayIdentifier(): string
    {
        return $this->phone ?? $this->email ?? '';
    }

    public function getFilamentAvatarUrl(): ?string
    {
        return $this->avatar ? Storage::disk('public')->url($this->avatar) : null;
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole('super_admin');
    }

    /** Thin wrapper kept for backwards-compatibility with HasResourcePermissions and other callers. */
    public function hasPermission(string $permission): bool
    {
        return $this->isSuperAdmin() || $this->hasPermissionTo($permission);
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_active
            && $this->roles()->where('name', '!=', 'customer')->exists();
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(UserAddress::class);
    }

    public function defaultAddress()
    {
        return $this->hasOne(UserAddress::class)->where('is_default', true);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function wishlist(): HasMany
    {
        return $this->hasMany(Wishlist::class);
    }

    public function cartItems(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }
}
