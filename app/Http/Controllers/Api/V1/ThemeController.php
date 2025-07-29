<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Theme;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class ThemeController extends Controller
{
    // Get all themes
    public function index()
    {
        return Theme::all();
    }
    // Get active theme (e.g. for frontend)
    public function active()
    {
        return Theme::where('is_active', true)->first();
    }
    // Show one
    public function show(Theme $theme)
    {
        return $theme;
    }
    // Admin: Create
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:190',
            'palette' => 'required|array',
            'palette.light' => 'required|array',
            'palette.dark' => 'required|array',
        ]);
        return Theme::create($data);
    }
    // Admin: Update
    public function update(Request $request, Theme $theme)
    {
        $data = $request->validate([
            'name' => 'string|max:190',
            'palette' => 'array',
            'palette.light' => 'array',
            'palette.dark' => 'array',
            'is_active' => 'boolean'
        ]);
        $theme->update($data);
        return $theme->fresh();
    }
    // Admin: Delete
    public function destroy(Theme $theme)
    {
        $theme->delete();
        return response()->json(['success' => true]);
    }
}
