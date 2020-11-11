<?php

namespace App\Core\POS\Controllers\Web;

use Illuminate\Http\Request;
use App\Laravel\Http\Controllers\Controller;
use App\Core\POS\Services\POSETicketService;
use App\Core\POS\Entities\PointOfSale;

class POSETicketController extends Controller 
{
    /** @var POSETicketService $ticketService */
    protected $ticketService;

    /**
     * @param POSETicketService $ticketService
     * @return void
     */
    public function __construct(POSETicketService $ticketService)
    {
        $this->ticketService = $ticketService;
    }

    /**
     * @param Request $request
     * @param string $id
     */
    public function send(Request $request, $id)
    {
        $pos = PointOfSale::findOrFail($id);
        $this->ticketService->send($pos, $pos->ETickets, $request->input('Email'));
    }
}