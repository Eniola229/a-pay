<?php

namespace App\Helpers;

class CashbackHelper
{
    public static function calculate(float $amount): float
    {
        $tiers = [
            0       => ['rate' => 0.00,  'cap' => 0],
            100     => ['rate' => 0.03,  'cap' => 200],
            5000    => ['rate' => 0.05,  'cap' => 500],
            10000   => ['rate' => 0.06,  'cap' => 1000],
        ];

        $cashback = 0.00;

        foreach ($tiers as $min => $cfg) {
            if ($amount >= $min) {
                $cashback = $amount * $cfg['rate'];
                if ($cashback > $cfg['cap']) {
                    $cashback = $cfg['cap'];
                }
            }
        }

        return round($cashback, 2);
    }
}
