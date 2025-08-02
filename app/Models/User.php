<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use App\Mail\CustomVerifyEmail;
use App\Mail\CustomResetPassword;
use Illuminate\Support\Facades\Mail;
use Laravel\Cashier\Billable; 
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Storage;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens, HasRoles, Billable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'avatar',
        'currency',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'email_verification_token',
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
     * Send the email verification notification.
     */
    public function sendEmailVerificationNotification()
    {
        Mail::to($this)->send(new CustomVerifyEmail($this));
    }
    
    /**
     * Send the password reset notification.
     */
    public function sendPasswordResetNotification($token)
    {
        Mail::to($this)->send(new CustomResetPassword($this, $token));
    }

    // Relationships
    public function notificationSettings(): HasMany
    {
        return $this->hasMany(NotificationSetting::class);
    }

    public function favorites(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'user_favorites')->withTimestamps();
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(UserAddress::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function preferredCurrency()
    {
        return $this->belongsTo(Currency::class, 'currency', 'code');
    }

    // Avatar accessor
    public function getAvatarUrlAttribute()
    {
        if ($this->avatar) {
            return Storage::disk('public')->url($this->avatar);
        }
        
        // Return default avatar or gravatar
        return 'https://www.gravatar.com/avatar/' . md5(strtolower(trim($this->email))) . '?d=mp&s=150';
    }

    // Currency methods
    public function getCurrency(): string
    {
        if ($this->currency) {
            return $this->currency;
        }
        
        // Get default currency from database
        $defaultCurrency = Currency::where('is_default', true)->first();
        return $defaultCurrency?->code ?? 'USD';
    }

    public function setCurrency(string $currencyCode): void
    {
        // Validate currency exists and is active
        $currency = Currency::where('code', strtoupper($currencyCode))->active()->first();
        
        if ($currency) {
            $this->update(['currency' => strtoupper($currencyCode)]);
        }
    }

    public function getCurrencySymbol(): string
    {
        $currency = $this->preferredCurrency;
        return $currency ? $currency->symbol : '$';
    }

    public function getCurrencyDetails(): array
    {
        $currency = $this->preferredCurrency;
        
        return [
            'code' => $this->getCurrency(),
            'name' => $currency?->name ?? 'US Dollar',
            'symbol' => $this->getCurrencySymbol(),
        ];
    }

    // Notification methods
    public function hasNotificationEnabled(string $type, string $category): bool
    {
        $setting = $this->notificationSettings()
            ->where('type', $type)
            ->where('category', $category)
            ->first();

        return $setting ? $setting->enabled : true; // Default to enabled
    }

    public function updateNotificationSetting(string $type, string $category, bool $enabled, array $preferences = []): void
    {
        $this->notificationSettings()->updateOrCreate(
            ['type' => $type, 'category' => $category],
            ['enabled' => $enabled, 'preferences' => $preferences]
        );
    }

    // Favorites methods
    public function addToFavorites(Product $product): bool
    {
        if (!$this->favorites()->where('product_id', $product->id)->exists()) {
            $this->favorites()->attach($product->id);
            return true;
        }
        return false;
    }

    public function removeFromFavorites(Product $product): bool
    {
        return $this->favorites()->detach($product->id) > 0;
    }

    public function isFavorite(Product $product): bool
    {
        return $this->favorites()->where('product_id', $product->id)->exists();
    }

    // Address methods
    public function getDefaultAddress(): ?UserAddress
    {
        $query = $this->addresses()->where('is_default', true);
        return $query->first();
    }

    public function setDefaultAddress(UserAddress $address): void
    {
        // Remove default from other addresses of the same type
        $this->addresses()
            ->where('id', '!=', $address->id)
            ->update(['is_default' => false]);

        $address->update(['is_default' => true]);
    }
}
