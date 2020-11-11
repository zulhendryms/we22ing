<?php

namespace App\Core\POS\Mails;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Core\POS\Entities\PointOfSale;

class BusinessPartner extends Mailable
{
    use Queueable, SerializesModels;

    public $pos;
    public $user;

    /**
     * Create a new message instance.
     * @param User $user
     * @return void
     */
    public function __construct(PointOfSale $pos)
    {
        $this->pos = $pos;
        $this->user = $pos->UserObj;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $this->subject('Business Partner '.$this->pos->Code);
        $this->view( config('core.pos.email.businesspartner') ?? 'Core\POS::emails.businesspartner' );
        return $this;
    }
}