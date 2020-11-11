<?php

namespace App\Core\Trading\Controllers\Api;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Core\Trading\Services\PurchaseInvoiceService;
use Illuminate\Http\Request;

class PurchaseInvoiceController 
{
    /** @var PurchaseInvoiceService $tradingService */
    protected $tradingService;

    /**
    * @param PurchaseInvoiceService $tradingService
    * @return void
    */
    public function __construct(PurchaseInvoiceService $tradingService)
    {
        $this->tradingService = $tradingService;
    }

    public function post(Request $request, $id) 
    {
        $this->tradingService->post($id);
    }

    public function unpost(Request $request, $id)
    {
        $this->tradingService->unpost($id);
    }
}