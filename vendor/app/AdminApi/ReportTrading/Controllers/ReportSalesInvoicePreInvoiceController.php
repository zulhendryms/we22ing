<?php

namespace App\AdminApi\ReportTrading\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

use App\Laravel\Http\Controllers\Controller;
use App\AdminApi\Report\Services\ReportService;

use Barryvdh\DomPDF\Facade as PDF;
use Barryvdh\Snappy\Facades\SnappyPdf;
use Carbon\Carbon;


class ReportSalesInvoicePreInvoiceController extends Controller
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
        $this->reportName = 'salesinvoice-prereport';
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

    public function report(Request $request, $Oid = null)
    {
        $date = date_default_timezone_set("Asia/Jakarta");

        $reportname = $this->reportName;
        $query = $this->query($reportname, $Oid);

        $data = DB::select($query);
        // return view('AdminApi\ReportTrading::pdf.faktursalesinvoice',  compact('data', 'user', 'reportname', 'filter', 'reporttitle'));
        $pdf = SnappyPdf::loadView('AdminApi\ReportTrading::pdf.faktursalesinvoice', compact('data','date','reportname'));


        $pdf
            ->setOption('page-width', '210.4')
            ->setOption('page-height', '135.9')
            ->setOption('margin-right', 5)
            ->setOption('margin-bottom', 5)
            ->setOption('margin-left', 5);

        $reportFile = $this->reportService->create($this->reportName, $pdf);
        $reportPath = $reportFile->getFileName();
        if ($request->input('action')=='download') return response()->download($reportFile->getFilePath())->deleteFileAfterSend(true);
        if ($request->input('action')=='export') return response()->json($this->reportGeneratorController->ReportActionExport($reportPath),Response::HTTP_OK);
        if ($request->input('action')=='email') return response()->json($this->reportGeneratorController->ReportActionEmail($request->input('Email'), $reporttitle, $reportPath),Response::HTTP_OK);
        if ($request->input('action')=='post') return response()->json($this->reportGeneratorController->ReportPost($reporttitle, $reportPath),Response::HTTP_OK);
        return response()
            ->json(
                route(
                    'AdminApi\Report::view',
                    ['reportName' => $reportPath]
                ),
                Response::HTTP_OK
            );
    }

    private function query($reportname, $Oid)
    {
        switch ($reportname) {
            default:
                return "SELECT
                co.Image AS CompanyLogo,
                co.Name AS CompanyName,
                si.Code AS Code,
                si.Note AS Note,
                si.BillingAddress,
                CONCAT(i.Name, ' - ', i.Code) AS GroupName,
                DATE_FORMAT(si.Date, '%e %b %Y') AS Date,
                bp.Name AS BusinessPartner,
                CONCAT(w.Name, ' - ',  w.Code) AS Warehouse,
                c.Code AS CurrencyCode,
                CONCAT(i.Name, ' - ',  i.Code) AS ItemName,
                SUM(IFNULL(sid.Quantity,0)) AS Qty,
                SUM(IFNULL(sid.Price,0)) AS Amount,
                SUM((IFNULL(sid.Price,0) * IFNULL(sid.Quantity,0)) - IFNULL(sid.DiscountAmount,0) - IFNULL(sid.DiscountPercentage,0)) AS TotalAmount,
                si.AdditionalAmount,
                si.DiscountAmount
                
                FROM trdsalesinvoice si 
                LEFT OUTER JOIN trdsalesinvoicedetail  sid ON si.Oid = sid.SalesInvoice
                LEFT OUTER JOIN mstbusinesspartner bp ON si.BusinessPartner = bp.Oid
                LEFT OUTER JOIN mstbusinesspartnergroup bpg ON bp.BusinessPartnerGroup = bpg.Oid
                LEFT OUTER JOIN mstcity ct ON bp.City = ct.Oid
                LEFT OUTER JOIN mstitem i ON sid.Item = i.Oid
                LEFT OUTER JOIN mstitemgroup ig ON i.ItemGroup = ig.Oid
                LEFT OUTER JOIN mstwarehouse w ON si.Warehouse = w.Oid
                LEFT OUTER JOIN mstemployee e ON si.Employee = e.Oid
                LEFT OUTER JOIN mstcurrency c ON si.Currency = c.Oid
                LEFT OUTER JOIN company co ON si.Company = co.Oid
                LEFT OUTER JOIN sysstatus s ON si.Status = s.Oid
                WHERE si.GCRecord IS NULL AND si.Oid =  '" . $Oid . "'
                GROUP BY i.Name, i.Code, c.Code, DATE_FORMAT(si.Date, '%Y%m%d')";
        }
        return " ";
    }
}
