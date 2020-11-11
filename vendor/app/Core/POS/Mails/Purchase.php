<?php

namespace App\Core\POS\Mails;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Core\POS\Entities\PointOfSale;
use App\Core\POS\Services\POSETicketService;

class Purchase extends Mailable
{
    use Queueable, SerializesModels;

    public $pos;
    public $etickets;
    public $user;

    /**
     * Create a new message instance.
     * @param User $user
     * @return void
     */
    public function __construct(PointOfSale $pos, $etickets = [])
    {
        $this->pos = $pos;
        $this->user = $pos->UserObj;
        $this->etickets = $etickets;
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
        if ($this->pos->Source != 'Backend') $this->bccVendor();
        if (config('core.pos.eticket.send_as_attachment')) {
            $etickets = $this->etickets;
            foreach ($etickets as $eticket) {
                $path = $eticketService->getTicketPath($eticket);
                if (!is_file($path)) continue;
                $this->attach($eticketService->getTicketPath($eticket));
            }
        }
        return $this;
    }

    protected function getSubject()
    {
        return 'Your E-Ticket '.$this->pos->Code;
    }

    protected function getEmailView()
    {
        $type = $this->pos->PointOfSaleTypeObj;
        $view = config('core.pos.email.purchase');
        if (strtolower($type->Code) == 'deal') {
            $view = config('core.deal.email.eticket') ?? 'Core\Deal::emails.eticket';
        }
        return $view ?? 'Core\POS::emails.purchase';
    }

    private function bccVendor()
    {
        $supplier = $this->pos->SupplierObj;
        if (!empty($supplier->Email)) {
            $this->bcc($supplier->Email);
        }
    }
}