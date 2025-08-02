<?php
// app/Http/Controllers/Api/V1/Currency/CurrencyController.php

namespace App\Http\Controllers\Api\V1\Currency;

use App\Http\Controllers\Controller;
use App\Services\V1\Currency\CurrencyService;
use App\Models\Currency;

class CurrencyController extends Controller
{
    protected CurrencyService $currencyService;

    public function __construct(CurrencyService $currencyService)
    {
        $this->currencyService = $currencyService;
    }

    public function index()
    {
        try {
            $currencies = $this->currencyService->getAllActiveCurrencies();

            return response()->json([
                'success' => true,
                'message' => 'Currencies retrieved successfully',
                'data' => $currencies,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve currencies',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function show($code)
    {
        try {
            $currency = Currency::where('code', strtoupper($code))->firstOrFail();

            return response()->json([
                'success' => true,
                'message' => 'Currency retrieved successfully',
                'data' => $currency,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Currency not found',
                'error' => $e->getMessage(),
            ], 404);
        }
    }
}
