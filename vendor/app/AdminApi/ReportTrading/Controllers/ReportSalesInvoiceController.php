<?php

namespace App\AdminApi\ReportTrading\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Laravel\Http\Controllers\Controller;
use App\Core\Master\Entities\BusinessPartner;
use App\AdminApi\Report\Services\ReportService;
use Barryvdh\DomPDF\Facade as PDF;
use Barryvdh\Snappy\Facades\SnappyPdf;
use App\Core\Master\Entities\PaymentMethod;
use App\Core\Master\Entities\Warehouse;
use App\Core\Master\Entities\Employee;
use App\Core\POS\Entities\POSTable;
use App\Core\Master\Entities\City;
use App\Core\Master\Entities\BusinessPartnerGroup;
use App\Core\Master\Entities\Company;
use App\Core\Master\Entities\Item;
use App\Core\Security\Entities\User;
use Carbon\Carbon;

class ReportSalesInvoiceController extends Controller
{
    protected $reportService;
    protected $reportName;
    public function __construct(ReportService $reportService)
    {
        $this->reportName = 'salesinvoice';
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
        $warehouse = Warehouse::where('IsActive', 1)->orderBy('Sequence')->whereRaw('Sequence > 0')->get();

        // $firstDayOfMonth = date("Y-m-01", strtotime($datefrom));
        // $lastDayOfMonth = date("Y-m-t", strtotime($datefrom));

        $criteria1 = $criteria1 . " AND DATE_FORMAT(pi.Date, '%Y-%m-%d') >= '" . $datefrom->format('Y-m-d') . "'";
        $filter = $filter . "Date From = '" . strtoupper($datefrom->format('d-m-Y')) . "'; ";
        $criteria1 = $criteria1 . " AND DATE_FORMAT(pi.Date, '%Y-%m-%d') <= '" . $dateto->format('Y-m-d') . "'";
        $filter = $filter . "Date To = '" . strtoupper($dateto->format('d-m-Y')) . "'; ";

        // $filter = $filter . "Date From = '" . strtoupper($datefrom->format('Y-m-d')) . "'; ";
        // $filter = $filter . "Date To = '" . strtoupper($dateto->format('Y-m-d')) . "'; ";

        $criteria5 = $criteria5 . " AND DATE_FORMAT(p.Date, '%Y-%m-%d') >= '" . $datefrom->format('Y-m-d') . "'";
        $criteria5 = $criteria5 . " AND DATE_FORMAT(p.Date, '%Y-%m-%d') <= '" . $dateto->format('Y-m-d') . "'";

        $criteria4 = $criteria4 . reportQueryCompany('trdsalesinvoice');
        if ($request->input('p_Company')) {
            $val = Company::findOrFail($request->input('p_Company'));
            $criteria4 = $criteria4 . " AND co.Oid = '" . $val->Oid . "'";
            $filter = $filter . " AND Company = '" . strtoupper($val->Name) . "'";
        }
        if ($request->input('p_Businesspartner')) {
            $val = BusinessPartner::findOrFail($request->input('p_Businesspartner'));
            $criteria4 = $criteria4 . " AND bp.Oid = '" . $val->Oid . "'";
            $filter = $filter . "businesspartner = '" . strtoupper($val->Name) . "'; ";
        }
        if ($request->input('p_Businesspartnergroup')) {
            $val = BusinessPartnerGroup::findOrFail($request->input('p_Businesspartnergroup'));
            $criteria4 = $criteria4 . " AND bpg.Oid = '" . $val->Oid . "'";
            $filter = $filter . " AND B.PartnerGroup = '" . strtoupper($val->Name) . "'";
        }
        if ($request->input('p_PaymentMethod')) {
            $val = PaymentMethod::findOrFail($request->input('p_paymentmethod'));
            $criteria4 = $criteria4 . " AND pm.Oid = '" . $val->Oid . "'";
            $filter = $filter . " AND PaymentMethod = '" . strtoupper($val->Name) . "'";
        }
        if ($request->input('p_Warehouse')) {
            $val = Warehouse::findOrFail($request->input('p_Warehouse'));
            $criteria4 = $criteria4 . " AND w.Oid = '" . $val->Oid . "'";
            $filter = $filter . " AND Warehouse = '" . strtoupper($val->Name) . "'";
        }
        if ($request->input('p_Employee')) {
            $val = Employee::findOrFail($request->input('p_Employee'));
            $criteria4 = $criteria4 . " AND e.Oid = '" . $val->Oid . "'";
            $filter = $filter . " AND Employee = '" . strtoupper($val->Name) . "'";
        }
        if ($request->input('p_City')) {
            $val = City::findOrFail($request->input('p_City'));
            $criteria4 = $criteria4 . " AND ct.Oid = '" . $val->Oid . "'";
            $filter = $filter . " AND City = '" . strtoupper($val->Name) . "'";
        }
        if ($request->input('p_Item')) {
            $val = Item::findOrFail($request->input('p_Item'));
            $criteria6 = $criteria6 . " AND i.Oid = '" . $val->Oid . "'";
            $filter = $filter . " AND Item = '" . strtoupper($val->Name) . "'";
        }
        // if ($request->input('itemgroup')) {
        //   $val = ItemGroup::findOrFail($request->input('itemgroup'));
        //   $criteria3 = $criteria3." AND ig.Oid = '".$val->Oid."'";
        //   $filter = $filter." AND ItemGroup = '".strtoupper($val->Name)."'";
        // }

        // $criteria2 = $criteria2." AND pi.Date >= '".$firstDayOfMonth."'";
        // $criteria2 = $criteria2." AND pi.Date <= '".$lastDayOfMonth."'";

        if ($request->input('p_User')) {
            $val = User::findOrFail($request->input('p_User'));
            $criteria5 = $criteria5 . " AND p.User = '" . $val->Oid . "'";
            $criteria4 = $criteria4 . " AND u.Oid = '" . $val->Oid . "'";
            $filter = $filter . "Account = '" . strtoupper($val->Name) . "'; ";
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
                $reporttitle = "Sales Invoice Detail List by Item Warehouse";
                $pdf = SnappyPdf::loadView('AdminApi\ReportTrading::pdf.purchaseinvoice_01', compact('data', 'user', 'reportname', 'filter', 'reporttitle'));
                break;
            case 2:
                $reporttitle = 'Sales Invoice Daily By Warehouse';
                $pdf = SnappyPdf::loadView('AdminApi\ReportTrading::pdf.purchaseinvoice_02', compact('data', 'user', 'reportname', 'filter', 'reporttitle'));
                break;
            case 3:
                $reporttitle = 'Sales Invoice Daily by Item';
                $pdf = SnappyPdf::loadView('AdminApi\ReportTrading::pdf.purchaseinvoice_03', compact('data', 'user', 'reportname', 'filter', 'reporttitle'));
                break;
            case 4:
                $reporttitle = 'Sales Invoice Daily by Item Group';
                $pdf = SnappyPdf::loadView('AdminApi\ReportTrading::pdf.purchaseinvoice_03', compact('data', 'user', 'reportname', 'filter', 'reporttitle'));
                break;
            case 5:
                $reporttitle = "Sales Invoice by Item Daily (Warehouse)";
                $pdf = SnappyPdf::loadView('AdminApi\ReportTrading::pdf.purchaseinvoice_04', compact('data', 'user', 'reportname', 'filter', 'reporttitle', 'warehouse'));
                break;
            case 6:
                $reporttitle = "Sales Invoice by Daily Item (Warehouse)";
                $pdf = SnappyPdf::loadView('AdminApi\ReportTrading::pdf.purchaseinvoice_07', compact('data', 'user', 'reportname', 'filter', 'reporttitle', 'warehouse'));
                break;
        }
        $headerHtml = view('AdminApi\Report::pdf.header', compact('user', 'reportname', 'filter', 'reporttitle'))
            ->render();
        $footerHtml = view('AdminApi\Report::pdf.footer', compact('user'))
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
        $data = Warehouse::where('IsActive', 1)->orderBy('Sequence')->whereRaw('Sequence > 0')->get();
        switch ($reportname) {
            case 1:
                return "SELECT
                    co.Code AS Comp,
                    pi.Code,
                    DATE_FORMAT(pi.Date, '%b %e %Y') AS Date,
                    CONCAT(bp.Name, ' - ', bp.Code) AS BusinessPartner,
                    CONCAT(w.Name, ' - ',  w.Code) AS Warehouse,
                    CONCAT(i.Name, ' - ',  i.Code) AS ItemName,
                    c.Code AS CurrencyCode,
                    pid.Quantity AS Qty,   
                    pid.price AS Amount,
                    pid.Quantity * pid.price AS SUBTOTAL,
                    (pid.Quantity * pid.price)  AS DetailTotal,
                    pi.AdditionalAccount,
                    pi.AdditionalAmount,
                    pi.DiscountAccount,
                    pi.DiscountAmount,
                    pi.Note
                    
                    FROM trdsalesinvoice pi
                    LEFT OUTER JOIN trdsalesinvoicedetail pid ON pi.Oid = pid.SalesInvoice
                    LEFT OUTER JOIN mstwarehouse w ON pi.Warehouse = w.Oid
                    LEFT OUTER JOIN mstemployee e ON pi.Employee = e.Oid
                    LEFT OUTER JOIN mstcurrency c ON pi.Currency = c.Oid
                    LEFT OUTER JOIN mstbusinesspartner bp ON pi.BusinessPartner = bp.Oid
                    LEFT OUTER JOIN mstbusinesspartnergroup bpg ON bp.BusinessPartnerGroup = bpg.Oid
                    LEFT OUTER JOIN mstcity ct ON bp.City = ct.Oid
                    LEFT OUTER JOIN mstitem i ON pid.Item = i.Oid
                    LEFT OUTER JOIN mstitemgroup ig ON i.ItemGroup = ig.Oid
                    LEFT OUTER JOIN sysstatus s ON pi.Status = s.Oid
                    LEFT OUTER JOIN company co ON co.oid = pi.Company
                    WHERE pi.GCRecord  IS NULL AND 1=1 AND 3=3 AND 4=4 AND 6=6
                    ORDER BY w.Name, DATE_FORMAT(pi.Date, '%Y%m%d'), pi.Code
                    ";
                break;

            case 2:
                return "SELECT
                    co.Code AS Comp,
                    CONCAT(w.Name, ' - ', w.Code) AS GroupName,
                    DATE_FORMAT(pi.Date, '%d %M %Y') AS Date,
                    COUNT(pi.Oid) AS Qty,
                    c.Code AS CurrencyCode,
                    SUM(IFNULL(pi.TotalAmount,0) + IFNULL(pi.DiscountAmount,0) - IFNULL(pi.AdditionalAmount,0) ) AS Subtotal,
                    SUM(IFNULL(pi.DiscountAmount,0) ) AS Discount,
                    SUM(IFNULL(pi.AdditionalAmount,0) ) AS Additional,
                    SUM(IFNULL(pi.TotalAmount,0 )) AS TotalAmount
                    FROM trdsalesinvoice pi 
                    LEFT OUTER JOIN mstbusinesspartner bp ON pi.BusinessPartner = bp.Oid
                    LEFT OUTER JOIN mstbusinesspartnergroup bpg ON bp.BusinessPartnerGroup = bpg.Oid
                    LEFT OUTER JOIN mstcity ct ON bp.City = ct.Oid
                    LEFT OUTER JOIN mstwarehouse w ON pi.Warehouse = w.Oid
                    LEFT OUTER JOIN mstemployee e ON pi.Employee = e.Oid
                    LEFT OUTER JOIN mstcurrency c ON pi.Currency = c.Oid
                    LEFT OUTER JOIN sysstatus s ON pi.Status = s.Oid
                    LEFT OUTER JOIN company co ON co.oid = pi.Company
                    WHERE pi.GCRecord IS NULL AND 1=1 AND 4=4
                    GROUP BY w.Name, w.Code, c.Code, DATE_FORMAT(pi.Date, '%Y%m%d')
                    ";
                break;
            case 3:
                return "SELECT
                    co.Code AS Comp,
                    CONCAT(i.Name, ' - ', i.Code) AS GroupName,
                    DATE_FORMAT(pi.Date, '%e %b %Y') AS Date,
                    CONCAT(w.Name, ' - ',  w.Code) AS Warehouse,
                    c.Code AS CurrencyCode,
                    (IFNULL(pid.Quantity,0)) AS Qty,
                    (IFNULL(pid.Price,0)) AS Amount,
                    (IFNULL(pid.Quantity,0) * IFNULL(pid.Price,0)) AS DetailSubtotal,
                    (IFNULL(pid.DiscountAmount,0) + IFNULL(pid.DiscountPercentage,0)) AS DetailDiscount,
                    ((IFNULL(pid.Price,0) * IFNULL(pid.Quantity,0)) - IFNULL(pid.DiscountAmount,0) - IFNULL(pid.DiscountPercentage,0)) AS TotalAmount
                    
                    FROM trdsalesinvoice pi 
                    LEFT OUTER JOIN trdsalesinvoicedetail  pid ON pi.Oid = pid.SalesInvoice
                    LEFT OUTER JOIN mstbusinesspartner bp ON pi.BusinessPartner = bp.Oid
                    LEFT OUTER JOIN mstbusinesspartnergroup bpg ON bp.BusinessPartnerGroup = bpg.Oid
                    LEFT OUTER JOIN mstcity ct ON bp.City = ct.Oid
                    LEFT OUTER JOIN mstitem i ON pid.Item = i.Oid
                    LEFT OUTER JOIN mstitemgroup ig ON i.ItemGroup = ig.Oid
                    LEFT OUTER JOIN mstwarehouse w ON pi.Warehouse = w.Oid
                    LEFT OUTER JOIN mstemployee e ON pi.Employee = e.Oid
                    LEFT OUTER JOIN mstcurrency c ON pi.Currency = c.Oid
                    LEFT OUTER JOIN sysstatus s ON pi.Status = s.Oid
                    LEFT OUTER JOIN company co ON co.oid = pi.Company
                    WHERE pi.GCRecord IS NULL AND i.Oid IS NOT NULL AND 1=1 AND 4=4
                    GROUP BY i.Name, i.Code, c.Code, DATE_FORMAT(pi.Date, '%Y%m%d')
                    ";
                break;
            case 4:
                return "SELECT
                    co.Code AS Comp,
                    CONCAT(ig.Name, ' - ', ig.Code) AS GroupName,
                    DATE_FORMAT(pi.Date, '%e %b %Y') AS Date,
                    CONCAT(w.Name, ' - ',  w.Code) AS Warehouse,
                    c.Code AS CurrencyCode,
                    SUM(IFNULL(pid.Quantity,0)) AS Qty,
                    SUM(IFNULL(pid.TotalAmount,0)) AS Amount,
                    SUM(IFNULL(pid.Quantity,0) * IFNULL(pid.TotalAmount,0)) AS DetailSubtotal,
                    SUM(IFNULL(pid.DiscountAmount,0) + IFNULL(pid.DiscountPercentage,0)) AS DetailDiscount,
                    SUM((IFNULL(pid.TotalAmount,0) * IFNULL(pid.Quantity,0)) - IFNULL(pid.DiscountAmount,0) - IFNULL(pid.DiscountPercentage,0)) AS TotalAmount
                    
                    FROM trdsalesinvoice pi 
                    LEFT OUTER JOIN trdsalesinvoicedetail  pid ON pi.Oid = pid.SalesInvoice
                    LEFT OUTER JOIN mstbusinesspartner bp ON pi.BusinessPartner = bp.Oid
                    LEFT OUTER JOIN mstbusinesspartnergroup bpg ON bp.BusinessPartnerGroup = bpg.Oid
                    LEFT OUTER JOIN mstcity ct ON bp.City = ct.Oid
                    LEFT OUTER JOIN mstitem i ON pid.Item = i.Oid
                    LEFT OUTER JOIN mstitemgroup ig ON i.ItemGroup = ig.Oid
                    LEFT OUTER JOIN mstwarehouse w ON pi.Warehouse = w.Oid
                    LEFT OUTER JOIN mstemployee e ON pi.Employee = e.Oid
                    LEFT OUTER JOIN mstcurrency c ON pi.Currency = c.Oid
                    LEFT OUTER JOIN sysstatus s ON pi.Status = s.Oid
                    LEFT OUTER JOIN company co ON co.oid = pi.Company
                    WHERE pi.GCRecord IS NULL AND 1=1 AND 4=4
                    GROUP BY i.Name, i.Code, c.Code, DATE_FORMAT(pi.Date, '%Y%m%d')
                    ";
                break;
            case 5:
                $wh1 = 'satu';
                $wh2 = 'dua';
                $wh3 = 'tiga';
                $wh4 = 'empat';
                $wh5 = 'lima';
                logger($data->count());
                if ($data->count() >= 1) $wh1 = $data[0]->Code;
                if ($data->count() >= 2) $wh2 = $data[1]->Code;
                if ($data->count() >= 3) $wh3 = $data[2]->Code;
                if ($data->count() >= 4) $wh4 = $data[3]->Code;
                if ($data->count() >= 5) $wh5 = $data[4]->Code;

                return "SELECT
                    co.Code AS Comp,
                    CONCAT(i.Name, ' - ', i.Code) AS GroupName,
                    c.Code AS CurrencyCode,
                    DATE_FORMAT(pi.Date, '%e %b %Y') AS Date,
                    DATE_FORMAT(pi.Date, '%Y%m%d') AS DateOrder,
                    SUM(CASE WHEN w.Code = '" . $wh1 . "' THEN IFNULL(pid.Quantity,0) ELSE 0 END) AS w1qty,
                    SUM(CASE WHEN w.Code = '" . $wh1 . "' THEN (IFNULL(pid.TotalAmount,0) * IFNULL(pid.Quantity,0)) - IFNULL(pid.DiscountAmount,0) - IFNULL(pid.DiscountPercentage,0) ELSE 0 END) AS w1amt,
                    SUM(CASE WHEN w.Code = '" . $wh2 . "' THEN IFNULL(pid.Quantity,0) ELSE 0 END) AS w2qty,
                    SUM(CASE WHEN w.Code = '" . $wh2 . "' THEN (IFNULL(pid.TotalAmount,0) * IFNULL(pid.Quantity,0)) - IFNULL(pid.DiscountAmount,0) - IFNULL(pid.DiscountPercentage,0) ELSE 0 END) AS w2amt,
                    SUM(CASE WHEN w.Code = '" . $wh3 . "' THEN IFNULL(pid.Quantity,0) ELSE 0 END) AS w3qty,
                    SUM(CASE WHEN w.Code = '" . $wh3 . "' THEN (IFNULL(pid.TotalAmount,0) * IFNULL(pid.Quantity,0)) - IFNULL(pid.DiscountAmount,0) - IFNULL(pid.DiscountPercentage,0) ELSE 0 END) AS w3amt,
                    SUM(CASE WHEN w.Code = '" . $wh4 . "' THEN IFNULL(pid.Quantity,0) ELSE 0 END) AS w4qty,
                    SUM(CASE WHEN w.Code = '" . $wh4 . "' THEN (IFNULL(pid.TotalAmount,0) * IFNULL(pid.Quantity,0)) - IFNULL(pid.DiscountAmount,0) - IFNULL(pid.DiscountPercentage,0) ELSE 0 END) AS w4amt,
                    SUM(CASE WHEN w.Code = '" . $wh5 . "' THEN IFNULL(pid.Quantity,0) ELSE 0 END) AS w5qty,
                    SUM(CASE WHEN w.Code = '" . $wh5 . "' THEN (IFNULL(pid.TotalAmount,0) * IFNULL(pid.Quantity,0)) - IFNULL(pid.DiscountAmount,0) - IFNULL(pid.DiscountPercentage,0) ELSE 0 END) AS w5amt
                    FROM trdsalesinvoice pi 
                    LEFT OUTER JOIN trdsalesinvoicedetail pid ON pi.Oid = pid.SalesInvoice
                    LEFT OUTER JOIN mstbusinesspartner bp ON pi.BusinessPartner = bp.Oid
                    LEFT OUTER JOIN mstbusinesspartnergroup bpg ON bp.BusinessPartnerGroup = bpg.Oid
                    LEFT OUTER JOIN mstcity ct ON bp.City = ct.Oid
                    LEFT OUTER JOIN mstitem i ON pid.Item = i.Oid
                    LEFT OUTER JOIN mstitemgroup ig ON i.ItemGroup = ig.Oid
                    LEFT OUTER JOIN mstwarehouse w ON pi.Warehouse = w.Oid
                    LEFT OUTER JOIN mstemployee e ON pi.Employee = e.Oid
                    LEFT OUTER JOIN mstcurrency c ON pi.Currency = c.Oid
                    LEFT OUTER JOIN company co ON co.oid = pi.Company
                    LEFT OUTER JOIN sysstatus s ON pi.Status = s.Oid
                    WHERE pi.GCRecord IS NULL AND pid.Oid IS NOT NULL AND 1=1 AND 3=3 AND 4=4 AND 6=6
                    GROUP BY i.Name, i.Code, c.Code, DATE_FORMAT(pi.Date, '%Y%m%d')
                    ";
                break;
            case 6;
                $wh1 = 'satu';
                $wh2 = 'dua';
                $wh3 = 'tiga';
                $wh4 = 'empat';
                $wh5 = 'lima';
                logger($data->count());
                if ($data->count() >= 1) $wh1 = $data[0]->Code;
                if ($data->count() >= 2) $wh2 = $data[1]->Code;
                if ($data->count() >= 3) $wh3 = $data[2]->Code;
                if ($data->count() >= 4) $wh4 = $data[3]->Code;
                if ($data->count() >= 5) $wh5 = $data[4]->Code;

                return "SELECT 
                    co.Code AS Comp,
                    CONCAT(i.Name, ' - ', i.Code) AS GroupName,
                    c.Code AS CurrencyCode,
                    DATE_FORMAT(pi.Date, '%e %b %Y') AS Date,
                    DATE_FORMAT(pi.Date, '%Y%m%d') AS DateOrder,
                    SUM(CASE WHEN w.Code = '" . $wh1 . "' THEN IFNULL(pid.Quantity,0) ELSE 0 END) AS w1qty,
                    SUM(CASE WHEN w.Code = '" . $wh1 . "' THEN (IFNULL(pid.TotalAmount,0) * IFNULL(pid.Quantity,0)) - IFNULL(pid.DiscountAmount,0) - IFNULL(pid.DiscountPercentage,0) ELSE 0 END) AS w1amt,
                    SUM(CASE WHEN w.Code = '" . $wh2 . "' THEN IFNULL(pid.Quantity,0) ELSE 0 END) AS w2qty,
                    SUM(CASE WHEN w.Code = '" . $wh2 . "' THEN (IFNULL(pid.TotalAmount,0) * IFNULL(pid.Quantity,0)) - IFNULL(pid.DiscountAmount,0) - IFNULL(pid.DiscountPercentage,0) ELSE 0 END) AS w2amt,
                    SUM(CASE WHEN w.Code = '" . $wh3 . "' THEN IFNULL(pid.Quantity,0) ELSE 0 END) AS w3qty,
                    SUM(CASE WHEN w.Code = '" . $wh3 . "' THEN (IFNULL(pid.TotalAmount,0) * IFNULL(pid.Quantity,0)) - IFNULL(pid.DiscountAmount,0) - IFNULL(pid.DiscountPercentage,0) ELSE 0 END) AS w3amt,
                    SUM(CASE WHEN w.Code = '" . $wh4 . "' THEN IFNULL(pid.Quantity,0) ELSE 0 END) AS w4qty,
                    SUM(CASE WHEN w.Code = '" . $wh4 . "' THEN (IFNULL(pid.TotalAmount,0) * IFNULL(pid.Quantity,0)) - IFNULL(pid.DiscountAmount,0) - IFNULL(pid.DiscountPercentage,0) ELSE 0 END) AS w4amt,
                    SUM(CASE WHEN w.Code = '" . $wh5 . "' THEN IFNULL(pid.Quantity,0) ELSE 0 END) AS w5qty,
                    SUM(CASE WHEN w.Code = '" . $wh5 . "' THEN (IFNULL(pid.TotalAmount,0) * IFNULL(pid.Quantity,0)) - IFNULL(pid.DiscountAmount,0) - IFNULL(pid.DiscountPercentage,0) ELSE 0 END) AS w5amt
                    FROM trdsalesinvoice pi 
                    LEFT OUTER JOIN trdsalesinvoicedetail pid ON pi.Oid = pid.SalesInvoice
                    LEFT OUTER JOIN mstbusinesspartner bp ON pi.BusinessPartner = bp.Oid
                    LEFT OUTER JOIN mstbusinesspartnergroup bpg ON bp.BusinessPartnerGroup = bpg.Oid
                    LEFT OUTER JOIN mstcity ct ON bp.City = ct.Oid
                    LEFT OUTER JOIN mstitem i ON pid.Item = i.Oid
                    LEFT OUTER JOIN mstitemgroup ig ON i.ItemGroup = ig.Oid
                    LEFT OUTER JOIN mstwarehouse w ON pi.Warehouse = w.Oid
                    LEFT OUTER JOIN mstemployee e ON pi.Employee = e.Oid
                    LEFT OUTER JOIN mstcurrency c ON pi.Currency = c.Oid
                    LEFT OUTER JOIN company co ON co.oid = pi.Company
                    LEFT OUTER JOIN sysstatus s ON pi.Status = s.Oid
                    WHERE pi.GCRecord IS NULL AND 1=1 AND 3=3 AND 4=4 AND 6=6
                    GROUP BY DATE_FORMAT(pi.Date, '%Y%m%d'), i.Name, i.Code, c.Code
                    ";
                break;
        }
        return "";
    }
}
