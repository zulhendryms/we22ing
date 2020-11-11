<?php

namespace App\Core\POS\Listeners;

use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Core\POS\Events\POSPaid;
use App\Core\POS\Events\POSOrdered;
use App\Core\POS\Events\POSVerifying;
use App\Core\POS\Services\POSNotificationService;

class SendPOSNotificationToSlack implements ShouldQueue
{

    /** @var POSNotificationService $notificationService */
    protected $notificationService;

    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct(POSNotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /** 
     * Handle the event.
     *
     * @param  POSOrdered|POSVerifying|POSPaid $event
     * @return void
     */
    public function handle($event)
    {
        $pos = $event->pos;
        $method = $pos->PaymentMethodObj;
        if ($event instanceof POSOrdered) {
            if (!config('core.pos.slack.pos_ordered')) return;
            if (strpos($method->Code, 'manual') === false) return; 
        }
        if ($event instanceof POSVerifying && !config('core.pos.slack.pos_verifying')) return; 
        if ($event instanceof POSPaid && !config('core.pos.slack.pos_paid')) return;
        $this->notificationService->send($pos);
    }
}
