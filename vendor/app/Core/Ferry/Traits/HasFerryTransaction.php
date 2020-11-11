<?php

namespace App\Core\Ferry\Traits;

trait HasFerryTransaction
{
    /**
     * Get the ferry transaction of the POS
     */
    public function FerryTransactionObj()
    {
        return $this->hasOne("App\Core\Ferry\Entities\FerryTransaction", "Oid", "Oid");
    }
}