<?php
// app/Http/Controllers/Api/V1/Currency/CurrencyController.php

namespace App\Http\Controllers\Api\V1\Currency;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Currency\StoreCurrencyRequest;
use App\Http\Requests\V1\Currency\UpdateCurrencyRequest;
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
            $showAll = request()->boolean('show_all', false);
            
            if ($showAll) {
                $currencies = Currency::orderBy('code')->get();
            } else {
                $currencies = $this->currencyService->getAllActiveCurrencies();
            }

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

    public function store(StoreCurrencyRequest $request)
    {
        try {
            $currency = Currency::create($request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Currency created successfully',
                'data' => $currency,
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create currency',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function update(UpdateCurrencyRequest $request, Currency $currency)
    {
        try {
            $currency->update($request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Currency updated successfully',
                'data' => $currency->fresh(),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update currency',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy(Currency $currency)
    {
        try {
            // Prevent deletion of default currency
            if ($currency->is_default) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete default currency',
                ], 422);
            }

            // Check if currency is being used by users
            $usersCount = \App\Models\User::where('currency', $currency->code)->count();
            if ($usersCount > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete currency as it is being used by users',
                    'users_count' => $usersCount,
                ], 422);
            }

            $currency->delete();

            return response()->json([
                'success' => true,
                'message' => 'Currency deleted successfully',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete currency',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function activate(Currency $currency)
    {
        try {
            $currency->update(['is_active' => true]);

            return response()->json([
                'success' => true,
                'message' => 'Currency activated successfully',
                'data' => $currency->fresh(),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to activate currency',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function deactivate(Currency $currency)
    {
        try {
            // Prevent deactivation of default currency
            if ($currency->is_default) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot deactivate default currency',
                ], 422);
            }

            $currency->update(['is_active' => false]);

            return response()->json([
                'success' => true,
                'message' => 'Currency deactivated successfully',
                'data' => $currency->fresh(),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to deactivate currency',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function setDefault(Currency $currency)
    {
        try {
            // Remove default status from other currencies
            Currency::where('is_default', true)->update(['is_default' => false]);
            
            // Set this currency as default and activate it
            $currency->update([
                'is_default' => true,
                'is_active' => true
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Default currency updated successfully',
                'data' => $currency->fresh(),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to set default currency',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function updateRates()
    {
        try {
            // This would trigger exchange rate updates from your API
            $result = $this->currencyService->updateAllExchangeRates();

            return response()->json([
                'success' => true,
                'message' => 'Exchange rates updated successfully',
                'data' => $result,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update exchange rates',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
