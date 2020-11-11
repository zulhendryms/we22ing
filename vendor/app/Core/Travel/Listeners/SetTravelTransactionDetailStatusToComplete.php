<?php

namespace App\Core\Travel\Listeners;

use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Core\Internal\Entities\Status;

class SetTravelTransactionDetailStatusToComplete
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
    public function handle($event)
    {
        $pos = $event->pos;
        if ($pos->TravelTransactionDetails()->count() > 0) {
            foreach ($pos->TravelTransactionDetails as $detail) {
                $detail->StatusObj()->associate(Status::complete()->first());
                $detail->save();
            }
        }
    }
}
