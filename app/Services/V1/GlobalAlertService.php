<?php

// app/Services/V1/GlobalAlertService.php

namespace App\Services\V1;

use App\Models\GlobalAlert;

class GlobalAlertService
{
    public function getActive(): ?GlobalAlert
    {
        return GlobalAlert::active()->latest('id')->first();
    }

    public function listAll()
    {
        return GlobalAlert::orderByDesc('id')->get();
    }

    public function create(array $data): GlobalAlert
    {
        return GlobalAlert::create($data);
    }

    public function update(GlobalAlert $alert, array $data): GlobalAlert
    {
        $alert->update($data);
        return $alert->fresh();
    }

    public function delete(GlobalAlert $alert): void
    {
        $alert->delete();
    }
}
