<?php

namespace App\Services\V1;

use App\Models\Slider;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;

class SliderService
{
    public function getAllActive()
    {
        return Slider::where('is_active', true)->orderBy('sort_order')->get();
    }
    public function adminList()    { return Slider::orderBy('sort_order')->get(); }

    public function create(array $data): Slider
    {
        if ($data['image'] ?? false) {
            // Handled in controller by passing UploadedFile
            $data['image'] = $data['image']->store('sliders', 'public');
        }
        return Slider::create($data);
    }

    public function update(Slider $slider, array $data): Slider
    {
        if (($data['image'] ?? false) && ($data['image'] instanceof UploadedFile)) {
            Storage::disk('public')->delete($slider->image);
            $data['image'] = $data['image']->store('sliders', 'public');
        } else {
            unset($data['image']);
        }
        $slider->update($data);
        return $slider->fresh();
    }

    public function delete(Slider $slider)
    {
        Storage::disk('public')->delete($slider->image);
        $slider->delete();
    }
}
