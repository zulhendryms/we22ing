<?php

namespace App\Core\POS\Services;

use App\Core\POS\Entities\PointOfSale;
use App\Core\Internal\Entities\Status;
use App\Core\POS\Events\POSExpired;
use App\Core\POS\Events\POSPaid;
use App\Core\POS\Events\POSOrdered;
use Illuminate\Support\Carbon;
use App\Core\POS\Events\POSVerifying;
use App\Core\POS\Exceptions\POSStatusException;
use App\Core\POS\Events\POSCompleted;
use App\Core\POS\Events\POSQuoted;
use App\Core\POS\Events\POSCancelled;
use App\Core\POS\Entities\ETicket;
use App\Core\Accounting\Services\SalesPOSService;

class POSStatusService 
{
    /**
     * Check pos expiry
     * 
     * @param PointOfSale $pos
     * @return Status
     */
    /** @var JournalService $this->journalService */
    protected $salesPosService;

    public function __construct(SalesPOSService $salesPosService)
    {
        $this->salesPosService = $salesPosService;
    }
    public function checkExpiry(PointOfSale $pos, $throw = false)
    {
        $status = $pos->StatusObj;
        if ($status->IsPaid || $status->IsPostedJournal || $status->IsCompleted || $status->IsCancelled) return $status;
        if (!$status->IsExpired) {
            $now = Carbon::now()->timestamp;
            $expiry = Carbon::parse($pos->DateExpiry)->timestamp;

            if ($now >= $expiry) {
                $this->setExpired($pos);
            }
        }

        if ($pos->StatusObj->IsExpired && $throw) {
            throw new POSStatusException($pos, "Transaction has expired");
        }
        return $pos->StatusObj;
    }

    /**
     * Check if pos status can be updated
     * 
     * @param PointOfSale $pos
     * @param array $status
     * @param boolean $throw
     * @return boolean
     */
    protected function checkStatusIsNot(
        PointOfSale $pos, 
        $exclude = [ 'paid', 'complete', 'cancel', 'expired' ], 
        $throw = false
    )
    {
        $status = $pos->StatusObj;
        if ($status->IsExpired && in_array("expired", $exclude)) {
            if ($throw) throw new POSStatusException($pos, "Transaction is already expired");
            return false;
        } else if ($status->IsCancelled && in_array("cancel", $exclude)) {
            if ($throw) throw new POSStatusException($pos, "Transaction is already cancelled");
            return false;
        } else if ($status->IsPaid && in_array("paid", $exclude)) {
            if ($throw) throw new POSStatusException($pos, "Transaction is already paid");
            return false;
        } else if ($status->IsCompleted && in_array("complete", $exclude)) {
            if ($throw) throw new POSStatusException($pos, "Transaction is already completed");
            return false;
        } else if ($status->IsVerifying && in_array("verifying", $exclude)) {
            if ($throw) throw new POSStatusException($pos, "Transaction is already verifying");
            return false;
        }
        
    }

    /**
     * @param PointOfSale $pos
     */
    public function setQuoted(PointOfSale $pos)
    {
        $this->checkStatusIsNot($pos, [ 'paid', 'complete', 'verifying' ], true);

        $pos->StatusObj()->associate(Status::quoted()->first());
        $pos->save();
        event(new POSQuoted($pos));
    }

     /**
     * @param PointOfSale $pos
     */
    public function setOrdered(PointOfSale $pos)
    {
        $this->checkStatusIsNot($pos, [ 'paid', 'complete', 'verifying' ], true);

        $pos->StatusObj()->associate(Status::ordered()->first());
        $pos->save();
        event(new POSOrdered($pos));
    }

    /**
     * @param PointOfSale $pos
     */
    public function setVerifying(PointOfSale $pos)
    {
        $this->checkStatusIsNot($pos, [ 'paid', 'complete', 'cancel' ], true);

        $pos->StatusObj()->associate(Status::verifying()->first());
        $pos->save();
        event(new POSVerifying($pos));
    }

    /**
     * @param PointOfSale $pos
     */
    public function setPaid(PointOfSale $pos)
    {
        $this->checkStatusIsNot($pos, [ 'complete', 'cancel' ], true);

        $pos->StatusObj()->associate(Status::paid()->first());
        $pos->DatePayment = now()->addHours(company_timezone())->toDateTimeString();
        $pos->save();
        $this->salesPosService->post($pos->Oid);
        event(new POSPaid($pos));

        if ($pos->APIType == 'redeem') {
            $this->setCompleted($pos);
        }
    }

    /**
     * @param PointOfSale $pos
     */
    public function setCompleted(PointOfSale $pos)
    {
        $this->checkStatusIsNot($pos, [ 'cancel' ], true);
        $this->salesPosService->post($pos->Oid);
        $pos->save();
        $pos->StatusObj()->associate(Status::complete()->first());
        event(new POSCompleted($pos));
    }

    /**
     * @param PointOfSale $pos
     */
    public function setExpired(PointOfSale $pos)
    {
        $this->checkStatusIsNot($pos, [ 'paid', 'complete', 'cancel' ], true);

        $type = $pos->PointOfSaleTypeObj;
        $pos->StatusObj()->associate(Status::expired()->first());
        $pos->DateExpiry = now()->toDateTimeString();
        $pos->ErrorStep = $type->IsFerry ? 4 : 2;
        $pos->ErrorDescription = 'Expired, no payment received';
        $pos->save();
        $this->salesPosService->unpost($pos->Oid);
        event(new POSExpired($pos));
    }

    public function setCancelled(PointOfSale $pos)
    {
        $this->checkStatusIsNot($pos, [ 'complete', 'cancel' ], true);
        $pos->StatusObj()->associate(Status::cancelled()->first());
        $pos->save();
        $this->salesPosService->unpost($pos->Oid);

        if ($pos->APIType == 'auto_stock') {
            $pos->ETickets()->update(['PointOfSale' => null, 'Key' => null, 'URL' => null ]);
        }
        event(new POSCancelled($pos));
    }

    public function setEntry(PointOfSale $pos)
    {
        $this->checkStatusIsNot($pos, [ 'cancel' ], true);
        $pos->StatusObj()->associate(Status::entry()->first());
        $pos->save();
        $this->salesPosService->unpost($pos->Oid);
    }

    private function checkForTravel(PointOfSale $pos)
    {
        foreach ($pos->TravelTransactionDetails as $detail) {
            if ($detail->APIType == 'manual_up') continue;
            $item = $detail->ItemObj;
            if ($item->ETicketMergeType == 1) {
                $eticket = ETicket::where('TravelTransactionDetail', $detail->Oid)->first();
                if (is_null($eticket)) {
                    
                }
            }
        }
    }

}