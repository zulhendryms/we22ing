<?php

namespace App\Core\Travel\Mails;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Core\POS\Entities\PointOfSale;

class BusinessPartner extends Mailable
{
    use Queueable, SerializesModels;

    public $pos;
    public $businessPartner;
    public $user;
    public $details;

    /**
     * Create a new message instance.
     * @return void
     */
    public function __construct(PointOfSale $pos, $details = [])
    {
        $this->pos = $pos;
        $this->user = $pos->UserObj;
        $this->businessPartner = $pos->SupplierObj ?? $details[0]->ItemObj->PurchaseBusinessPartnerObj;
        $this->details = $details;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $this->subject('Business Partner '.$this->pos->Code);
        $this->view( 'Core\Travel::emails.businesspartner' );
        return $this;
    }
}