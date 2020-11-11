<?php

namespace App\Core\Travel\Controllers\Api;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Core\Travel\Services\TravelPurchaseInvoiceService;
use Illuminate\Http\Request;

class TravelPurchaseInvoiceController 
{
    /** @var TravelPurchaseInvoiceService $travelService */
    protected $travelService;

    /**
    * @param TravelPurchaseInvoiceService $travelService
    * @return void
    */
    public function __construct(TravelPurchaseInvoiceService $travelService)
    {
        $this->travelService = $travelService;
    }

    public function post(Request $request, $id) 
    {
        $this->travelService->post($id);
    }

    public function unpost(Request $request, $id)
    {
        $this->travelService->unpost($id);
    }
}