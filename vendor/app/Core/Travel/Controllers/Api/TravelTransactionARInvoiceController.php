<?php

namespace App\Core\Travel\Controllers\Api;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Laravel\Http\Controllers\Controller;
use App\Core\Travel\Entities\TravelTransaction;
use App\Core\Travel\Services\TravelTransactionARInvoiceService;

class TravelTransactionARInvoiceController extends Controller
{
    /** @var TravelTransactionARInvoiceService $arInvoiceService */
    private $arInvoiceService;

    public function __construct(TravelTransactionARInvoiceService $arInvoiceService)
    {
        $this->arInvoiceService = $arInvoiceService;
    }

    public function post(Request $request, $id)
    {
        DB::transaction(function () use ($id) {
            $transaction = TravelTransaction::findOrFail($id);
            $this->arInvoiceService->post($transaction);
        });
    }

    public function unpost(Request $request, $id)
    {
        DB::transaction(function () use ($id) {
            $transaction = TravelTransaction::findOrFail($id);
            $this->arInvoiceService->unpost($transaction);
        });
    }
}