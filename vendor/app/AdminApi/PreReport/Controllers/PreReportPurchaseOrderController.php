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



class PreReportPurchaseOrderController extends Controller
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
        $this->reportName = 'prereport-purchaseorder';
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

        // $reportname = $this->reportName;
        $reportname = $request->has('report') ? $request->input('report') : 'PurchaseOrder';
        $Oid = $request->input('oid');
        $req = json_decode(json_encode(object_to_array(json_decode($request->getContent()))));
        if ($req) {
            $reportname = $req->Report;
            $PaperSize = $req->PaperSize;
        }
        
        $query = $this->query($reportname, $Oid);
        $data = DB::select($query);
        // dd($query);
        switch ($reportname) {
            case 'purchaseorder':
                $reporttitle = "PreReport Purchase Order";
                // return view('AdminApi\PreReport::pdf.prereportpurchaseorder', compact('data'));
                $pdf = SnappyPdf::loadView('AdminApi\PreReport::pdf.prereport_' . $reportname, compact('data','PaperSize'));
                break;
            case 'purchaserequest':
                $reporttitle = "PreReport Purchase Request";
                // return view('AdminApi\PreReport::pdf.prereportpurchaserequest', compact('data','user', 'reportname', 'filter', 'reporttitle'));
                $pdf = SnappyPdf::loadView('AdminApi\PreReport::pdf.prereport_' . $reportname, compact('data','PaperSize'));
                break;
                // case 'paymentrequest':
                //     $reporttitle = "PreReport Purchase Request Amount";
                //     // return view('AdminApi\PreReport::pdf.prereportpurchaserequest'.$reportname, compact('data','user', 'reportname', 'filter', 'reporttitle'));
                //     $pdf = SnappyPdf::loadView('AdminApi\PreReport::pdf.prereport_purchaseorder_' . $reportname, compact('data', 'reportname'));
                //     break;
        }

        $headerHtml = view('AdminApi\PreReport::headfoot.prereport_header', compact('data', 'reportname', 'reporttitle'))
            ->render();
        $footerHtml = view('AdminApi\PreReport::headfoot.prereport_footer', compact('data', 'reportname'))
            ->render();

        switch ($PaperSize) {
            case 'A4':
                // PRINT STANDARD
                $pdf
                    ->setOption('header-html', $headerHtml)
                    // ->setOption('footer-html', $footerHtml)
                    // ->setOption('footer-right', "Page [page] of [toPage]")
                    // ->setOption('footer-font-size', 5)
                    // ->setOption('footer-line', true)
                    // ->setOption('page-width', '215.9')
                    ->setOption('page-height', '297')
                    ->setOption('margin-right', 15)
                    ->setOption('margin-bottom', 10);
                break;
            case 'Half':
                // PRINT HALF-CONTINUOUS
                $pdf
                    ->setOption('header-html', $headerHtml)
                    ->setOption('footer-html', $footerHtml)
                    ->setOption('page-width', '210.4')
                    ->setOption('page-height', '135.9')
                    ->setOption('margin-right', 5)
                    ->setOption('margin-left', 5)
                    ->setOption('margin-top', 40)
                    ->setOption('margin-bottom', 14);
                break;
                case 'Full':
                //PRINT FULL-CONTINUOUS
                $pdf
                    ->setOption('header-html', $headerHtml)
                    ->setOption('footer-html', $footerHtml)
                    // ->setOption('footer-right', "Page [page] of [toPage]")
                    // ->setOption('footer-font-size', 5)
                    // ->setOption('footer-line', true)
                    ->setOption('page-width', '215.9')
                    ->setOption('page-height', '265')
                    ->setOption('margin-left', 10)
                    ->setOption('margin-right', 10)
                    ->setOption('margin-bottom', 14);
                break;
            }



        $reportFile = $this->reportService->create($this->reportName, $pdf);
        $reportPath = $reportFile->getFileName();
        if ($request->has('action')) {
            if ($request->input('action') == 'download') return response()->download($reportFile->getFilePath())->deleteFileAfterSend(true);
            if ($request->input('action') == 'export') return response()->json($this->reportGeneratorController->ReportActionExport($reportPath), Response::HTTP_OK);
            if ($request->input('action') == 'email') return response()->json($this->reportGeneratorController->ReportActionEmail($request->input('Email'), $reporttitle, $reportPath), Response::HTTP_OK);
            if ($request->input('action') == 'post') return response()->json($this->reportGeneratorController->ReportPost($reporttitle, $reportPath), Response::HTTP_OK);
        }
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
            case 'purchaseorder':
                return "SELECT
                co.Image AS CompanyLogo,
                co.Name AS CompanyName,
                co.FullAddress AS CompanyAddress,
                co.PhoneNo AS CompanyPhone,
                co.Email AS CompanyEmail,
                po.Code AS Code,
                po.Note,
                po.Type,
                DATE_FORMAT(po.Date, '%e %b %Y') AS Date,
                bp.Name AS Customer,
                CONCAT(w.Name, ' - ',  w.Code) AS Warehouse,
                bp.FullAddress AS CustomerAddress,
                bp.PhoneNumber AS CostumerPhone,
                pm.Name AS PaymentTerm,
                c.Code AS CurrencyCode,
                i.Name AS ItemName,
                pod.Note AS itemNote,
                cc.Name AS CostCenter,
                IFNULL(pod.Quantity,0) AS Qty,
                IFNULL(pod.Price,0) AS Amount,
                IFNULL(po.DiscountAmount,0) AS DiscountAmount,
                (IFNULL(pod.Price,0) * IFNULL(pod.Quantity,0)) - IFNULL(pod.DiscountAmount,0) AS TotalAmount,
                pu.Name AS Purchaser
                
                FROM trdpurchaseorder po 
                LEFT OUTER JOIN trdpurchaseorderdetail pod ON po.Oid = pod.PurchaseOrder
                LEFT OUTER JOIN mstcostcenter cc ON cc.Oid = pod.CostCenter
                LEFT OUTER JOIN mstbusinesspartner bp ON po.BusinessPartner = bp.Oid
                LEFT OUTER JOIN mstbusinesspartnergroup bpg ON bp.BusinessPartnerGroup = bpg.Oid
                LEFT OUTER JOIN mstcity ct ON bp.City = ct.Oid
                LEFT OUTER JOIN mstitem i ON pod.Item = i.Oid
                LEFT OUTER JOIN mstpaymentterm pm ON po.PaymentTerm = pm.Oid
                LEFT OUTER JOIN mstitemgroup ig ON i.ItemGroup = ig.Oid
                LEFT OUTER JOIN mstwarehouse w ON po.Warehouse = w.Oid
                LEFT OUTER JOIN mstemployee e ON po.Employee = e.Oid
                LEFT OUTER JOIN mstcurrency c ON po.Currency = c.Oid
                LEFT OUTER JOIN company co ON po.Company = co.Oid
                LEFT OUTER JOIN sysstatus s ON po.Status = s.Oid
                LEFT OUTER JOIN user pu ON po.Purchaser = pu.Oid
                WHERE po.GCRecord IS NULL AND po.Oid =  '" . $Oid . "'
                ORDER BY pod.Sequence
                ";
                break;
            case 'purchaserequest':
                return "SELECT po.Oid,
                co.Image AS CompanyLogo,
                co.Name AS CompanyName,
                co.FullAddress AS CompanyAddress,
                co.PhoneNo AS CompanyPhone,
                co.Email AS CompanyEmail,
                po.RequestCode AS Code,
                m.Name AS Department,
                po.Note,
                po.Note2,
                po.Type,
                DATE_FORMAT(po.RequestDate, '%e %b %Y') AS Date,
                po.RequestCodeReff AS CodeReff,
                c.Code AS CurrencyCode,
                IFNULL(i.Name,'') AS ItemName,
                IFNULL(pod.Note,'') AS ItemNote,
                IFNULL(pod.Quantity,0) Qty,
                IFNULL(pod.Price1,0) AS Price1, IFNULL(pod.Price2,0) AS Price2, IFNULL(pod.Price3,0) AS Price3,
                IFNULL(po.DiscountAmount1,0) AS DiscountAmount1,
                IFNULL(po.DiscountAmount2,0) AS DiscountAmount2,
                IFNULL(po.DiscountAmount3,0) AS DiscountAmount3,
                CASE po.SupplierChosen WHEN '1' THEN bp1.name WHEN '2' THEN bp2.Name WHEN '3' THEN bp3.Name END AS SupplierChoosen,
                bp1.Name AS Supplier1, bp2.Name AS Supplier2, bp3.Name AS Supplier3,
                u.Name AS Purchaser,
                e1.Name AS Requestor1,
                e2.Name AS Requestor2,
                e3.Name AS Requestor3,
                pm1.name AS PaymentTerm1,pm2.name AS PaymentTerm2,pm3.name AS PaymentTerm3,
                ep1.Name AS ap1,ep2.Name AS ap2,ep3.Name AS ap3,
                u1.Name AS Approval1,u2.Name AS Approval2,u3.Name AS Approval3,
                DATE_FORMAT(ap1.ActionDate, '%e/%m/%y') AS Approval1Date,
                DATE_FORMAT(ap2.ActionDate, '%e/%m/%y') AS Approval2Date,
                DATE_FORMAT(ap3.ActionDate, '%e/%m/%y') AS Approval3Date,
                DATE_FORMAT(ap1.ActionDate, '%h:%i:%s') AS Approval1Hour,
                DATE_FORMAT(ap2.ActionDate, '%h:%i:%s') AS Approval2Hour,
                DATE_FORMAT(ap3.ActionDate, '%h:%i:%s') AS Approval3Hour,
                mu.Name AS ItemUnit,
                cur.Code AS Cur, cc.Name AS CostCenter,
                ap1.Note Approval1Note,ap2.Note Approval2Note,ap3.Note Approval3Note  
                  
                FROM trdpurchaseorder po 
                LEFT OUTER JOIN trdpurchaseorderdetail pod ON po.Oid = pod.PurchaseOrder
                LEFT OUTER JOIN mstcostcenter cc ON cc.Oid = pod.CostCenter
                LEFT OUTER JOIN mstbusinesspartner bp1 ON po.Supplier1 = bp1.Oid
                LEFT OUTER JOIN mstbusinesspartner bp2 ON po.Supplier2 = bp2.Oid
                LEFT OUTER JOIN mstbusinesspartner bp3 ON po.Supplier3 = bp3.Oid
                LEFT OUTER JOIN mstdepartment m ON po.Department = m.Oid
                LEFT OUTER JOIN mstcurrency cur ON po.Currency = cur.Oid
                LEFT OUTER JOIN user u ON po.Purchaser = u.Oid
                LEFT OUTER JOIN mstemployee e1 ON po.Requestor1 = e1.Oid
                LEFT OUTER JOIN mstemployee e2 ON po.Requestor2 = e2.Oid
                LEFT OUTER JOIN mstemployee e3 ON po.Requestor3 = e3.Oid
                LEFT OUTER JOIN mstemployeeposition ep1 ON e1.EmployeePosition = ep1.Oid
                LEFT OUTER JOIN mstemployeeposition ep2 ON e2.EmployeePosition = ep2.Oid
                LEFT OUTER JOIN mstemployeeposition ep3 ON e3.EmployeePosition = ep3.Oid
                LEFT OUTER JOIN mstpaymentterm pm1 ON po.Supplier1PaymentTerm = pm1.Oid
                LEFT OUTER JOIN mstpaymentterm pm2 ON po.Supplier2PaymentTerm = pm2.Oid
                LEFT OUTER JOIN mstpaymentterm pm3 ON po.Supplier3PaymentTerm = pm3.Oid
                LEFT OUTER JOIN mstitem i ON pod.Item = i.Oid
                LEFT OUTER JOIN mstitemunit mu ON pod.ItemUnit = mu.Oid
                LEFT OUTER JOIN mstitemgroup ig ON i.ItemGroup = ig.Oid
                LEFT OUTER JOIN mstcurrency c ON po.Currency = c.Oid
                LEFT OUTER JOIN company co ON po.Company = co.Oid
                LEFT OUTER JOIN pubapproval ap1 ON ap1.PublicPost = po.Oid AND ap1.Sequence = 1
                LEFT OUTER JOIN pubapproval ap2 ON ap2.PublicPost = po.Oid AND ap2.Sequence = 2
                LEFT OUTER JOIN pubapproval ap3 ON ap3.PublicPost = po.Oid AND ap3.Sequence = 3
                LEFT OUTER JOIN user u1 ON ap1.User = u1.Oid
                LEFT OUTER JOIN user u2 ON ap2.User = u2.Oid
                LEFT OUTER JOIN user u3 ON ap3.User = u3.Oid
                WHERE po.GCRecord IS NULL AND po.Oid =  '" . $Oid . "'
                ORDER BY pod.Sequence
                ";
        }
        return " ";
    }
}
