<?php

namespace App\Http\Controllers\Api\V1\Currency;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Currency\StoreCurrencyRateRequest;
use App\Http\Requests\V1\Currency\UpdateCurrencyRateRequest;
use App\Services\V1\Currency\CurrencyRateService;
use App\Models\CurrencyRate;

class CurrencyRateController extends Controller
{
    protected $service;
    public function __construct(CurrencyRateService $service) { $this->service = $service; }

    public function index()
    {
        $rates = $this->service->getAllRates();
        return response()->json(['success' => true, 'data' => $rates]);
    }

    public function store(StoreCurrencyRateRequest $request)
    {
        $rate = $this->service->createRate($request->validated());
        return response()->json(['success' => true, 'data' => $rate, 'message' => 'Exchange rate created/updated successfully.']);
    }

    public function update(UpdateCurrencyRateRequest $request, CurrencyRate $rate)
    {
        $rate = $this->service->updateRate($rate, $request->validated());
        return response()->json(['success' => true, 'data' => $rate, 'message' => 'Exchange rate updated successfully.']);
    }

    public function destroy(CurrencyRate $rate)
    {
        $this->service->deleteRate($rate);
        return response()->json(['success' => true, 'message' => 'Exchange rate deleted successfully.']);
    }
}
