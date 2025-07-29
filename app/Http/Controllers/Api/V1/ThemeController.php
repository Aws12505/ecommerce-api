<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Theme\ThemeStoreRequest;
use App\Http\Requests\V1\Theme\ThemeUpdateRequest;
use App\Models\Theme;
use App\Services\V1\ThemeService;

class ThemeController extends Controller
{
    public function __construct(protected ThemeService $service) {}

    public function index()
    {
        return $this->service->all();
    }

    public function active()
    {
        return $this->service->getActive();
    }

    public function show(Theme $theme)
    {
        return $theme;
    }

    public function store(ThemeStoreRequest $req)
    {
        return $this->service->create($req->validated());
    }

    public function update(ThemeUpdateRequest $req, Theme $theme)
    {
        return $this->service->update($theme, $req->validated());
    }

    public function destroy(Theme $theme)
    {
        $this->service->delete($theme);
        return response()->json(['success' => true]);
    }
}