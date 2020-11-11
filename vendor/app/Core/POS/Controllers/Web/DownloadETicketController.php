<?php

namespace App\Core\POS\Controllers\Web;

use Illuminate\Http\Request;
use App\Laravel\Http\Controllers\Controller;
use App\Core\POS\Services\POSETicketService;
use App\AdminApi\Report\Services\ReportService;
use App\Core\POS\Entities\ETicket;
use App\Core\POS\Entities\PointOfSale;

class DownloadETicketController extends Controller 
{

    /** @var POSETicketService $ticketService */
    protected $ticketService;

    /**
     * @param POSETicketService $ticketService
     * @return void
     */

    protected $reportService;

    public function __construct(POSETicketService $ticketService, ReportService $reportService)
    {
        $this->ticketService = $ticketService;
        $this->reportService = $reportService;
    }

    /**
     * @param Request $request
     * @param string $key
     */
    public function index(Request $request, $key)
    {
        $token = decrypt($key);
        $keys = explode('_', $token);

        throw_if(empty($keys), new \RuntimeException("Unable to process current request"));

        $id = $keys[0];
        $ticket = ETicket::findOrFail($id);
        
        $delete = false;
        $path = $this->ticketService->getTicketPath($ticket, $delete);
        return response()->file($path, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.basename($path).'"',
        ])->deleteFileAfterSend($delete);
    }

    public function index2(Request $request, $key)
    {
        $token = decrypt($key);
        $keys = explode('_', $token);

        throw_if(empty($keys) || count($keys) < 2, new \RuntimeException("Unable to process current request"));

        $pos = PointOfSale::where('Oid', $keys[1])->firstOrFail();

        $etickets = $pos->ETickets;

        if (empty($etickets)) return "Not found";

        if (count($etickets) == 1) {
            $delete = false;
            $path = $this->ticketService->getTicketPath($etickets[0], $delete);
            return response()->file($path)->deleteFileAfterSend($delete);
        }
        $externalFiles = [];

        $path = config('filesystems.disks.temp.root');
        if (!is_dir($path)) mkdir($path);

        $filename = $path.'/'.$pos->CompanyObj->Name.'_'.$pos->Code.'_'.time().'.zip';

        $zipper = new \Chumper\Zipper\Zipper;
        $zipper->make($filename);
        foreach ($etickets as $eticket) {
            $external = false;
            $ticketPath = $this->ticketService->getTicketPath($eticket, $external);
            if ($external) $externalFiles[] = $ticketPath;
            $zipper->add($ticketPath);
        }
        $zipper->close();
        foreach ($externalFiles as $file) unlink($file);
        return response()->download($filename, basename($filename))->deleteFileAfterSend(true);
    }

    public function index3(Request $request, $key)
    { 
        $delete = false;
        $path =  $this->ticketService->getTicketPathExport($key, $delete);
        return response()->file($path, [
            'Content-Type' => 'application/xlsx',
            'Content-Disposition' => 'inline; filename="'.basename($path).'"',
        ])->deleteFileAfterSend($delete);
    }

    public function exportReport(Request $request, $key)
    {
        $filename = $key;
    
        throw_if(empty($filename), new \RuntimeException("Unable to process current request"));
        
        $delete = false;
        $path =  $this->reportService->getPathExportReport($filename, $delete);
        return response()->file($path, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.basename($path).'"',
        ])->deleteFileAfterSend($delete);
    }
}