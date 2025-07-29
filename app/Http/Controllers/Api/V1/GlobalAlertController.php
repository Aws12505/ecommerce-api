<?php
// app/Http/Controllers/Api/V1/GlobalAlertController.php

namespace App\Http\Controllers\Api\V1;

use App\Models\GlobalAlert;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class GlobalAlertController extends Controller
{
    // Public endpoint: check if alert is active
    public function showActive()
    {
        $alert = GlobalAlert::active()->latest('id')->first();

        return response()->json([
            'show' => !!$alert,
            'alert' => $alert ? [
                'type' => $alert->type,
                'title' => $alert->title,
                'body' => $alert->body,
                'buttons' => $alert->buttons,
                'metadata' => $alert->metadata,
            ] : null,
        ]);
    }

    // Admin: list all
    public function index()
    {
        return GlobalAlert::orderByDesc('id')->get();
    }

    // Admin: create
    public function store(Request $request)
    {
        $data = $request->validate([
            'type' => 'required|string|max:32',
            'title' => 'required|string|max:255',
            'body' => 'nullable|string',
            'buttons' => 'nullable|array',
            'status' => 'required|in:active,inactive',
            'metadata' => 'nullable|array',
        ]);
        return GlobalAlert::create($data);
    }

    // Admin: update
    public function update(Request $request, GlobalAlert $globalAlert)
    {
        $data = $request->validate([
            'type' => 'string|max:32',
            'title' => 'string|max:255',
            'body' => 'nullable|string',
            'buttons' => 'nullable|array',
            'status' => 'in:active,inactive',
            'metadata' => 'nullable|array',
        ]);
        $globalAlert->update($data);
        return $globalAlert->fresh();
    }

    // Admin: delete
    public function destroy(GlobalAlert $globalAlert)
    {
        $globalAlert->delete();
        return response()->json(['success' => true]);
    }
}
