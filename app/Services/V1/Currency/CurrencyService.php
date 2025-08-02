<?php
// app/Services/V1/Currency/CurrencyService.php

namespace App\Services\V1\Currency;

use App\Models\Currency;
use App\Models\CurrencyRate;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CurrencyService
{
    protected string $baseCurrency;

    public function __construct()
    {
        $this->baseCurrency = Currency::getDefault()?->code ?? 'USD';
    }

    public function getUserCurrency(): string
    {
        return Auth::user()?->currency ?? $this->baseCurrency;
    }

    public function convertPrice(float $price, string $fromCurrency = null, string $toCurrency = null): float
    {
        $fromCurrency = $fromCurrency ?? $this->baseCurrency;
        $toCurrency = $toCurrency ?? $this->getUserCurrency();

        if ($fromCurrency === $toCurrency) {
            return $price;
        }

        $rate = $this->getExchangeRate($fromCurrency, $toCurrency);
        return round($price * $rate, 2);
    }

    public function convertPriceArray(array $prices, string $fromCurrency = null, string $toCurrency = null): array
    {
        $convertedPrices = [];
        
        foreach ($prices as $key => $price) {
            if (is_numeric($price)) {
                $convertedPrices[$key] = $this->convertPrice($price, $fromCurrency, $toCurrency);
            } else {
                $convertedPrices[$key] = $price;
            }
        }

        return $convertedPrices;
    }

    public function getExchangeRate(string $from, string $to): float
    {
        if ($from === $to) {
            return 1.0;
        }

        // Try to get from database first
        $rate = CurrencyRate::getRate($from, $to);
        
        if ($rate === 1.0 && $from !== $to) {
            // If not found, try to get from API and cache
            $rate = $this->fetchExchangeRateFromAPI($from, $to);
        }

        return $rate;
    }

    protected function fetchExchangeRateFromAPI(string $from, string $to): float
    {
        $cacheKey = "exchange_rate_{$from}_{$to}";
        
        return Cache::remember($cacheKey, 3600, function () use ($from, $to) {
            try {
                // Using a free API service - you can replace with your preferred service
                $response = Http::get("https://api.exchangerate-api.com/v4/latest/{$from}");
                
                if ($response->successful()) {
                    $data = $response->json();
                    $rate = $data['rates'][$to] ?? 1.0;

                    // Store in database for future use
                    CurrencyRate::updateOrCreate([
                        'from_currency' => $from,
                        'to_currency' => $to,
                    ], [
                        'rate' => $rate,
                        'last_updated_at' => now(),
                    ]);

                    return (float) $rate;
                }
            } catch (\Exception $e) {
                // Log error and return 1.0 as fallback
                Log::error("Currency conversion error: " . $e->getMessage());
            }

            return 1.0;
        });
    }

    public function formatPrice(float $price, string $currency = null): array
    {
        $currency = $currency ?? $this->getUserCurrency();
        $currencyModel = Currency::where('code', $currency)->first();

        return [
            'amount' => $price,
            'currency' => $currency,
            'symbol' => $currencyModel?->symbol ?? $currency,
            'formatted' => ($currencyModel?->symbol ?? $currency) . ' ' . number_format($price, 2),
        ];
    }

    public function getAllActiveCurrencies()
    {
        return Currency::active()->orderBy('code')->get();
    }

    public function updateAllExchangeRates(): array
{
    $currencies = Currency::active()->get();
    $baseCurrency = $this->baseCurrency;
    $updatedRates = [];
    
    foreach ($currencies as $currency) {
        if ($currency->code !== $baseCurrency) {
            $rate = $this->fetchExchangeRateFromAPI($baseCurrency, $currency->code);
            $updatedRates[$currency->code] = $rate;
        }
    }
    
    return [
        'base_currency' => $baseCurrency,
        'updated_rates' => $updatedRates,
        'updated_at' => now()->toISOString(),
    ];
}
}
