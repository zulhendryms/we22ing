<?php

namespace App\Core\Travel\Traits;

trait HasTravelTransaction
{
    public function TravelTransactionObj()
    {
        return $this->hasOne("App\Core\Travel\Entities\TravelTransaction", "Oid", "Oid");
    }

    public function TravelTransactionDetails()
    {
        return $this->hasMany("App\Core\Travel\Entities\TravelTransactionDetail", "TravelTransaction", "Oid");
    }
}
