<?php

namespace App\Helpers;

use App\Models\Subsidiary;

class BusinessHelper
{
    public static function getSubsidiaries()
    {
        return Subsidiary::whereBusinessId(auth()->user()->business_id)->get();
    }
}
