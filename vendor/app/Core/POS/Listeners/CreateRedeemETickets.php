<?php

namespace App\Core\POS\Listeners;

use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Core\POS\Events\POSPaid;
use Illuminate\Support\Facades\Mail;
use App\Core\POS\Services\POSETicketService;

class CreateRedeemETickets
{
    /** @var POSETicketService $eticketService */
    protected $eticketService;

    /**
     * Create the event listener.
     *
     * @param POSETicketService $eticketService
     * @return void
     */
    public function __construct(POSETicketService $eticketService)
    {
        $this->eticketService = $eticketService;
    }

    /** 
     * Handle the event.
     *
     * @param  POSPaid $event
     * @return void
     */
    public function handle(POSPaid $event)
    {
        $pos = $event->pos;
        if ($pos->APIType == 'redeem') {
            foreach ($pos->Details as $detail) {
                for ($i = 0; $i < $detail->Quantity; $i++) {
                    $this->eticketService->create(null, [
                        'Item' => $detail->Item, 
                        'URL' => '',
                        'PointOfSale' => $pos->Oid,
                        'Company' => $pos->Company
                    ]);
                }
            }
        }
    }
}
