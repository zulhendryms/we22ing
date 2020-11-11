<?php

namespace App\Core\Travel\Controllers\Api;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Laravel\Http\Controllers\Controller;
use App\Core\Travel\Services\TravelTransactionCommissionService;
use App\Core\Travel\Entities\TravelTransactionCommission;
use App\Core\Travel\Entities\TravelTransaction;

class TravelTransactionCommissionController extends Controller
{
    /** @var TravelTransactionCommissionService $commissionService */
    private $commissionService;

    public function __construct(TravelTransactionCommissionService $commissionService)
    {
        $this->commissionService = $commissionService;
    }

    public function post(Request $request, $id)
    {
        DB::transaction(function () use ($id) {
            $commission = TravelTransaction::findOrFail($id);
            $this->commissionService->post($commission);
        });
    }

    public function unpost(Request $request, $id)
    {
        DB::transaction(function () use ($id) {
            $commission = TravelTransaction::findOrFail($id);
            $this->commissionService->unpost($commission);
        });
    }
}