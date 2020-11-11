<?php

namespace App\AdminApi\ReportTrading\Controllers;

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

use App\Core\Master\Entities\BusinessPartner;
use App\Core\Master\Entities\PaymentMethod;
use App\Core\Master\Entities\Warehouse;
use App\Core\Master\Entities\Employee;
use App\Core\Master\Entities\City;
use App\Core\Master\Entities\BusinessPartnerGroup;
use App\Core\Master\Entities\Item;
use App\Core\Security\Entities\User;


class ReportPurchaseOrderPreOrderController extends Controller
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
        $this->reportName = 'purchaseorder-prereport';
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
        $pdf = SnappyPdf::loadView('AdminApi\ReportTrading::pdf.fakturpurchaseorder', compact('data'));

        $headerHtml = view('AdminApi\ReportTrading::pdf.headerfakturpo', compact('data', 'date', 'reportname'))
            ->render();
        // $footerHtml = view('AdminApi\ReportTrading::pdf.footerfaktur')
        //     ->render();

        $pdf
            ->setOption('header-html', $headerHtml)
            // ->setOption('footer-html', $footerHtml)
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
                po.Code AS Code,
                CONCAT(i.Name, ' - ', i.Code) AS GroupName,
                DATE_FORMAT(po.Date, '%e %b %Y') AS Date,
                CONCAT(bp.Name, ' - ', bp.Code) AS BusinessPartner,
                CONCAT(w.Name, ' - ',  w.Code) AS Warehouse,
                c.Code AS CurrencyCode,
                CONCAT(i.Name, ' - ',  i.Code) AS ItemName,
                SUM(IFNULL(pod.Quantity,0)) AS Qty,
                SUM(IFNULL(pod.TotalAmount,0)) AS Amount,
                SUM((IFNULL(pod.TotalAmount,0) * IFNULL(pod.Quantity,0)) - IFNULL(pod.DiscountAmount,0) - IFNULL(pod.DiscountPercentage,0)) AS TotalAmount
                
                FROM trdpurchaseorder po 
                LEFT OUTER JOIN trdpurchaseorderdetail  pod ON po.Oid = pod.PurchaseOrder
                LEFT OUTER JOIN mstbusinesspartner bp ON po.BusinessPartner = bp.Oid
                LEFT OUTER JOIN mstbusinesspartnergroup bpg ON bp.BusinessPartnerGroup = bpg.Oid
                LEFT OUTER JOIN mstcity ct ON bp.City = ct.Oid
                LEFT OUTER JOIN mstitem i ON pod.Item = i.Oid
                LEFT OUTER JOIN mstitemgroup ig ON i.ItemGroup = ig.Oid
                LEFT OUTER JOIN mstwarehouse w ON po.Warehouse = w.Oid
                LEFT OUTER JOIN mstemployee e ON po.Employee = e.Oid
                LEFT OUTER JOIN mstcurrency c ON po.Currency = c.Oid
                LEFT OUTER JOIN company co ON po.Company = co.Oid
                LEFT OUTER JOIN sysstatus s ON po.Status = s.Oid
                WHERE po.GCRecord IS NULL AND po.Oid =  '" . $Oid . "'
                GROUP BY i.Name, i.Code, c.Code, DATE_FORMAT(po.Date, '%Y%m%d')";
        }
        return " ";
    }
}
