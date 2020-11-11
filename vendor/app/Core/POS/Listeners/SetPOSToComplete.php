<?php

namespace App\Core\POS\Listeners;

use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Core\POS\Services\POSStatusService;
use App\Core\Travel\Events\TravelTransactionDetailCompleted;

class SetPOSToComplete
{

    /** @var POSStatusService $statusService */
    protected $statusService;

    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct(POSStatusService $statusService)
    {
        $this->statusService = $statusService;
    }

    /** 
     * Handle the event.
     *
     * @param  mixed $event
     * @return void
     */
    public function handle($event)
    {
        if ($event instanceof TravelTransactionDetailCompleted) {
            $pos = $event->detail->PointOfSaleObj;
            $details = $pos->TravelTransactionDetails;
            // if ($pos->Source == 'Backend') return;
        } else {
            $pos = $event->pos;
            $details = $pos->Details;
        }
        $isComplete = true;
        foreach ($details as $detail) {
            if ($detail->StatusObj->Code != 'complete') {
                $isComplete = false;
                break;
            }
        }
        if ($isComplete) $this->statusService->setCompleted($pos);
    }
}
