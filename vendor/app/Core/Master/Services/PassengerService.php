<?php

namespace App\Core\Master\Services;

use Illuminate\Support\Facades\DB;
use App\Core\POS\Entities\PointOfSaleDetail;
use App\Core\Master\Entities\Company;

class PassengerService
{
    public function __construct()
    {
        
    }

    public function create(Company $company, $param)
    {
        $passport = $param['PassportNumber'];
        $passenger = $company->Passengers()->where('PassportNumber', $passport)->first();
        if (is_null($passenger)) {
            return $company->Passengers()->create($param);
        }
        $passenger->update($param);
        return $passenger;
    }
}