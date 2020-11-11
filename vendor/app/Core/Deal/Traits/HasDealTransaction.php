<?php

namespace App\Core\Deal\Traits;

trait HasDealTransaction
{
    /**
     * Get the deal transaction of the POS
     */
    public function DealTransactionObj()
    {
        return $this->hasOne("App\Core\Deal\Entities\DealTransaction", "Oid", "Oid");
    }
}