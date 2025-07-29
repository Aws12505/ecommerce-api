<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Sliders\SliderStoreRequest;
use App\Http\Requests\V1\Sliders\SliderUpdateRequest;
use App\Models\Slider;
use App\Services\V1\SliderService;
use Illuminate\Http\Request;

class SliderController extends Controller
{
    public function __construct(protected SliderService $service) {}

    // Public: get all active sliders
    public function index()
    {
        return $this->service->getAllActive();
    }

    // Admin: list all
    public function adminIndex()
    {
        return $this->service->adminList();
    }

    // Admin: create
    public function store(SliderStoreRequest $req)
    {
        $data = $req->validated();
        $data['image'] = $req->file('image');
        return $this->service->create($data);
    }

    // Admin: update
    public function update(SliderUpdateRequest $req, Slider $slider)
    {
        $data = $req->validated();
        if ($req->hasFile('image')) {
            $data['image'] = $req->file('image');
        }
        return $this->service->update($slider, $data);
    }

    // Admin: delete
    public function destroy(Slider $slider)
    {
        $this->service->delete($slider);
        return response()->json(['success' => true]);
    }
}

