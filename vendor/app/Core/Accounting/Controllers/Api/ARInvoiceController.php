<?php

namespace App\Core\Accounting\Controllers\Api;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Core\Accounting\Services\ARInvoiceService;
use Illuminate\Http\Request;

class ARInvoiceController 
{
    /** @var ARInvoiceService $accountingService */
    protected $accountingService;

    /**
    * @param ARInvoiceService $accountingService
    * @return void
    */
    public function __construct(ARInvoiceService $accountingService)
    {
        $this->accountingService = $accountingService;
    }

    public function post(Request $request, $id) 
    {
        $this->accountingService->post($id);
    }

    public function unpost(Request $request, $id)
    {
        $this->accountingService->unpost($id);
    }
}