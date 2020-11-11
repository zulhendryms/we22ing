<?php

namespace App\Core\Travel\Listeners;

use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Core\Travel\Services\TravelTransactionAllotmentService;

class RemoveTravelTransactionAllotment
{

    /** @var TravelTransactionAllotmentService $allotmentService */
    protected $allotmentService;

    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct(TravelTransactionAllotmentService $allotmentService)
    {
        $this->allotmentService = $allotmentService;
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
                $this->allotmentService->removeTransactionAllotment($detail);
            }
        }
    }
}
