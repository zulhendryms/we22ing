<?php

namespace App\AdminApi\ReportTrading\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Laravel\Http\Controllers\Controller;
use App\AdminApi\Report\Services\ReportService;
use Barryvdh\DomPDF\Facade as PDF;
use Barryvdh\Snappy\Facades\SnappyPdf;
use App\Core\Master\Entities\Item;
use App\Core\Master\Entities\BusinessPartner;
use App\Core\Master\Entities\Employee;
use App\Core\Security\Entities\User;
use App\Core\Master\Entities\Department;
use App\Core\Trucking\Entities\TruckingPrimeMover;
use App\Core\Master\Entities\Company;
use Carbon\Carbon;

class ReportPurchaseRequestController extends Controller
{
    protected $reportService;
    protected $reportName;
    public function __construct(ReportService $reportService)
    {
        $this->reportName = 'purchaserequest';
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
        $reportname = $request->input('report');
        $user = Auth::user();

        $query = $this->query($reportname);
        $filter = "";
        $criteria1 = "";
        $criteria2 = "";
        $criteria3 = "";
        $criteria4 = "";
        $criteria5 = "";
        $criteria6 = "";
        $criteria7 = "";

        $datefrom = Carbon::parse($request->input('DateStart'));
        $dateto = Carbon::parse($request->input('DateUntil'));

        $criteria1 = $criteria1 . " AND DATE_FORMAT(pr.Date, '%Y-%m-%d') >= '" . $datefrom->format('Y-m-d') . "'";
        $filter = $filter . "Date From = '" . strtoupper($datefrom->format('Y-m-d')) . "'; ";
        $criteria1 = $criteria1 . " AND DATE_FORMAT(pr.Date, '%Y-%m-%d') <= '" . $dateto->format('Y-m-d') . "'";
        $filter = $filter . "Date To = '" . strtoupper($dateto->format('Y-m-d')) . "'; ";


        $criteria5 = $criteria5 . " AND DATE_FORMAT(p.Date, '%Y-%m-%d') >= '" . $datefrom->format('Y-m-d') . "'";
        $criteria5 = $criteria5 . " AND DATE_FORMAT(p.Date, '%Y-%m-%d') <= '" . $dateto->format('Y-m-d') . "'";

        $criteria4 = $criteria4 . reportQueryCompany('trdpurchaserequest');
        if ($request->input('p_Company')) {
            $val = Company::findOrFail($request->input('p_Company'));
            $criteria4 = $criteria4 . " AND co.Oid = '" . $val->Oid . "'";
            $filter = $filter . " AND Company = '" . strtoupper($val->Name) . "'";
        }

        if ($request->input('p_Supplier')) {
            $val = BusinessPartner::findOrFail($request->input('p_Supplier'));
            $criteria4 = $criteria4 . " AND (bp1.Oid = '" . $val->Oid . "' OR bp2.Oid = '" . $val->Oid . "' OR bp3.Oid = '" . $val->Oid . "') ";
            $filter = $filter . " AND Supplier = '" . strtoupper($val->Name) . "'";
        }
        if ($request->input('p_Approval')) {
            $val = User::findOrFail($request->input('p_Approval'));
            $criteria4 = $criteria4 . " AND (u1.Oid = '" . $val->Oid . "' OR u2.Oid = '" . $val->Oid . "' OR u3.Oid = '" . $val->Oid . "') ";
            $filter = $filter . " AND Approval = '" . strtoupper($val->Name) . "'";
        }
        if ($request->input('p_Requestor')) {
            $val = Employee::findOrFail($request->input('p_Requestor'));
            $criteria4 = $criteria4 . " AND (e1.Oid = '" . $val->Oid . "' OR e2.Oid = '" . $val->Oid . "' OR e3.Oid = '" . $val->Oid . "'";
            $filter = $filter . " AND Requestor = '" . strtoupper($val->Name) . "'";
        }
        if ($request->input('p_Department')) {
            $val = Department::findOrFail($request->input('p_Department'));
            $criteria4 = $criteria4 . " AND d.Oid = '" . $val->Oid . "'";
            $filter = $filter . " AND Department = '" . strtoupper($val->Name) . "'";
        }
        if ($request->input('p_PrimeMover')) {
            $val = TruckingPrimeMover::findOrFail($request->input('p_PrimeMover'));
            $criteria4 = $criteria4 . " AND pm.Oid = '" . $val->Oid . "'";
            $filter = $filter . " AND TruckingPrimeMover = '" . strtoupper($val->Name) . "'";
        }
        if ($request->input('p_Item')) {
            $val = Item::findOrFail($request->input('p_Item'));
            $criteria4 = $criteria4 . " AND i.Oid = '" . $val->Oid . "'";
            $filter = $filter . " AND Item = '" . strtoupper($val->Name) . "'";
        }
        if ($request->input('p_Status')) {
            $status = $request->input('p_Status');
            $criteria3 = $criteria3 . " AND pr.PurchaseRequestStatus = '" . $status. "'";
            $filter = $filter . " AND Status = '" . strtoupper($status) . "'; ";
        }

        if ($filter) $filter = substr($filter, 0);
        if ($criteria1) $query = str_replace(" AND 1=1", $criteria1, $query);
        // if ($criteria2) $query = str_replace(" AND 2=2",$criteria2,$query);
        if ($criteria3) $query = str_replace(" AND 3=3", $criteria3, $query);
        if ($criteria4) $query = str_replace(" AND 4=4", $criteria4, $query);
        if ($criteria5) $query = str_replace(" AND 5=5", $criteria5, $query);
        if ($criteria6) $query = str_replace(" AND 6=6", $criteria6, $query);


        $data = DB::select($query);
        // dd($query);
        switch ($reportname) {
            case 1:
                $reporttitle = "Report Purchase Request";
                $pdf = SnappyPdf::loadView('AdminApi\ReportTrading::pdf.purchaserequest_01', compact('data', 'user', 'reportname', 'filter', 'reporttitle'))->setPaper('A4', 'landscape');
                break;
        }
        $headerHtml = view('AdminApi\ReportTrading::pdf.header', compact('user', 'reportname', 'filter', 'reporttitle'))
            ->render();
        $footerHtml = view('AdminApi\ReportTrading::pdf.footer', compact('user'))
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
                route(
                    'AdminApi\Report::view',
                    ['reportName' => $reportPath]
                ),
                Response::HTTP_OK
            );
    }

    private function query($reportname)
    {
        switch ($reportname) {
            case 1:
                return "SELECT
                co.Code AS Comp,
                pr.RequestCode AS Code,
                DATE_FORMAT(pr.RequestDate, '%e %b %y') AS Date,
                CASE WHEN pr.SupplierChosen = 1 THEN bp1.Name 
                WHEN pr.SupplierChosen = 2 THEN bp2.Name
                WHEN pr.SupplierChosen = 3 THEN bp3.Name END AS BusinessPartner,
                u.Name AS Purchaser,
                d.Name AS Department,
                CONCAT(i.Name, ' - ',  i.Code) AS ItemName,
                CONCAT(u1.Name, ', ',u2.Name, ', ',u3.Name) AS Approval,
                DATE_FORMAT(pr.Approval3Date, '%b %e %Y') AS ApprovalDate,
                c.Code AS CurrencyCode,
                IFNULL(prd.Quantity,0) AS Qty,
                pr.DiscountAmount,
                prd.Note,
                m.Code AS ItemUnit,
                pt.Name AS PaymentTerm,
                CASE WHEN pr.SupplierChosen = 1 THEN prd.price1 
                WHEN pr.SupplierChosen = 2 THEN prd.price2
                WHEN pr.SupplierChosen = 3 THEN prd.price3 END AS Amount,
                cc.Name AS CostCenter
                FROM trdpurchaseorder pr
                LEFT OUTER JOIN trdpurchaseorderdetail prd ON pr.Oid = prd.PurchaseOrder
                LEFT OUTER JOIN mstdepartment d ON pr.Department = d.Oid
                LEFT OUTER JOIN user u ON pr.Purchaser = u.Oid
                LEFT OUTER JOIN user u1 ON pr.Approval1 = u1.Oid
                LEFT OUTER JOIN user u2 ON pr.Approval2 = u2.Oid
                LEFT OUTER JOIN user u3 ON pr.Approval3 = u3.Oid
                LEFT OUTER JOIN mstemployee e1 ON pr.Requestor1 = e1.Oid
                LEFT OUTER JOIN mstemployee e2 ON pr.Requestor2 = e2.Oid
                LEFT OUTER JOIN mstemployee e3 ON pr.Requestor3 = e3.Oid
                LEFT OUTER JOIN mstcostcenter cc ON prd.CostCenter = cc.Oid
                LEFT OUTER JOIN mstcurrency c ON pr.Currency = c.Oid
                LEFT OUTER JOIN mstbusinesspartner bp1 ON pr.Supplier1 = bp1.Oid
                LEFT OUTER JOIN mstbusinesspartner bp2 ON pr.Supplier2 = bp2.Oid
                LEFT OUTER JOIN mstbusinesspartner bp3 ON pr.Supplier3 = bp3.Oid
                LEFT OUTER JOIN mstitem i ON prd.Item = i.Oid
                LEFT OUTER JOIN mstitemunit m ON i.ItemUnit = m.Oid
                LEFT OUTER JOIN mstpaymentterm pt ON pr.PaymentTerm = pt.Oid
                LEFT OUTER JOIN company co ON co.Oid = pr.Company
                LEFT OUTER JOIN sysstatus s ON pr.Status = s.Oid
                WHERE pr.GCRecord IS NULL AND s.Name IN ('paid', 'complete','posted') AND 1=1 AND 3=3 AND 4=4
                AND pr.Type = 'PurchaseRequest'
                ORDER BY DATE_FORMAT(pr.RequestDate, '%Y%m%d'), pr.RequestCode
                ";
            break;
        }
        return "";
    }
}
