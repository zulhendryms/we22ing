<?php

namespace App\Core\POS\Mails;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Core\POS\Entities\PointOfSale;
use App\Core\POS\Services\POSETicketService;

class ETicket extends Mailable
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
    public function build(POSETicketService $eticketService)
    {
        $this->subject($this->getSubject());
        $this->view( $this->getEmailView() );
        if (config('core.pos.eticket.send_as_attachment')) {
            $etickets = $this->pos->ETickets;
            foreach ($etickets as $eticket) {
                $this->attach($eticketService->getTicketPath($eticket));
            }
        }
        return $this;
    }

    protected function getSubject()
    {
        return 'E-Ticket '.$this->pos->Code;
    }

    protected function getEmailView()
    {
        $type = $this->pos->PointOfSaleTypeObj;
        $view = null;
        if (strtolower($type->Code) == 'deal') {
            $view = config('core.deal.email.eticket') ?? 'Core\Deal::emails.eticket';
        }
        return $view ?? 'Core\POS::emails.eticket';
    }
}