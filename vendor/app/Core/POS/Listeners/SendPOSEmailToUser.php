<?php

namespace App\Core\POS\Listeners;

use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Core\POS\Events\POSPaymentMethodSelected;
use App\Core\POS\Events\POSPaid;
use App\Core\POS\Events\POSCompleted;
use Illuminate\Support\Facades\Mail;

class SendPOSEmailToUser implements ShouldQueue
{

    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /** 
     * Handle the event.
     *
     * @param  POSPaymentMethodSelected|POSPaid|POSCompleted $event
     * @return void
     */
    public function handle($event)
    {
        $pos = $event->pos;
        $method = $pos->PaymentMethodObj;
        $type = $pos->PointOfSaleTypeObj;
        $email = $pos->ContactEmail;
        // if (isset($pos->User)) $email = $pos->UserObj->UserName;
        if ($event instanceof POSPaymentMethodSelected) {
            if (!empty(config('core.pos.email.payment_instruction') && strpos($method->Code, 'manual') !== false)) {
                Mail::to($email)->queue(new \App\Core\POS\Mails\PaymentInstruction($pos));
            }
        } else if ($event instanceof POSPaid) {
            //
        } else if ($event instanceof POSCompleted) {
            //
        }
    }
}
