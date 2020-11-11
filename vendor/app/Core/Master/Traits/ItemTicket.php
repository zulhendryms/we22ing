<?php

namespace App\Core\Master\Traits;

use Illuminate\Support\Facades\DB;


trait ItemTicket
{
    /**
     * Get the tickets of the transaction
     */
    public function ETickets()
    {
        return $this->hasMany("App\Core\POS\Entities\ETicket", "Item", "Oid");
    }
}