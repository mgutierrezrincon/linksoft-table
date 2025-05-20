<?php

namespace App\Helpers;

use App\Models\Order;

class Billing
{
    public static function percentBilled($date)
    {
        $sales = Order::whereBetween('created_at', [now()->startOfDay(), now()->endOfDay()])->whereNot('status', Order::STATUS_CANCELLED)->get();

        if (0 === $sales->count()) {
            return 0;
        }

        return ($sales->filter(function ($sale) {
            return $sale->sale_id > 0;
        })->count() / $sales->count()) * 100;
    }
}
