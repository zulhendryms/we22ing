<?php

namespace App\Core\POS\Listeners;

use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Core\POS\Services\POSETicketService;
use App\Core\POS\Events\POSPaid;

class SendETicketToUser implements ShouldQueue
{

    /** @var POSETicketService $eticketService */
    protected $eticketService;

    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct(POSETicketService $eticketService)
    {
        $this->eticketService = $eticketService;
    }

    /** 
     * Handle the event.
     *
     * @param  mixed $event
     * @return void
     */
    public function handle(POSPaid $event)
    {
        $pos = $event->pos;

        $method = $pos->PaymentMethodObj;

        $blacklists = config('core.pos.eticket.auto_send.payment_method_blacklist');
        $whitelists = config('core.pos.eticket.auto_send.payment_method_whitelist');

        $send = true;

        if (count($blacklists) != 0) {
            if (in_array(
                $method->Code,
                config('core.pos.eticket.auto_send.payment_method_blacklist')
            )) $send = false;
        } else if (count($whitelists) != 0) {
            if (!in_array(
                $method->Code,
                config('core.pos.eticket.auto_send.payment_method_whitelist')
            )) $send = false;
        }

        $auto = strpos($pos->APIType, 'auto') !== false;

        if ($pos->TravelTransactionDetails()->count() != 0) { // is travel
            $auto = true;
            $send = false;
            // foreach ($pos->TravelTransactionDetails as $detail) {
            //     if (strpos($detail->APIType, 'auto') !== false) {
            //         $auto = true;
            //         break;
            //     }
            // }
        }
        if (!config('core.pos.eticket.auto_send.enable')) $send = false;
        if (!$auto) return;

        if ($pos->APIType == 'auto_stock') {
            $this->eticketService->linkFromStock($pos, $send);
        } else {
            $this->eticketService->generate($pos, $send, ['auto']);
        }
    }
}
