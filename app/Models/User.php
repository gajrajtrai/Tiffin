<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Spatie\Activitylog\Models\Concerns\HasActivity;
use Spatie\Permission\Traits\HasRoles;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'email', 'mobile', 'password', 'low_balance_threshold', 'status'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, PasskeyAuthenticatable, TwoFactorAuthenticatable, HasRoles, HasActivity;

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
        'mobile_verified_at' => 'datetime',
        'wallet_balance' => 'decimal:2',
        'low_balance_threshold' => 'decimal:2',
    ];
}

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        $initials = Str::initials($this->name, true);

        return Str::length($initials) > 1
            ? Str::substr($initials, 0, 1).Str::substr($initials, -1)
            : $initials;
    }
    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function orders(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Modules\Order\Models\Order::class);
    }

    public function walletTransactions(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Modules\Payment\Models\WalletTransaction::class);
    }

    public function paymentProofs(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Modules\Payment\Models\PaymentProof::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Role / status helpers
    |--------------------------------------------------------------------------
    */

    public function isAdmin(): bool
    {
        return $this->hasRole('Admin');
    }

    public function isManager(): bool
    {
        return $this->hasRole('Manager');
    }

    public function isStaff(): bool
    {
        return $this->hasAnyRole(['Admin', 'Manager', 'Kitchen Staff', 'Delivery Staff']);
    }

    public function isCustomer(): bool
    {
        return $this->hasRole('Customer');
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function hasLowBalance(): bool
    {
        return $this->wallet_balance < $this->low_balance_threshold;
    }
}