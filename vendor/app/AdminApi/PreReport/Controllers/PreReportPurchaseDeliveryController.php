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




class PreReportPurchaseDeliveryController extends Controller
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
        $this->reportName = 'prereport-purchasedelivery';
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
        // dd($query);
        $data = DB::select($query);
        $pdf = SnappyPdf::loadView('AdminApi\PreReport::pdf.prereport_purchasedelivery', compact('data'));

        $pdf
            ->setOption('footer-right', "Page [page] of [toPage]")
            ->setOption('footer-font-size', 5)
            ->setOption('footer-line', true)
            ->setOption('page-width', '215.9')
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
                pd.Code AS Code,
                DATE_FORMAT(pd.Date, '%e %b %Y') AS Date,
                CONCAT(bp.Name, ' - ', bp.Code) AS Supplier,
                bp.PhoneNumber AS SupplierPhone,
                pd.ShippingAddress AS ShippingAddress,
                CONCAT(w.Name, ' - ',  w.Code) AS Warehouse,
                c.Code AS CurrencyCode,
                pd.Note,
                CONCAT(i.Name, ' - ',  i.Code) AS ItemName,
                pdd.Note AS itemNote,
                IFNULL(pdd.Quantity,0) AS Qty
                
                FROM trdpurchasedelivery pd 
                LEFT OUTER JOIN trdpurchasedeliverydetail  pdd ON pd.Oid = pdd.PurchaseDelivery
                LEFT OUTER JOIN mstbusinesspartner bp ON pd.BusinessPartner = bp.Oid
                LEFT OUTER JOIN mstbusinesspartnergroup bpg ON bp.BusinessPartnerGroup = bpg.Oid
                LEFT OUTER JOIN mstcity ct ON bp.City = ct.Oid
                LEFT OUTER JOIN mstitem i ON pdd.Item = i.Oid
                LEFT OUTER JOIN mstitemgroup ig ON i.ItemGroup = ig.Oid
                LEFT OUTER JOIN mstwarehouse w ON pd.Warehouse = w.Oid
                LEFT OUTER JOIN mstemployee e ON pd.Employee = e.Oid
                LEFT OUTER JOIN mstcurrency c ON pd.Currency = c.Oid
                LEFT OUTER JOIN company co ON pd.Company = co.Oid
                LEFT OUTER JOIN sysstatus s ON pd.Status = s.Oid
                WHERE pd.GCRecord IS NULL AND pd.Oid =  '" . $Oid . "'
                ORDER BY pdd.Sequence
                ";
        }
        return " ";
    }
}
