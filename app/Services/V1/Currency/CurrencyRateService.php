<?php

namespace App\Services\V1\Currency;

use App\Models\CurrencyRate;

class CurrencyRateService
{
    public function getAllRates()
    {
        return CurrencyRate::orderBy('from_currency')->orderBy('to_currency')->get();
    }

    public function createRate(array $data)
    {
        return CurrencyRate::updateOrCreate(
            [
                'from_currency' => strtoupper($data['from_currency']),
                'to_currency'   => strtoupper($data['to_currency']),
            ],
            [
                'rate'            => $data['rate'],
                'last_updated_at' => now(),
            ]
        );
    }

    public function updateRate(CurrencyRate $rate, array $data)
    {
        $rate->update([
            'rate'            => $data['rate'],
            'last_updated_at' => now(),
        ]);
        return $rate->fresh();
    }

    public function deleteRate(CurrencyRate $rate)
    {
        return $rate->delete();
    }
}
