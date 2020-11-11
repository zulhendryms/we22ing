<?php

namespace App\Core\POS\Listeners;

use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Core\POS\Services\POSLogService;
use App\Core\POS\Events\POSHidden;

class CreatePOSStatusLog
{

    /** @var POSLogService $logService */
    protected $logService;

    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct(POSLogService $logService)
    {
        $this->logService = $logService;
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
        if ($event instanceof POSHidden) {
            $this->logService->createHideByUserLog($pos);
        } else {
            $this->logService->createStatusChangedLog($pos);
        }
    }
}
