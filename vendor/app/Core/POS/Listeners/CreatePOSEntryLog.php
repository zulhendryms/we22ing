<?php

namespace App\Core\POS\Listeners;

use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Core\POS\Services\POSLogService;

class CreatePOSEntryLog
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
        $this->logService->createCreatedLog($pos);
    }
}
