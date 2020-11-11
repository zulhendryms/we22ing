<?php

namespace App\Core\Travel\Controllers\Api;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Core\POS\Entities\PointOfSale;
use App\Laravel\Http\Controllers\Controller;
use App\Core\Travel\Services\TravelTransactionService;
use App\Core\Travel\Entities\TravelTransactionDetail;
use App\Core\Travel\Services\TravelTransactionAllotmentService;
use App\Core\POS\Services\POSETicketService;
use Illuminate\Support\Facades\Mail;

class TravelTransactionDetailController extends Controller
{

    /** @var TravelTransactionService $travelTransactionService */
    protected $travelTransactionService;
     /** @var TravelTransactionAllotmentService $allotmentService */
     protected $allotmentService;
    /** @var POSETicketService $eticketService */
    protected $eticketService;

    public function __construct(
        TravelTransactionService $travelTransactionService, 
        TravelTransactionAllotmentService $allotmentService,
        POSETicketService $eticketService
    )
    {
        $this->travelTransactionService = $travelTransactionService;
        $this->allotmentService = $allotmentService;
        $this->eticketService = $eticketService;
    }

   public function updateQty(Request $request, $id)
   {
       DB::transaction(function () use ($id, $request) {
        $detail = TravelTransactionDetail::findOrFail($id);
        $this->travelTransactionService->updateDetailQty($detail, $request->all());
   });
   }

   public function updateDate(Request $request, $id)
   {
       DB::transaction(function () use ($id, $request) {
            $detail = TravelTransactionDetail::findOrFail($id);
            $this->travelTransactionService->updateDetailDate($detail, $request->all());
       });
   }

   public function updateAllotment(Request $request, $id)
   {
    DB::transaction(function () use ($id) {
        $detail = TravelTransactionDetail::findOrFail($id);
        $this->allotmentService->removeTransactionAllotment($detail);
        if (!is_null($detail->Item)) $this->allotmentService->assignTransactionAllotment($detail);
    });
   }

   public function deleteAllotment(Request $request, $id)
   {
    DB::transaction(function () use ($id) {
        $detail = TravelTransactionDetail::findOrFail($id);
        $this->allotmentService->removeTransactionAllotment($detail);
        // if (!is_null($detail->Item)) $this->allotmentService->assignTransactionAllotment($detail);
    });
   }

   public function calculate(Request $request, $id)
   {
    DB::transaction(function () use ($id) {
        $detail = TravelTransactionDetail::findOrFail($id);
        $this->travelTransactionService->calculateDetailDateQty($detail);
        $this->travelTransactionService->calculateDetailAmount($detail);
        $this->travelTransactionService->calculateAmount($detail->PointOfSaleObj);
    });
   }

   public function setComplete(Request $request, $id)
   {
       DB::transaction(function () use ($id) {
            $detail = TravelTransactionDetail::findOrFail($id);
            $this->travelTransactionService->setDetailToComplete($detail);
       });
   }

   public function setCancel(Request $request, $id)
   {
        DB::transaction(function () use ($id) {
            $detail = TravelTransactionDetail::findOrFail($id);
            $this->travelTransactionService->setDetailToCancel($detail);
        });
   }

   public function sendEmail(Request $request, $id)
   {
        $detail = TravelTransactionDetail::findOrFail($id);
        $pos = $detail->PointOfSaleObj;
        $item = $detail->ItemObj;
        if ($item->ParentObj->ETicketMergeType == 1) {
            $etickets = $pos->ETickets()->where('TravelTransactionDetail', $detail->Oid)->get();
            $this->eticketService->send($pos, $etickets, $request->input('Email') ?? $pos->ContactEmail ?? 'testing@ezbooking.co');
        } else {
            $etickets = $pos->ETickets()->where('BusinessPartner', $item->ParentObj->PurchaseBusinessPartner)->get();
            $this->eticketService->send($pos, $etickets, $request->input('Email') ?? $pos->ContactEmail ?? 'testing@ezbooking.co');
        }
   }

   public function setEntry(Request $request, $id)
   {
        DB::transaction(function () use ($id) {
            $detail = TravelTransactionDetail::findOrFail($id);
            $this->travelTransactionService->setDetailToEntry($detail);
        });
   }

   public function sendToUser(Request $request, $id)
   {
        $detail = TravelTransactionDetail::findOrFail($id);
        $pos = $detail->PointOfSaleObj;
        $item = $detail->ItemObj;
        if ($item->ParentObj->ETicketMergeType == 1) {
            $etickets = $pos->ETickets()->where('TravelTransactionDetail', $detail->Oid)->get();
            $this->eticketService->send($pos, $etickets, $request->input('Email') ?? $pos->ContactEmail ?? 'testing@ezbooking.co');
        } else {
            $etickets = $pos->ETickets()->where('BusinessPartner', $item->ParentObj->PurchaseBusinessPartner)->get();
            $this->eticketService->send($pos, $etickets, $detail->PointOfSaleObj->ContactEmail ?? $pos->ContactEmail ?? 'testing@ezbooking.co');
        }
   }

   public function sendToVendor(Request $request, $id)
   {
        $detail = TravelTransactionDetail::findOrFail($id);
        $pos = $detail->PointOfSaleObj;
        $item = $detail->ItemObj;
        $email = $item->ParentObj->PurchaseBusinessPartnerObj->Email;
        $details = collect([ $detail ]);
        if ($item->ParentObj->ETicketMergeType != 1) {
            $details = $pos->TravelTransactionDetails->filter(function ($v) use ($item) {
                if (empty($v->ItemObj->ParentOid)) return false;
                return $v->ItemObj->ParentObj->PurchaseBusinessPartner == $item->ParentObj->PurchaseBusinessPartner;
            });
        }
        Mail::to($email ?? 'testing@ezbooking.co')->queue(new \App\Core\Travel\Mails\BusinessPartner($pos, $details));
   }
}