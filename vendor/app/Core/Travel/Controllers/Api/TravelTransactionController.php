<?php

namespace App\Core\Travel\Controllers\Api;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Core\POS\Entities\PointOfSale;
use App\Laravel\Http\Controllers\Controller;
use App\Core\Travel\Services\TravelTransactionService;
use App\Core\POS\Exceptions\NoETicketException;
use App\Core\POS\Services\POSStatusService;

class TravelTransactionController extends Controller
{

    /** @var TravelTransactionService $travelTransactionService */
    protected $travelTransactionService;
    /** @var POSStatusService $statusService */
    protected $statusService;

    public function __construct(
        TravelTransactionService $travelTransactionService,
        POSStatusService $statusService
    )
    {
        $this->travelTransactionService = $travelTransactionService;
        $this->statusService = $statusService;
    }

    public function calculate(Request $request, $id)
    {
        DB::transaction(function () use ($id) {
            $pos = PointOfSale::findOrFail($id);
            $this->travelTransactionService->calculateAmount($pos);
        });
    }

    public function complete(Request $request, $id)
    {
        $pos = PointOfSale::findOrFail($id);
        foreach ($pos->TravelTransactionDetails as $detail) {
            if (!is_null($detail->Item) && !$this->travelTransactionService->checkDetailHasETicket($detail)) {
                throw new NoETicketException($detail);
            }
        }
        $this->statusService->setCompleted($pos);
    }
}