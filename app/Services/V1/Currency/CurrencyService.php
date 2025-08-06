<?php
// app/Services/V1/Currency/CurrencyService.php

namespace App\Services\V1\Currency;

use App\Models\Currency;
use App\Models\CurrencyRate;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class CurrencyService
{
    protected ?string $baseCurrency = null;

    public function __construct()
    {
        $this->baseCurrency = $this->getBaseCurrency();
    }

    public function getBaseCurrency(): string
    {
        if (!$this->baseCurrency) {
            $defaultCurrency = Currency::where('is_default', true)->first();
            $this->baseCurrency = $defaultCurrency->code ?? 'USD';
        }
        return $this->baseCurrency;
    }

    public function getUserCurrency(): string
    {
        if(Auth::user()&&!is_null(Auth::user()->currency)){
            return Auth::user()->currency;
        }
        return $this->getBaseCurrency();
    }

    public function convertPrice(float $price, string $fromCurrency = null, string $toCurrency = null): float
    {
        $fromCurrency = $fromCurrency ?? $this->getBaseCurrency();
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

        // Try direct rate first
        $directRate = $this->getDirectRate($from, $to);
        if ($directRate !== null) {
            return $directRate;
        }

        // Try inverse rate
        $inverseRate = $this->getDirectRate($to, $from);
        if ($inverseRate !== null) {
            $calculatedRate = 1 / $inverseRate;
            $this->storeCalculatedRate($from, $to, $calculatedRate);
            return $calculatedRate;
        }

        // Convert through base currency
        $rateFromToBase = $this->convertThroughBase($from, $to);
        if ($rateFromToBase !== null) {
            $this->storeCalculatedRate($from, $to, $rateFromToBase);
            return $rateFromToBase;
        }

        // Fallback
        return 1.0;
    }

    protected function getDirectRate(string $from, string $to): ?float
    {
        $rate = CurrencyRate::where('from_currency', $from)
                           ->where('to_currency', $to)
                           ->first();

        return $rate ? (float) $rate->rate : null;
    }

    protected function convertThroughBase(string $from, string $to): ?float
    {
        $baseCurrency = $this->getBaseCurrency();

        // If from or to is already base currency
        if ($from === $baseCurrency) {
            return $this->getDirectRate($from, $to) ?? $this->getInverseRate($from, $to);
        }

        if ($to === $baseCurrency) {
            return $this->getDirectRate($from, $to) ?? $this->getInverseRate($from, $to);
        }

        // Convert: FROM → BASE → TO
        $fromToBase = $this->getDirectRate($from, $baseCurrency) ?? $this->getInverseRate($from, $baseCurrency);
        $baseToTo = $this->getDirectRate($baseCurrency, $to) ?? $this->getInverseRate($baseCurrency, $to);

        if ($fromToBase !== null && $baseToTo !== null) {
            return $fromToBase * $baseToTo;
        }

        return null;
    }

    protected function getInverseRate(string $from, string $to): ?float
    {
        $inverseRate = $this->getDirectRate($to, $from);
        return $inverseRate ? (1 / $inverseRate) : null;
    }

    protected function storeCalculatedRate(string $from, string $to, float $rate): void
    {
        CurrencyRate::updateOrCreate(
            [
                'from_currency' => $from,
                'to_currency' => $to,
            ],
            [
                'rate' => $rate,
                'last_updated_at' => now(),
            ]
        );
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

    /**
     * Update exchange rates when base currency changes
     */
    public function recalculateRatesForNewBase(string $newBaseCurrency): array
    {
        $oldBaseCurrency = $this->baseCurrency;
        $this->baseCurrency = $newBaseCurrency;
        
        $activeCurrencies = Currency::active()->where('code', '!=', $newBaseCurrency)->get();
        $updatedRates = [];

        foreach ($activeCurrencies as $currency) {
            // Calculate new rate from new base to this currency
            $rate = $this->calculateRateToNewBase($oldBaseCurrency, $newBaseCurrency, $currency->code);
            
            if ($rate !== null) {
                $this->storeCalculatedRate($newBaseCurrency, $currency->code, $rate);
                $updatedRates[$currency->code] = $rate;
            }
        }

        return [
            'old_base' => $oldBaseCurrency,
            'new_base' => $newBaseCurrency,
            'updated_rates' => $updatedRates,
            'updated_at' => now()->toISOString(),
        ];
    }

    protected function calculateRateToNewBase(string $oldBase, string $newBase, string $targetCurrency): ?float
    {
        // If we had OLD_BASE → TARGET, we need NEW_BASE → TARGET
        
        // Get NEW_BASE → OLD_BASE rate
        $newBaseToOldBase = $this->getExchangeRate($newBase, $oldBase);
        
        // Get OLD_BASE → TARGET rate  
        $oldBaseToTarget = $this->getDirectRate($oldBase, $targetCurrency);
        
        if ($oldBaseToTarget !== null && $newBaseToOldBase !== 1.0) {
            // NEW_BASE → TARGET = (NEW_BASE → OLD_BASE) × (OLD_BASE → TARGET)
            return $newBaseToOldBase * $oldBaseToTarget;
        }

        return null;
    }

    
}
