<?php

namespace App\Core\Travel\Listeners;

use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Core\Internal\Entities\Status;
use App\Core\POS\Events\POSCompleted;

class CreateTravelTransactionJournal
{

    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
    }

    /** 
     * Handle the event.
     *
     * @param  mixed $event
     * @return void
     */
    public function handle(POSCompleted $event)
    {
        $pos = $event->pos;
        if (is_null($pos->TravelTransactionObj)) return;
        // Create journal
        logger('CreateTravelTransactionJournal');
    }
}
