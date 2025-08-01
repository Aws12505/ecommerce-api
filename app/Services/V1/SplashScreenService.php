<?php
// app/Services/V1/SplashScreenService.php

namespace App\Services\V1;

use App\Models\SplashScreen;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class SplashScreenService
{
    public function getAllSplashScreens(bool $activeOnly = false): Collection
    {
        $query = SplashScreen::with(['creator', 'updater']);

        if ($activeOnly) {
            $query->active()->currentlyValid();
        }

        return $query->ordered()->get();
    }

    public function getActiveSplashScreen(string $userType = 'all'): ?SplashScreen
    {
        return SplashScreen::active()
                          ->currentlyValid()
                          ->byAudience($userType)
                          ->ordered()
                          ->first();
    }

    public function createSplashScreen(array $data, UploadedFile $image, int $userId): SplashScreen
    {
        $imageUrl = $this->uploadImage($image);

        return SplashScreen::create([
            'title' => $data['title'],
            'image_url' => $imageUrl,
            'description' => $data['description'] ?? null,
            'type' => $data['type'],
            'is_active' => $data['is_active'] ?? false,
            'display_duration' => $data['display_duration'] ?? 3,
            'sort_order' => $data['sort_order'] ?? 0,
            'start_date' => $data['start_date'] ?? null,
            'end_date' => $data['end_date'] ?? null,
            'target_audience' => $data['target_audience'] ?? SplashScreen::AUDIENCE_ALL,
            'metadata' => $data['metadata'] ?? null,
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);
    }

    public function updateSplashScreen(SplashScreen $splashScreen, array $data, ?UploadedFile $image, int $userId): SplashScreen
    {
        $updateData = [
            'title' => $data['title'],
            'description' => $data['description'] ?? $splashScreen->description,
            'type' => $data['type'],
            'is_active' => $data['is_active'] ?? $splashScreen->is_active,
            'display_duration' => $data['display_duration'] ?? $splashScreen->display_duration,
            'sort_order' => $data['sort_order'] ?? $splashScreen->sort_order,
            'start_date' => $data['start_date'] ?? $splashScreen->start_date,
            'end_date' => $data['end_date'] ?? $splashScreen->end_date,
            'target_audience' => $data['target_audience'] ?? $splashScreen->target_audience,
            'metadata' => $data['metadata'] ?? $splashScreen->metadata,
            'updated_by' => $userId,
        ];

        if ($image) {
            // Delete old image
            $this->deleteImage($splashScreen->image_url);
            // Upload new image
            $updateData['image_url'] = $this->uploadImage($image);
        }

        $splashScreen->update($updateData);

        return $splashScreen->fresh(['creator', 'updater']);
    }

    public function deleteSplashScreen(SplashScreen $splashScreen): bool
    {
        $this->deleteImage($splashScreen->image_url);
        return $splashScreen->delete();
    }

    public function activateSplashScreen(SplashScreen $splashScreen): SplashScreen
    {
        $splashScreen->activate();
        return $splashScreen->fresh(['creator', 'updater']);
    }

    public function deactivateSplashScreen(SplashScreen $splashScreen): SplashScreen
    {
        $splashScreen->deactivate();
        return $splashScreen->fresh(['creator', 'updater']);
    }

    private function uploadImage(UploadedFile $image): string
    {
        $path = $image->store('splash-screens', 'public');
        return Storage::url($path);
    }

    private function deleteImage(string $imageUrl): void
    {
        $path = str_replace('/storage/', '', $imageUrl);
        Storage::disk('public')->delete($path);
    }

    public function getAvailableTypes(): array
    {
        return SplashScreen::getAvailableTypes();
    }

    public function getAvailableAudiences(): array
    {
        return SplashScreen::getAvailableAudiences();
    }
}
