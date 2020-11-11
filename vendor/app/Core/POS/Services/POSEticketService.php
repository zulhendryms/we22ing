<?php

namespace App\Core\POS\Services;

use App\Core\POS\Entities\PointOfSale;
use App\Core\POS\Entities\ETicket;
use App\Core\Base\Services\HttpService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade as PDF;
use Illuminate\Support\Facades\Mail;
use App\Core\POS\Exceptions\TicketOutOfStockException;
use App\Core\Travel\Entities\TravelTransactionDetail;
use App\Core\Internal\Entities\Status;
use App\Core\Travel\Services\TravelTransactionService;
use Carbon\Carbon;

class POSETicketService 
{
    /** @var HttpService $httpService*/
    protected $httpService;
    /** @var POSStatusService $statusService*/
    protected $statusService;
     /** @var TravelTransactionService $travelTransactionService*/
     protected $travelTransactionService;

    protected $disk;

    /**
     * @param HttpService $httpService
     * @return void
     */
    public function __construct(
        HttpService $httpService,
        POSStatusService $statusService,
        TravelTransactionService $travelTransactionService
    )
    {
        $this->httpService = $httpService;
        $this->statusService = $statusService;
        $this->travelTransactionService = $travelTransactionService;
        $this->disk = Storage::disk('etickets');
    }

    /**
     * @param mixed $file
     * @param array $param
     */
    public function create($file, $param = [])
    {
        $ticket = ETicket::create($param);
        if (isset($file)) {
            // $this->putFile($file, '//'.$ticket->Company.'//'.$ticket->FileName);
            $this->putFile($file, $ticket->FileName);
            try {                
                chmod($this->getTicketPath($ticket), 0644);
            } catch (\Exception $e) { }
        }
        return $ticket;
    }

    public function upload($file, $param = [])
    {
        $ticket = ETicket::create($param);
        if (isset($file)) {
            $ticket->URL = $this->putFile($file, $ticket->FileName);
            
            $ticket->save();
            // dd($ticket->URL);
            try {                
                chmod($this->getTicketPath($ticket), 0644);
            } catch (\Exception $e) { }
        }
        return $ticket;
    }

    public function putFile($file, $filename)
    {
        if (is_string($file)) {
            $this->disk->put($filename, $file);
        } else {
            $this->disk->putFileAs('', $file, $filename);
        }
        try {
            chmod($this->disk->getAdapter()->getPathPrefix().$filename, 0644);
            return $this->disk->getAdapter()->getPathPrefix().$filename;
        } catch (\Exception $e) { }
    }

    /**
     * Get local ticket path
     * 
     * @param ETicket $eticket
     * @param boolean &$external
     */
    public function getTicketPath(ETicket $eticket, &$external = null)
    {
        if (isset($eticket->FileName)) {
            $path = $this->disk->getAdapter()->getPathPrefix();
            return $path.$eticket->FileName;
        } else if (isset($eticket->URL)) {
            $external = true;
            return $this->downloadExternalTicket();
        }
    }

    public function getTicketPathExport($filename, &$external = null)
    {
        $this->disk = Storage::disk('export');
        if (isset($filename)) {
            $path = $this->disk->getAdapter()->getPathPrefix();
            return $path.$filename;
        }
    }

    public function generate(PointOfSale $pos, $send = false, $apiTypes = [ 'auto' ])
    {
        DB::transaction(function () use ($pos, $send, $apiTypes) {
            if ($pos->PointOfSaleTypeObj->Code == 'attraction') {
                $this->generateForTravel($pos, $apiTypes);
            } else {
                if (!in_array($pos->APIType, $apiTypes)) return;
                $pos->ETickets()->delete();
                $this->generateETicket($pos, [], [], $apiTypes);
            }
            if ($send) {
                $this->send($pos, $pos->ETickets);
            }
        });
    }

    protected function generateETicket(PointOfSale $pos, $eticketParams, $params, $apiTypes = ['auto'])
    {
        $eticket = $this->create(null, array_merge([ 'PointOfSale' => $pos->Oid ], $eticketParams));
        $pdf = PDF::loadView(
            $this->getPDFView($pos), array_merge( compact('pos', 'apiTypes'), $params )
        );
        $this->disk->put($eticket->FileName, $pdf->output());

        try {
            chmod($this->getTicketPath($eticket), 0644);
        } catch (\Exception $e) { }
    }

    protected function generateForTravel(PointOfSale $pos, $apiTypes = ['auto'])
    {
        $details = $pos->TravelTransactionDetails;
        $byQtyTransactions = $details->filter(function ($value, $key) use ($apiTypes) {
            if (empty($value->ItemObj->ParentOid)) return false;
            return $value->ItemObj->ParentObj->ETicketMergeType == 1 && in_array($value->APIType, $apiTypes);
        });
        $businessPartnerTransactions = $details->filter(function ($value, $key) use ($apiTypes) {
            if (empty($value->ItemObj->ParentOid)) return false;
            return $value->ItemObj->ParentObj->ETicketMergeType == 0 && in_array($value->APIType, $apiTypes);
        })->groupBy(function ($value, $key) {
            return $value->ItemObj->ParentObj->PurchaseBusinessPartner;
        });
        foreach ($byQtyTransactions as $byQtyTransaction) {
            $this->generateForTravelTransactionDetailByQty($byQtyTransaction, $apiTypes);
        }
        foreach ($businessPartnerTransactions as $businessPartner => $businessPartnerTransaction) {
            $this->generateForTravelTransactionDetailByBusinessPartner($businessPartner, $businessPartnerTransaction);
        }

        // foreach (['Hotel', 'Transport', 'Travel'] as $type) {
        //     $transactionDetails = $details->filter(function ($value, $key) use ($type, $apiTypes) {
        //         return $value->ItemObj->ItemGroupObj->ItemTypeObj->Code == $type && in_array($value->APIType, $apiTypes);
        //     });
        //     if ($transactionDetails->isNotEmpty()) {
        //         if ($type != 'Travel') {
        //             $businessPartners = $transactionDetails->groupBy(function ($value, $key) {
        //                 return $value->ItemObj->PurchaseBusinessPartner;
        //             });
        //             foreach ($businessPartners as $transactionDetails) {
        //                 $this->generateETicket($pos, [ 'APIType' => $transactionDetails[0]->APIType ] , [ 'details' => $transactionDetails ]);
        //             }
        //         } else {
        //             foreach ($transactionDetails as $detail) {
        //                 foreach (['Adult', 'Child', 'Infant'] as $age) {
        //                     for ($i = 0; $i < $detail->{'Qty'.$age}; $i++) {
        //                         $this->generateETicket($pos, [ 'Item' => $detail->Item, 'APIType' => $detail->APIType ], [ 'details' => [ $detail ] ]);
        //                     }
        //                 }
        //             }
        //         }
        //     }
        // }
    }

    public function generateForTravelTransactionDetailByBusinessPartner($businessPartner, $details) {
        $detail = $details->first();
        $pos = $detail->PointOfSaleObj;
        // $pos = (clone $detail->PointOfSaleObj);
        // $pos->ConvenienceAmount = 0;
        // $pos->SubtotalAmount = 0;
        // $pos->TotalAmount = 0;
        // $pos->TotalAmountDisplay = 0;
        
        // foreach ($details as $detail) {
            // $pos->SubtotalAmount += $detail->SalesTotal;
            // $pos->TotalAmount += $detail->SalesTotal;
            // $pos->TotalAmountDisplay += $detail->SalesTotal / $detail->SalesRate;
        // }

        $pos->ETickets()->where('BusinessPartner', $businessPartner)->where('APIType', $detail->APIType)->delete();
        $this->generateETicket($pos, [ 'BusinessPartner' => $businessPartner, 'APIType' => $detail->APIType ], [ 'details' => $details ]);

        foreach ($details as $detail) {
            $this->travelTransactionService->setDetailToComplete($detail);
        }
    }

    public function generateForTravelTransactionDetailByQty(TravelTransactionDetail $detail) {
        // $pos = (clone $detail->PointOfSaleObj);
        $pos = $detail->PointOfSaleObj;
        $item = $detail->ItemObj;
        $pos->ETickets()->where('TravelTransactionDetail', $detail->Oid)->delete();
        // $pos->ConvenienceAmount = 0;
        if ($item->ParentObj->ItemGroupObj->ItemTypeObj->Code == 'Hotel') {
            $start = Carbon::parse($detail->DateFrom);
            $end = Carbon::parse($detail->DateUntil);
            // while ($start->lt($end)) {
                for ($i = 0; $i < $detail->Qty; $i++) {
                    // $detail->SalesTotal = $start->isWeekday() ? $detail->SalesWeekday : $detail->SalesWeekend;
                    // $pos->SubtotalAmount = $detail->SalesTotal;
                    // $pos->TotalAmount = $pos->SubtotalAmount;
                    // $pos->TotalAmountDisplay = $pos->TotalAmount / $detail->SalesRate;
                    $this->generateETicket($pos, [ 'Item' => $item->Oid, 'TravelTransactionDetail' => $detail->Oid, 'APIType' => $detail->APIType ], [ 'details' => [ $detail ], 'qty' => '1 room' ]);
                }
                // $start->addDay();
            // }
        } else {
            foreach (['Adult', 'Child', 'Infant'] as $age) {
                for ($i = 0; $i < $detail->{'Qty'.$age}; $i++) {
                    // $detail->SalesTotal = $detail->{'Sales'.$age};
                    // $pos->SubtotalAmount = $detail->SalesTotal;
                    // $pos->TotalAmount = $pos->SubtotalAmount;
                    // $pos->TotalAmountDisplay = $pos->TotalAmount / $detail->SalesRate;
                    $this->generateETicket($pos, [ 'Item' => $item->Oid, 'TravelTransactionDetail' => $detail->Oid, 'APIType' => $detail->APIType ], [ 'details' => [ $detail ], 'qty' => '1 '.$age ]);
                }
            }
        }
        
        $this->travelTransactionService->setDetailToComplete($detail);
    }

    public function linkFromStock(PointOfSale $pos, $send = false)
    {
        DB::transaction(function () use ($pos, $send) {
            $details = $pos->Details;
            $qty = 0;
            foreach ($details as $detail) {
                // $tickets = ETicket::whereNull('PointOfSale')
                // ->where('Item', $detail->Item)
                // ->limit($detail->Quantity)
                // ->get();
                $itemEtickets = $detail->ItemObj->ETickets()->where('PointOfSale', $pos->Oid)->count();
                $tickets = $detail->ItemObj->ETickets()
                ->available()
                ->limit($detail->Quantity - $itemEtickets)
                ->get();

                // if ($tickets->count() != $detail->Quantity) throw new TicketOutOfStockException("Ticket for {$detail->ItemObj->Name} is out of stock");
                foreach ($tickets as $ticket) {
                    $ticket->PointOfSaleObj()->associate($pos);
                    $ticket->updateKey();
                }
            }
            if ($send) {
                $this->send($pos, $pos->ETickets);
            }
        });
        return $pos->ETickets;
    }

    public function send(PointOfSale $pos, $etickets = [], $email = null)
    {
        if (empty($email)) {
            $email = $pos->ContactEmail;
            // if (isset($pos->User)) $email = $pos->UserObj->UserName;
        }
        Mail::to($email)->queue(new \App\Core\Deal\Mails\ETicket($pos, $etickets));
        $this->statusService->setCompleted($pos);
    }

    /**
     * @return string
     */
    protected function getPDFView(PointOfSale $pos)
    {
        $type = $pos->PointOfSaleTypeObj;
        $view = null;
        if ($type->Code == 'deal') {
            $view = config('core.deal.eticket.template') ?? 'Core\Deal::eticket';
        }
        if ($type->Code == 'attraction') {
            $view = config('core.travel.eticket.template') ?? 'Core\Travel::eticket';
        }
        return $view ?? 'Core\POS::eticket';
    }

    /**
     * @param string $url
     * @return string
     */
    protected function downloadExternalTicket($url)
    {
        $name = str_random(20);
        $path = $this->disk->url($name.".pdf");
        $this->httpService->download($url, $path);
        return $path;
    }
}