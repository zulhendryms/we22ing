<?php

namespace App\Core\POS\Services;

use Illuminate\Support\Facades\DB;
use App\Core\POS\Entities\PointOfSaleDetail;

class POSPassengerService
{
    public function __construct()
    {
        
    }

    public function create(PointOfSaleDetail $pos, $param)
    {
        $passenger = $pos->Passengers()->create($param);
    }
}