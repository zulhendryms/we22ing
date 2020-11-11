<?php

namespace App\Core\POS\Traits;

use Illuminate\Support\Facades\DB;

trait HasETickets
{
    /**
     * Get the tickets of the transaction
     */
    public function ETickets()
    {
        return $this->hasMany("App\Core\POS\Entities\ETicket", "PointOfSale", "Oid");
    }

    /**
     * Get encrypted url
     * 
     * @return void
     */
    public function getETicketURL()
    {
        if ($this->ETickets()->count() == 0) return null;
        return route('Core\POS::eticket2', [ 'key' => $this->ETickets[0]->Key ]);
    }
}