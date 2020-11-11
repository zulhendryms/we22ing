<?php

namespace App\Core\Accounting\Controllers\Api;

use App\Laravel\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Core\Accounting\Services\CashBankService;


class CashBankController extends Controller 
{
    /** @var CashBankService $cashBankService */
    protected $cashBankService;

    /**
     * @param CashBankService $cashBankService
     * @return void
     */
    public function __construct(CashBankService $cashBankService)
    {
        $this->cashBankService = $cashBankService;
    }

    public function post(Request $request, $id) 
    {
        $this->cashBankService->post($id);
    }

    public function unpost(Request $request, $id)
    {
        $this->cashBankService->unpost($id);
    }
}