<?php

namespace App\Core\Trading\Controllers\Api;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Core\Trading\Services\SalesInvoiceService;
use Illuminate\Http\Request;

class SalesInvoiceController 
{
    /** @var SalesInvoiceService $tradingService */
    protected $tradingService;

    /**
    * @param SalesInvoiceService $tradingService
    * @return void
    */
    public function __construct(SalesInvoiceService $tradingService)
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