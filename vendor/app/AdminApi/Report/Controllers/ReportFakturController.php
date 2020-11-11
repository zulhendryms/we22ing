<?php

namespace App\AdminApi\Report\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

use App\Laravel\Http\Controllers\Controller;
use App\Core\Accounting\Entities\AccountSection;
use App\Core\Accounting\Entities\AccountGroup;
use App\Core\Internal\Entities\AccountType;
use App\AdminApi\Report\Services\ReportService;

use Barryvdh\DomPDF\Facade as PDF;
use Barryvdh\Snappy\Facades\SnappyPdf;
use Carbon\Carbon;

class ReportFakturController extends Controller
{
    /** @var ReportService $reportService */
    protected $reportService;

    protected $reportName;

    /**
     * @param ReportService $reportService
     * @return void
     */
    public function __construct(ReportService $reportService)
    {
        $this->reportName = 'faktur';
        $this->reportService = $reportService;
    }

    public function view(Request $request, $reportName)
    {
        return response()
            ->file($this
                ->reportService
                ->setFileName($reportName)
                ->getFilePath())
            ->deleteFileAfterSend(true);
    }

    public function report(Request $request, $Oid = null )
    {
        $date = date_default_timezone_set("Asia/Jakarta");

        $reportname = $this->reportName;
        $reporttitle = $reportname;
        $query = $this->query($reportname, $Oid);
// logger($query);
        $data = DB::select($query);
        $pdf = SnappyPdf::loadView('AdminApi\Report::pdf.faktur', compact('data'));
        
        $headerHtml = view('AdminApi\Report::pdf.headerfaktur', compact('data','Date'))
            ->render();
        $footerHtml = view('AdminApi\Report::pdf.footerfaktur')
            ->render();

        $pdf
        ->setOption('header-html', $headerHtml)
        ->setOption('footer-html', $footerHtml)
        ->setOption('footer-right', "Page [page] of [toPage]")
        ->setOption('footer-font-size', 5)
        ->setOption('footer-line', true)
        ->setOption('margin-right', 15)
        ->setOption('margin-bottom', 10);
       
        $reportFile = $this->reportService->create($this->reportName, $pdf);
        $reportPath = $reportFile->getFileName();
        if ($request->input('action')=='download') return response()->download($reportFile->getFilePath())->deleteFileAfterSend(true);
        if ($request->input('action')=='export') return response()->json($this->reportGeneratorController->ReportActionExport($reportPath),Response::HTTP_OK);
        if ($request->input('action')=='email') return response()->json($this->reportGeneratorController->ReportActionEmail($request->input('Email'), $reporttitle, $reportPath),Response::HTTP_OK);
        if ($request->input('action')=='post') return response()->json($this->reportGeneratorController->ReportPost($reporttitle, $reportPath),Response::HTTP_OK);
        return response()
            ->json(
                route('AdminApi\Report::view',
                ['reportName' => $reportPath]), Response::HTTP_OK
            );
    }

    private function query($reportname, $Oid) {
        switch ($reportname) {
            default:
                return "SELECT          
                c.Image AS CompanyLogo,
                c.Name AS CompanyName,
                c.PhoneNo AS NoTlp,
                c.FullAddress AS Address,
                pos.Code AS Code,
                pos.Date AS Date,
                m.Name AS Customer, 
                m1.Name AS Project,
                m2.Name AS Employee2,
                u.Name AS Cashier,
                e.Name AS Sales,
                pm.Name AS Payment,
                pt.Name AS Room,
                i.Name AS ItemName,
                pos.DiscountAmount AS DiscountAmount,
                pos.DiscountPercentage AS DiscountPercentage,
                pos.DiscountPercentageAmount AS DiscountPercentageAmount,

                posd.Quantity,
                posd.Amount,
                posd.DiscountPercentageAmount,
                posd.DiscountPercentage
                
              FROM pospointofsale pos
              LEFT OUTER JOIN user u ON pos.User = u.Oid
              LEFT OUTER JOIN mstemployee e ON pos.Employee = e.Oid
              LEFT OUTER JOIN pospointofsaledetail posd ON pos.Oid = posd.PointOfSale
              LEFT OUTER JOIN mstitem i ON posd.Item = i.Oid
              LEFT OUTER JOIN mstpaymentmethod pm ON pos.PaymentMethod = pm.Oid
              LEFT OUTER JOIN company c ON pos.Company = c.Oid
              LEFT OUTER JOIN mstbusinesspartner m ON pos.Customer = m.Oid
              LEFT OUTER JOIN postable pt ON pos.POSTable = pt.Oid
              LEFT OUTER JOIN mstproject m1 ON pos.Project = m1.Oid
              LEFT OUTER JOIN mstemployee m2 ON pos.Employee2 = m2.Oid
              WHERE pos.GCRecord IS NULL AND pos.Oid ='".$Oid."'";
        }
        return " ";
    }

}