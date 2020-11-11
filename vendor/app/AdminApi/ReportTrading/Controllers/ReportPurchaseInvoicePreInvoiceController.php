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


class ReportPurchaseInvoicePreInvoiceController extends Controller
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
        $this->reportName = 'purchaseinvoice-prereport';
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
        $pdf = SnappyPdf::loadView('AdminApi\ReportTrading::pdf.fakturpurchaseinvoice', compact('data'));

        $headerHtml = view('AdminApi\ReportTrading::pdf.headerfaktur', compact('data', 'date', 'reportname'))
            ->render();
        // $footerHtml = view('AdminApi\Trading::pdf.footerfaktur')
        //     ->render();

        $pdf
            ->setOption('header-html', $headerHtml)
            // ->setOption('footer-html', $footerHtml)
            ->setOption('footer-right', "Page [page] of [toPage]")
            ->setOption('footer-font-size', 5)
            ->setOption('footer-line', true)
            ->setOption('page-height', '140')
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
                pi.Code AS Code,
                CONCAT(i.Name, ' - ', i.Code) AS GroupName,
                DATE_FORMAT(pi.Date, '%e %b %Y') AS Date,
                CONCAT(bp.Name, ' - ', bp.Code) AS BusinessPartner,
                CONCAT(w.Name, ' - ',  w.Code) AS Warehouse,
                c.Code AS CurrencyCode,
                CONCAT(i.Name, ' - ',  i.Code) AS ItemName,
                SUM(IFNULL(pid.Quantity,0)) AS Qty,
                SUM(IFNULL(pid.TotalAmount,0)) AS Amount,
                SUM((IFNULL(pid.TotalAmount,0) * IFNULL(pid.Quantity,0)) - IFNULL(pid.DiscountAmount,0) - IFNULL(pid.DiscountPercentage,0)) AS TotalAmount
                
                FROM trdpurchaseinvoice pi 
                LEFT OUTER JOIN trdpurchaseinvoicedetail  pid ON pi.Oid = pid.PurchaseInvoice
                LEFT OUTER JOIN mstbusinesspartner bp ON pi.BusinessPartner = bp.Oid
                LEFT OUTER JOIN mstbusinesspartnergroup bpg ON bp.BusinessPartnerGroup = bpg.Oid
                LEFT OUTER JOIN mstcity ct ON bp.City = ct.Oid
                LEFT OUTER JOIN mstitem i ON pid.Item = i.Oid
                LEFT OUTER JOIN mstitemgroup ig ON i.ItemGroup = ig.Oid
                LEFT OUTER JOIN mstwarehouse w ON pi.Warehouse = w.Oid
                LEFT OUTER JOIN mstemployee e ON pi.Employee = e.Oid
                LEFT OUTER JOIN mstcurrency c ON pi.Currency = c.Oid
                LEFT OUTER JOIN company co ON pi.Company = co.Oid
                LEFT OUTER JOIN sysstatus s ON pi.Status = s.Oid
                WHERE pi.GCRecord IS NULL AND pi.Oid =  '" . $Oid . "'
                GROUP BY i.Name, i.Code, c.Code, DATE_FORMAT(pi.Date, '%Y%m%d')";
        }
        return " ";
    }
}
