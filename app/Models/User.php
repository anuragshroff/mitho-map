<?php

namespace App\Models;

use App\Enums\UserRole;
use App\Enums\VehicleType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'role',
        'phone_verified_at',
        'vehicle_type',
        'is_available',
        'expo_push_token',
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
            'phone_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'vehicle_type' => VehicleType::class,
            'is_available' => 'boolean',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    public function defaultAddress(): HasOne
    {
        return $this->hasOne(UserAddress::class)->where('is_default', true);
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(UserAddress::class);
    }

    public function socialAccounts(): HasMany
    {
        return $this->hasMany(SocialAccount::class);
    }

    public function restaurantsOwned(): HasMany
    {
        return $this->hasMany(Restaurant::class, 'owner_id');
    }

    public function customerOrders(): HasMany
    {
        return $this->hasMany(Order::class, 'customer_id');
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(Order::class, 'driver_id');
    }

    public function trackingUpdates(): HasMany
    {
        return $this->hasMany(DeliveryTrackingUpdate::class, 'driver_id');
    }

    public function latestTrackingUpdate(): HasOne
    {
        return $this->hasOne(DeliveryTrackingUpdate::class, 'driver_id')
            ->latestOfMany('recorded_at');
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class, 'updated_by');
    }

    public function stories(): HasMany
    {
        return $this->hasMany(Story::class, 'created_by');
    }

    public function sentOrderChatMessages(): HasMany
    {
        return $this->hasMany(OrderChatMessage::class, 'sender_id');
    }

    /**
     * Get the preferences associated with the user.
     */
    public function preferences(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(UserPreference::class);
    }

    public function favoriteRestaurants(): BelongsToMany
    {
        return $this->belongsToMany(Restaurant::class, 'user_favorite_restaurants')
            ->withTimestamps();
    }

    public function favoriteRestaurantPivots(): HasMany
    {
        return $this->hasMany(UserFavoriteRestaurant::class);
    }

    public function paymentMethods(): HasMany
    {
        return $this->hasMany(UserPaymentMethod::class);
    }
}
