<?php

namespace App\Core\POS\Controllers\Api;

use Illuminate\Http\Request;
use App\Laravel\Http\Controllers\Controller;
use App\Core\POS\Services\POSETicketService;
use App\Core\POS\Entities\ETicket;
use App\Core\POS\Entities\PointOfSale;
use Ramsey\Uuid\Uuid;
use Illuminate\Support\Facades\Storage;
use App\Core\POS\Entities\POSETicketUpload;
use Illuminate\Support\Facades\DB;

class POSETicketController extends Controller 
{

    /** @var POSETicketService $ticketService */
    protected $ticketService;

    /**
     * @param POSETicketService $ticketService
     * @return void
     */
    public function __construct(POSETicketService $ticketService)
    {
        $this->ticketService = $ticketService;
    }

    /**
     * @param Request $request
     * @param string $id
     */
    public function generate(Request $request, $id)
    {
        $pos = PointOfSale::findOrFail($id);
        $this->ticketService->generate($pos, config('core.pos.eticket.auto_send'), ['manual_gen']);
    }

    /**
     * @param Request $request
     * @param string $id
     */
    public function send(Request $request, $id)
    {
        $pos = PointOfSale::findOrFail($id);
        $this->ticketService->send($pos, $pos->ETickets);
    }

    /**
     * @param Request $request
     * @param string $id
     */
    public function upload(Request $request, $id)
    {
        $pos = PointOfSale::findOrFail($id);
        $file = base64_decode(
            substr($request->input('base64'), strpos($request->input('base64'), ',') + 1)
        );
        $id = $request->input('id');
        $filename = $id.'_'.$pos->Oid.'_'.$pos->Code.'_'.str_random(8);
        $key = encrypt($filename);
        $filename .= '.pdf';
        $this->ticketService->putFile($file, $filename);
        return response()->json([
            'filename' => $filename,
            'key' => $key,
            'url' => route('Core\POS::eticket', [ 'key' => $key ])
        ]);
    }

    public function itemUpload(Request $request)
    {
        $files = $request->input('base64');
        $result = [];
        DB::transaction(function () use ($request, $files, &$result) {
            foreach ($files as $f) {
                $file = base64_decode(
                    substr($f, strpos($f, ',') + 1)
                );
                $eticket = $this->ticketService->create($file, [ 
                    'Item' => $request->input('Item'), 
                    'URL' => '',
                    'CostPrice' => $request->input('CostPrice'),
                    'DateExpiry' => $request->input('DateExpiry'),
                    'Note' => $request->input('Note'),
                    // 'ETicketUpload' => $request->input('ETicketUpload')
                ]);
                $result[] = $eticket->Oid;
            }
        });
        return $result;
        // POSETicketUpload::create([
        //     'Item' => $request->input('Item'),
        //     'CostPrice' => $request->input('CostPrice'),
        //     'Note' => $request->input('Note'),
        //     'Count' => count($files),
        // ]);
    }

    public function applyFromStock(Request $request, $id)
    {
        $pos = PointOfSale::findOrFail($id);
        $this->ticketService->linkFromStock($pos);
    }
}