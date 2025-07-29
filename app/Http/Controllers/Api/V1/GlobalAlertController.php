<?php
// app/Http/Controllers/Api/V1/GlobalAlertController.php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\GlobalAlerts\GlobalAlertStoreRequest;
use App\Http\Requests\V1\GlobalAlerts\GlobalAlertUpdateRequest;
use App\Models\GlobalAlert;
use App\Services\V1\GlobalAlertService;
use Illuminate\Http\Request;

class GlobalAlertController extends Controller
{
    public function __construct(protected GlobalAlertService $service) {}

    public function showActive()
    {
        $alert = $this->service->getActive();
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

    // Admin methods
    public function index()
    {
        return $this->service->listAll();
    }

    public function store(GlobalAlertStoreRequest $req)
    {
        return $this->service->create($req->validated());
    }

    public function update(GlobalAlertUpdateRequest $req, GlobalAlert $globalAlert)
    {
        return $this->service->update($globalAlert, $req->validated());
    }

    public function destroy(GlobalAlert $globalAlert)
    {
        $this->service->delete($globalAlert);
        return response()->json(['success' => true]);
    }
}