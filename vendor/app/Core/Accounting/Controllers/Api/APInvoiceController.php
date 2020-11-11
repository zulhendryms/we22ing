<?php

namespace App\Core\Accounting\Controllers\Api;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Core\Accounting\Services\APInvoiceService;
use Illuminate\Http\Request;

class APInvoiceController 
{
    /** @var APInvoiceService $accountingService */
    protected $accountingService;

    /**
    * @param APInvoiceService $accountingService
    * @return void
    */
    public function __construct(APInvoiceService $accountingService)
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