<?php
// FILE: app/Services/V1/UserProfileService.php

namespace App\Services\V1\User;

use App\Models\User;
use App\Models\Product;
use App\Models\UserAddress;
use App\Models\NotificationSetting;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class UserProfileService
{
    public function updateProfile(User $user, array $data): array
    {
        $user->update($data);

        return [
            'data' => $user->fresh(),
            'message' => 'Profile updated successfully'
        ];
    }

    public function uploadAvatar(User $user, UploadedFile $avatar): array
    {
        // Delete old avatar
        if ($user->avatar) {
            Storage::disk('public')->delete($user->avatar);
        }

        // Generate unique filename
        $filename = 'avatars/' . $user->id . '_' . time() . '.' . $avatar->getClientOriginalExtension();
        
        // Store new avatar
        $path = $avatar->storeAs('avatars', basename($filename), 'public');
        
        // Update user avatar
        $user->update(['avatar' => $path]);

        return [
            'data' => [
                'avatar_url' => $user->avatar_url,
                'avatar_path' => $path
            ],
            'message' => 'Avatar updated successfully'
        ];
    }

    public function deleteAvatar(User $user): array
    {
        if ($user->avatar) {
            Storage::disk('public')->delete($user->avatar);
            $user->update(['avatar' => null]);
        }

        return [
            'data' => [
                'avatar_url' => $user->avatar_url, // Will return gravatar
            ],
            'message' => 'Avatar deleted successfully'
        ];
    }

    public function getUserFavorites(User $user): array
    {
        $favorites = $user->favorites()
            ->with(['categories'])
            ->active()
            ->published()
            ->paginate(15);

        return [
            'data' => $favorites,
            'message' => 'Favorites retrieved successfully'
        ];
    }

    public function addToFavorites(User $user, Product $product): array
    {
        if ($user->addToFavorites($product)) {
            return [
                'data' => ['is_favorite' => true],
                'message' => 'Product added to favorites'
            ];
        }

        throw ValidationException::withMessages([
            'product' => ['Product is already in favorites.']
        ]);
    }

    public function removeFromFavorites(User $user, Product $product): array
    {
        if ($user->removeFromFavorites($product)) {
            return [
                'data' => ['is_favorite' => false],
                'message' => 'Product removed from favorites'
            ];
        }

        throw ValidationException::withMessages([
            'product' => ['Product is not in favorites.']
        ]);
    }

    public function getUserAddresses(User $user): array
    {
        $addresses = $user->addresses()->orderBy('is_default', 'desc')->get();

        return [
            'data' => $addresses,
            'message' => 'Addresses retrieved successfully'
        ];
    }

    public function createAddress(User $user, array $data): array
    {
        $address = $user->addresses()->create($data);

        return [
            'data' => $address,
            'message' => 'Address created successfully'
        ];
    }

    public function updateAddress(UserAddress $address, array $data): array
    {
        $address->update($data);

        return [
            'data' => $address->fresh(),
            'message' => 'Address updated successfully'
        ];
    }

    public function deleteAddress(UserAddress $address): array
    {
        $wasDefault = $address->is_default;
        $addressType = $address->type;
        $userId = $address->user_id;
        
        $address->delete();

        // If deleted address was default, set another address as default
        if ($wasDefault) {
            $nextAddress = UserAddress::where('user_id', $userId)->first();
            
            if ($nextAddress) {
                $nextAddress->update(['is_default' => true]);
            }
        }

        return [
            'data' => null,
            'message' => 'Address deleted successfully'
        ];
    }

    public function setDefaultAddress(UserAddress $address): array
    {
        $address->user->setDefaultAddress($address);

        return [
            'data' => $address->fresh(),
            'message' => 'Default address updated successfully'
        ];
    }

    public function getNotificationSettings(User $user): array
    {
        $settings = $user->notificationSettings()->get();
        
        // Create default settings for missing categories
        $defaultSettings = [];
        foreach (NotificationSetting::TYPES as $type => $typeLabel) {
            foreach (NotificationSetting::CATEGORIES as $category => $categoryLabel) {
                $existing = $settings->where('type', $type)
                    ->where('category', $category)
                    ->first();
                
                $defaultSettings[] = [
                    'type' => $type,
                    'type_label' => $typeLabel,
                    'category' => $category,
                    'category_label' => $categoryLabel,
                    'enabled' => $existing ? $existing->enabled : true,
                    'preferences' => $existing ? $existing->preferences : [],
                ];
            }
        }

        return [
            'data' => $defaultSettings,
            'message' => 'Notification settings retrieved successfully'
        ];
    }

    public function updateNotificationSettings(User $user, array $settings): array
    {
        foreach ($settings as $setting) {
            $user->updateNotificationSetting(
                $setting['type'],
                $setting['category'],
                $setting['enabled'],
                $setting['preferences'] ?? []
            );
        }

        return [
            'data' => null,
            'message' => 'Notification settings updated successfully'
        ];
    }
}
