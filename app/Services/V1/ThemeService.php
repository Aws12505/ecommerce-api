<?php

namespace App\Services\V1;

use App\Models\Theme;

class ThemeService
{
    public function all()
    {
        return Theme::all();
    }

    public function getActive()
    {
        return Theme::where('is_active', true)->first();
    }

    public function show(Theme $theme)
    {
        return $theme;
    }

    public function create(array $data): Theme
    {
        return Theme::create($data);
    }

    public function update(Theme $theme, array $data): Theme
    {
        $theme->update($data);
        return $theme->fresh();
    }

    public function delete(Theme $theme): void
    {
        $theme->delete();
    }
}
