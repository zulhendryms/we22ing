<?php

namespace App\Core\Travel\Controllers\Api;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Core\POS\Services\POSETicketService;
use Illuminate\Http\Request;
use App\Laravel\Http\Controllers\Controller;
use App\Core\Travel\Entities\TravelTransactionDetail;
use Barryvdh\DomPDF\Facade as PDF;
use App\Core\POS\Entities\PointOfSale;

class TravelETicketController extends Controller
{
    /** @var POSETicketService $eticketService */
    protected $eticketService;
    /**
     * @param POSETicketService $posETicketService
     * @return void
     */
    public function __construct(
        POSETicketService $eticketService
    )
    {
        $this->eticketService = $eticketService;
    }

    public function show(Request $request, $id)
    {
        $pos = PointOfSale::findOrFail($id);
        $pdf = PDF::loadView(
            'Core\Travel::pdf.eticket_parent', compact('pos') 
        );
        return $pdf->stream();
    }

    public function generateForDetail(Request $request, $id)
    {
        $travelTransactionDetail = TravelTransactionDetail::findOrFail($id);
        $item = $travelTransactionDetail->ItemObj;
        if ($item->ParentObj->ETicketMergeType == 1) {
            $this->generateByQty($request, $id);
        } else {
            $this->generateByMerchant($request, $id);
        }
    }

    public function generateByQty(Request $request, $id)
    {
        $travelTransactionDetail = TravelTransactionDetail::findOrFail($id);
        $this->eticketService->generateForTravelTransactionDetailByQty($travelTransactionDetail);
    }

    public function generateByMerchant(Request $request, $id)
    {
        $travelTransactionDetail = TravelTransactionDetail::findOrFail($id);
        $item = $travelTransactionDetail->ItemObj;
        $pos = $travelTransactionDetail->PointOfSaleObj;
        $details = $pos->TravelTransactionDetails->filter(function ($value, $key) use ($item) {
            if (empty($value->ItemObj->ParentOid)) return false;
            return $value->ItemObj->ParentObj->ETicketMergeType == 0 && 
            $value->ItemObj->ParentObj->PurchaseBusinessPartner == $item->ParentObj->PurchaseBusinessPartner && 
            in_array($value->APIType, [ 'manual_gen' ]);
        });
        if ($details->isNotEmpty()) {
            $this->eticketService->generateForTravelTransactionDetailByBusinessPartner(
                $item->ParentObj->PurchaseBusinessPartner,
                $details
            );
        }
    }
}