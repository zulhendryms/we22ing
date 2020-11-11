<?php

namespace App\AdminApi\PreReport\Controllers;

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



class PreReportPointOfSaleController extends Controller
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
        $this->reportName = 'prereport-pointofsale';
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
        // return view('AdminApi\PreReport::pdf.prereportpointofsale',  compact('data', 'user', 'reportname', 'filter', 'reporttitle'));
        $pdf = SnappyPdf::loadView('AdminApi\PreReport::pdf.prereport_pointofsale', compact('data'));

        $pdf
            ->setOption('footer-right', "Page [page] of [toPage]")
            ->setOption('footer-font-size', 5)
            ->setOption('footer-line', true)
            // ->setOption('page-width', '215.9')
            ->setOption('page-height', '297')
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
                co.FullAddress AS CompanyAddress,
                co.PhoneNo AS CompanyPhone,
                co.Email AS CompanyEmail,
                pos.Code AS Code,
                pos.Note AS Note,
                DATE_FORMAT(pos.Date, '%e %b %Y') AS Date,
                CONCAT(bp2.Name, ' - ', bp2.Code) AS Customer,
                bp2.FullAddress AS CustomerAddress,
                bp2.PhoneNumber AS CostumerPhone,
                CONCAT(w.Name, ' - ',  w.Code) AS Warehouse,
                c.Code AS CurrencyCode,
                CONCAT(i.Name, ' - ',  i.Code) AS ItemName,
                SUM(IFNULL(posd.Quantity,0)) AS Qty,
                SUM(IFNULL(posd.Amount,0)) AS Amount,
                SUM((IFNULL(posd.Amount,0) * IFNULL(posd.Quantity,0)) - IFNULL(posd.DiscountAmount,0) - IFNULL(posd.DiscountPercentage,0)) AS TotalAmount,
                pos.AdditionalAmount,
                pos.DiscountAmount
                
                FROM pospointofsale pos 
                LEFT OUTER JOIN pospointofsaledetail  posd ON pos.Oid = posd.PointOfSale
                LEFT OUTER JOIN mstbusinesspartner bp1 ON pos.Supplier = bp1.Oid
                LEFT OUTER JOIN mstbusinesspartner bp2 ON pos.Customer = bp2.Oid
                LEFT OUTER JOIN mstbusinesspartnergroup bpg ON bp1.BusinessPartnerGroup = bpg.Oid
                LEFT OUTER JOIN mstcity ct ON bp1.City = ct.Oid
                LEFT OUTER JOIN mstitem i ON posd.Item = i.Oid
                LEFT OUTER JOIN mstitemgroup ig ON i.ItemGroup = ig.Oid
                LEFT OUTER JOIN mstwarehouse w ON pos.Warehouse = w.Oid
                LEFT OUTER JOIN mstemployee e ON pos.Employee = e.Oid
                LEFT OUTER JOIN mstcurrency c ON pos.Currency = c.Oid
                LEFT OUTER JOIN company co ON pos.Company = co.Oid
                LEFT OUTER JOIN sysstatus s ON pos.Status = s.Oid
                WHERE pos.GCRecord IS NULL AND pos.Oid =  '" . $Oid . "'
                GROUP BY i.Name, i.Code, c.Code, DATE_FORMAT(pos.Date, '%Y%m%d')";
        }
        return " ";
    }
}
