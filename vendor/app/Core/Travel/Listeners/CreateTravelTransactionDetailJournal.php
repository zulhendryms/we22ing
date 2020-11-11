<?php

namespace App\Core\Travel\Listeners;

use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Core\Internal\Entities\Status;
use App\Core\Travel\Events\TravelTransactionDetailCompleted;

class CreateTravelTransactionDetailJournal
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
    public function handle(TravelTransactionDetailCompleted $event)
    {
        $detail = $event->detail;
        // Create journal
        logger('CreateTravelTransactionDetailJournal');
    }
}
