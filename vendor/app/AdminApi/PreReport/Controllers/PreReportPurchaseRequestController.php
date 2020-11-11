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

class PreReportPurchaseRequestController extends Controller
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
        $this->reportName = 'prereport-purchaserequest';
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

    public function report(Request $request)
    {
        $date = date_default_timezone_set("Asia/Jakarta");

        // $reportname = $this->reportName;
        $reportname = $request->has('report') ? $request->input('report') : 'purchaserequest';
        $Oid = $request->input('oid');
        $query = $this->query($reportname, $Oid);
        
        $data = DB::select($query);


        switch ($reportname) {
            case 'purchaserequest':
                $reporttitle = "PreReport Purchase Request";
                // return view('AdminApi\PreReport::pdf.prereportpurchaserequest', compact('data','user', 'reportname', 'filter', 'reporttitle'));
                $pdf = SnappyPdf::loadView('AdminApi\PreReport::pdf.prereport_purchaserequest', compact('data'));
                break;
            case 'paymentrequest':
                $reporttitle = "PreReport Purchase Request Amount";
                // return view('AdminApi\PreReport::pdf.prereportpurchaserequest'.$reportname, compact('data','user', 'reportname', 'filter', 'reporttitle'));
                $pdf = SnappyPdf::loadView('AdminApi\PreReport::pdf.prereport_purchaseorder_' . $reportname, compact('data','reportname'));
                break;
            }

        $pdf
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
            case 'purchaserequest':
                return "SELECT
                co.Image AS CompanyLogo,
                co.Name AS CompanyName,
                co.FullAddress AS CompanyAddress,
                co.PhoneNo AS CompanyPhone,
                co.Email AS CompanyEmail,
                pr.Code AS Code,
                m.Name AS Department,
                  t.Name AS Truckingpm,
                  pr.Note,
                DATE_FORMAT(pr.Date, '%e %b %Y') AS Date,
                pr.CodeReff AS CodeReff,
                c.Code AS CurrencyCode,
                CONCAT(IFNULL(i.Name,''), ' - ',  IFNULL(prd.Note,'')) AS ItemName,
                IFNULL(prd.Quantity,0) Qty,
                IFNULL(prd.Price1,0) AS Price1, IFNULL(prd.Price2,0) AS Price2, IFNULL(prd.Price3,0) AS Price3,
                IFNULL(pr.DiscountAmount1,0) AS DiscountAmount1,
                IFNULL(pr.DiscountAmount2,0) AS DiscountAmount2,
                IFNULL(pr.DiscountAmount3,0) AS DiscountAmount3,
                CASE pr.SupplierChosen WHEN '1' THEN bp1.name WHEN '2' THEN bp2.Name WHEN '3' THEN bp3.Name END AS SupplierChoosen,
                bp1.Name AS Supplier1, bp2.Name AS Supplier2, bp3.Name AS Supplier3,
                u1.Name AS Approval1,u2.Name AS Approval2,u3.Name AS Approval3,
                u.Name AS Purchaser,
                e1.Name AS Requestor1,
                e2.Name AS Requestor2,
                e3.Name AS Requestor3,
                pm1.name AS PaymentTerm1,pm2.name AS PaymentTerm2,pm3.name AS PaymentTerm3,
                ep1.Name AS ap1,ep2.Name AS ap2,ep3.Name AS ap3,
                DATE_FORMAT(pr.Approval1Date, '%e/%m/%y') AS Approval1Date,
                DATE_FORMAT(pr.Approval2Date, '%e/%m/%y') AS Approval2Date,
                DATE_FORMAT(pr.Approval3Date, '%e/%m/%y') AS Approval3Date,
                DATE_FORMAT(pr.Approval1Date, '%h:%i:%s') AS Approval1Hour,
                DATE_FORMAT(pr.Approval2Date, '%h:%i:%s') AS Approval2Hour,
                DATE_FORMAT(pr.Approval3Date, '%h:%i:%s') AS Approval3Hour,
                mu.Name AS ItemUnit,
                cur.Code AS Cur
                  
                FROM trdpurchaserequest pr 
                LEFT OUTER JOIN trdpurchaserequestdetail prd ON pr.Oid = prd.PurchaseRequest
                LEFT OUTER JOIN mstbusinesspartner bp1 ON pr.Supplier1 = bp1.Oid
                LEFT OUTER JOIN mstbusinesspartner bp2 ON pr.Supplier2 = bp2.Oid
                LEFT OUTER JOIN mstbusinesspartner bp3 ON pr.Supplier3 = bp3.Oid
                LEFT OUTER JOIN mstdepartment m ON pr.Department = m.Oid
                LEFT OUTER JOIN mstcurrency cur ON pr.Currency = cur.Oid
                LEFT OUTER JOIN user u1 ON pr.Approval1 = u1.Oid
                LEFT OUTER JOIN user u2 ON pr.Approval2 = u2.Oid
                LEFT OUTER JOIN user u3 ON pr.Approval3 = u3.Oid
                LEFT OUTER JOIN user u ON pr.Purchaser = u.Oid
                LEFT OUTER JOIN mstcostcenter cc ON pr.CostCenter = cc.Oid
                LEFT OUTER JOIN mstemployee e1 ON pr.Requestor1 = e1.Oid
                LEFT OUTER JOIN mstemployee e2 ON pr.Requestor2 = e2.Oid
                LEFT OUTER JOIN mstemployee e3 ON pr.Requestor3 = e3.Oid
                LEFT OUTER JOIN mstemployeeposition ep1 ON e1.EmployeePosition = ep1.Oid
                LEFT OUTER JOIN mstemployeeposition ep2 ON e2.EmployeePosition = ep2.Oid
                LEFT OUTER JOIN mstemployeeposition ep3 ON e3.EmployeePosition = ep3.Oid
                LEFT OUTER JOIN mstpaymentterm pm1 ON pr.PaymentTerm1 = pm1.Oid
                LEFT OUTER JOIN mstpaymentterm pm2 ON pr.PaymentTerm2 = pm2.Oid
                LEFT OUTER JOIN mstpaymentterm pm3 ON pr.PaymentTerm3 = pm3.Oid
                LEFT OUTER JOIN trcprimemover t ON pr.TruckingPrimeMover = t.Oid
                LEFT OUTER JOIN mstitem i ON prd.Item = i.Oid
                LEFT OUTER JOIN mstitemunit mu ON i.ItemUnit = mu.Oid
                LEFT OUTER JOIN mstitemgroup ig ON i.ItemGroup = ig.Oid
                LEFT OUTER JOIN mstcurrency c ON pr.Currency = c.Oid
                LEFT OUTER JOIN company co ON pr.Company = co.Oid
                WHERE pr.GCRecord IS NULL AND pr.Oid =  '" . $Oid . "'
                ORDER BY prd.Sequence
                ";
            break;
        
        case 'purchaserequestamount':
            return "SELECT
                co.Image AS CompanyLogo,
                co.Name AS CompanyName,
                co.FullAddress AS CompanyAddress,
                co.PhoneNo AS CompanyPhone,
                co.Email AS CompanyEmail,
                pr.Code AS Code,
                m.Name AS Department,
                t.Name AS Truckingpm,
                pr.Note,
                DATE_FORMAT(pr.Date, '%e %b %Y') AS Date,
                pr.CodeReff AS CodeReff,
                a.Name AS Account,
                pra.Description AS Description,
                IFNULL(pra.Amount,0) AS Amount,
                bp1.Name AS Supplier1, bp2.Name AS Supplier2, bp3.Name AS Supplier3,
                u1.Name AS Approval1,u2.Name AS Approval2,u3.Name AS Approval3,
                u.Name AS Purchaser,
                e1.Name AS Requestor1,
                e2.Name AS Requestor2,
                e3.Name AS Requestor3,
                pm1.name AS PaymentTerm1,pm2.name AS PaymentTerm2,pm3.name AS PaymentTerm3,
                ep1.Name AS ap1,ep2.Name AS ap2,ep3.Name AS ap3,
                DATE_FORMAT(pr.Approval1Date, '%e/%m/%y') AS Approval1Date,
                DATE_FORMAT(pr.Approval2Date, '%e/%m/%y') AS Approval2Date,
                DATE_FORMAT(pr.Approval3Date, '%e/%m/%y') AS Approval3Date,
                DATE_FORMAT(pr.Approval1Date, '%h:%i:%s') AS Approval1Hour,
                DATE_FORMAT(pr.Approval2Date, '%h:%i:%s') AS Approval2Hour,
                DATE_FORMAT(pr.Approval3Date, '%h:%i:%s') AS Approval3Hour,
                cur.Code AS Cur
                
                FROM trdpurchaserequest pr 
                LEFT OUTER JOIN trdpurchaserequestamount pra ON pr.Oid = pra.PurchaseRequest
                LEFT OUTER JOIN mstbusinesspartner bp1 ON pr.Supplier1 = bp1.Oid
                LEFT OUTER JOIN mstbusinesspartner bp2 ON pr.Supplier2 = bp2.Oid
                LEFT OUTER JOIN mstbusinesspartner bp3 ON pr.Supplier3 = bp3.Oid
                LEFT OUTER JOIN mstdepartment m ON pr.Department = m.Oid
                LEFT OUTER JOIN mstcurrency cur ON pr.Currency = cur.Oid
                LEFT OUTER JOIN user u1 ON pr.Approval1 = u1.Oid
                LEFT OUTER JOIN user u2 ON pr.Approval2 = u2.Oid
                LEFT OUTER JOIN user u3 ON pr.Approval3 = u3.Oid
                LEFT OUTER JOIN user u ON pr.Purchaser = u.Oid
                LEFT OUTER JOIN mstcostcenter cc ON pr.CostCenter = cc.Oid
                LEFT OUTER JOIN mstemployee e1 ON pr.Requestor1 = e1.Oid
                LEFT OUTER JOIN mstemployee e2 ON pr.Requestor2 = e2.Oid
                LEFT OUTER JOIN mstemployee e3 ON pr.Requestor3 = e3.Oid
                LEFT OUTER JOIN mstemployeeposition ep1 ON e1.EmployeePosition = ep1.Oid
                LEFT OUTER JOIN mstemployeeposition ep2 ON e2.EmployeePosition = ep2.Oid
                LEFT OUTER JOIN mstemployeeposition ep3 ON e3.EmployeePosition = ep3.Oid
                LEFT OUTER JOIN mstpaymentterm pm1 ON pr.PaymentTerm1 = pm1.Oid
                LEFT OUTER JOIN mstpaymentterm pm2 ON pr.PaymentTerm2 = pm2.Oid
                LEFT OUTER JOIN mstpaymentterm pm3 ON pr.PaymentTerm3 = pm3.Oid
                LEFT OUTER JOIN trcprimemover t ON pr.TruckingPrimeMover = t.Oid
                LEFT OUTER JOIN accaccount a ON pra.Account = a.Oid
                LEFT OUTER JOIN mstcurrency c ON pr.Currency = c.Oid
                LEFT OUTER JOIN company co ON pr.Company = co.Oid
                WHERE pr.GCRecord IS NULL AND pr.Oid =  '" . $Oid . "'
                ";
    }
        return " ";
    }
}
