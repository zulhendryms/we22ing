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
use App\Core\Master\Entities\Warehouse;
use App\Core\Master\Entities\Employee;
use App\Core\Master\Entities\City;
use App\Core\Master\Entities\BusinessPartnerGroup;
use App\Core\Master\Entities\Item;
use App\Core\Master\Entities\Company;
use Carbon\Carbon;

class ReportPurchaseInvoiceController extends Controller
{
    protected $reportService;
    protected $reportName;
    public function __construct(ReportService $reportService)
    {
        $this->reportName = 'purchaseinvoice';
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

        $criteria4 = $criteria4 . reportQueryCompany('trdpurchaseinvoice');
        if ($request->input('p_Company')) {
            $val = Company::findOrFail($request->input('p_Company'));
            $criteria4 = $criteria4 . " AND co.Oid = '" . $val->Oid . "'";
            $filter = $filter . " AND Company = '" . strtoupper($val->Code) . "'";
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
            $criteria4 = $criteria4 . " AND i.Oid = '" . $val->Oid . "'";
            $filter = $filter . " AND Item = '" . strtoupper($val->Name) . "'";
        }

        if ($filter) $filter = substr($filter, 0);
        if ($criteria1) $query = str_replace(" AND 1=1", $criteria1, $query);
        // if ($criteria2) $query = str_replace(" AND 2=2",$criteria2,$query);
        if ($criteria3) $query = str_replace(" AND 3=3", $criteria3, $query);
        if ($criteria4) $query = str_replace(" AND 4=4", $criteria4, $query);
        if ($criteria5) $query = str_replace(" AND 5=5", $criteria5, $query);
        if ($criteria6) $query = str_replace(" AND 6=6", $criteria6, $query);


        $data = DB::select($query);
        
        switch ($reportname) {
            case 1:
                $reporttitle = "Purchase Invoice Detail List by Item Warehouse";
                $pdf = SnappyPdf::loadView('AdminApi\ReportTrading::pdf.purchaseinvoice_01', compact('data', 'user', 'reportname', 'filter', 'reporttitle'));
                break;
            case 2:
                $reporttitle = 'Purchase Invoice Daily By Warehouse';
                $pdf = SnappyPdf::loadView('AdminApi\ReportTrading::pdf.purchaseinvoice_02', compact('data', 'user', 'reportname', 'filter', 'reporttitle'));
                break;
            case 3:
                $reporttitle = 'Purchase Invoice Daily by Item';
                $pdf = SnappyPdf::loadView('AdminApi\ReportTrading::pdf.purchaseinvoice_03', compact('data', 'user', 'reportname', 'filter', 'reporttitle'));
                break;
            case 4:
                $reporttitle = 'Purchase Invoice Daily by Item Group';
                $pdf = SnappyPdf::loadView('AdminApi\ReportTrading::pdf.purchaseinvoice_03', compact('data', 'user', 'reportname', 'filter', 'reporttitle'));
                break;
            case 5:
                $reporttitle = "Purchase Invoice by Item Daily (Warehouse)";
                $pdf = SnappyPdf::loadView('AdminApi\ReportTrading::pdf.purchaseinvoice_04', compact('data', 'user', 'reportname', 'filter', 'reporttitle', 'warehouse'));
                break;
            case 6:
                $reporttitle = "Purchase Invoice by Daily Item (Warehouse)";
                $pdf = SnappyPdf::loadView('AdminApi\ReportTrading::pdf.purchaseinvoice_07', compact('data', 'user', 'reportname', 'filter', 'reporttitle', 'warehouse'));
                break;
            case 7:
                $reporttitle = "Purchase Invoice Detail List by Item Group";
                $pdf = SnappyPdf::loadView('AdminApi\ReportTrading::pdf.purchaseinvoice_05', compact('data', 'user', 'reportname', 'filter', 'reporttitle'));
                break;
                // case 7:
                //     $reporttitle = "Faktur Purchase Invoice";
                //     $pdf = SnappyPdf::loadView('AdminApi\ReportTrading::pdf.purchaseinvoice_05', compact('data', 'date', 'reportname','filter', 'reporttitle'));
                // break;
                //end
            // case 8:
            //     $reporttitle = "Report Cash Transaction";
            //     $pdf = SnappyPdf::loadView('AdminApi\ReportTrading::pdf.purchaseinvoice_06', compact('data', 'date', 'reportname', 'filter', 'reporttitle'));
            //     break;
            // case 9:
            //     $reporttitle = "Report Cash Bank";
            //     $pdf = SnappyPdf::loadView('AdminApi\ReportTrading::pdf.purchaseinvoice_08', compact('data', 'date', 'reportname', 'filter', 'reporttitle'));
            //     break;
            // case 10:
            //     $reporttitle = "Report Expense";
            //     $pdf = SnappyPdf::loadView('AdminApi\ReportTrading::pdf.purchaseinvoice_09', compact('data', 'date', 'reportname', 'filter', 'reporttitle'));
            //     break;
            // case 11:
            //     $reporttitle = "Report Income";
            //     $pdf = SnappyPdf::loadView('AdminApi\ReportTrading::pdf.purchaseinvoice_09', compact('data', 'date', 'reportname', 'filter', 'reporttitle'));
            //     break;
            // case 12:
            //     $reporttitle = "Report Payment";
            //     $pdf = SnappyPdf::loadView('AdminApi\ReportTrading::pdf.purchaseinvoice_10', compact('data', 'date', 'reportname', 'filter', 'reporttitle'));
            //     break;
            // case 13:
            //     $reporttitle = "Report Receipt";
            //     $pdf = SnappyPdf::loadView('AdminApi\ReportTrading::pdf.purchaseinvoice_11', compact('data', 'date', 'reportname', 'filter', 'reporttitle'));
            //     break;
            // case 14:
            //     $reporttitle = "Report Transfer";
            //     $pdf = SnappyPdf::loadView('AdminApi\ReportTrading::pdf.purchaseinvoice_12', compact('data', 'date', 'reportname', 'filter', 'reporttitle'));
            //     break;
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
        $data = Warehouse::where('IsActive', 1)->orderBy('Sequence')->whereRaw('Sequence > 0')->get();
        switch ($reportname) {
            case 1:
                return "SELECT
                    pi.Oid,
                    pi.Code,
                    co.Code AS Comp,
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
                    
                    FROM trdpurchaseinvoice pi
                    LEFT OUTER JOIN trdpurchaseinvoicedetail pid ON pi.Oid = pid.PurchaseInvoice
                    LEFT OUTER JOIN mstwarehouse w ON pi.Warehouse = w.Oid
                    LEFT OUTER JOIN mstemployee e ON pi.Employee = e.Oid
                    LEFT OUTER JOIN mstcurrency c ON pi.Currency = c.Oid
                    LEFT OUTER JOIN mstbusinesspartner bp ON pi.BusinessPartner = bp.Oid
                    LEFT OUTER JOIN mstbusinesspartnergroup bpg ON bp.BusinessPartnerGroup = bpg.Oid
                    LEFT OUTER JOIN mstcity ct ON bp.City = ct.Oid
                    LEFT OUTER JOIN mstitem i ON pid.Item = i.Oid
                    LEFT OUTER JOIN mstitemgroup ig ON i.ItemGroup = ig.Oid
                    LEFT OUTER JOIN sysstatus s ON pi.Status = s.Oid
                    LEFT OUTER JOIN company co ON pi.Company = co.Oid
                    WHERE pi.GCRecord IS NULL AND s.Code IN ('paid', 'complete','posted') AND 1=1 AND 3=3 AND 4=4 AND 6=6
                    ORDER BY w.Name, DATE_FORMAT(pi.Date, '%Y%m%d'), pi.Code, pi.Oid
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

                    FROM trdpurchaseinvoice pi 
                    LEFT OUTER JOIN mstbusinesspartner bp ON pi.BusinessPartner = bp.Oid
                    LEFT OUTER JOIN mstbusinesspartnergroup bpg ON bp.BusinessPartnerGroup = bpg.Oid
                    LEFT OUTER JOIN mstcity ct ON bp.City = ct.Oid
                    LEFT OUTER JOIN mstwarehouse w ON pi.Warehouse = w.Oid
                    LEFT OUTER JOIN mstemployee e ON pi.Employee = e.Oid
                    LEFT OUTER JOIN mstcurrency c ON pi.Currency = c.Oid
                    LEFT OUTER JOIN company co ON pi.Company = co.Oid
                    LEFT OUTER JOIN sysstatus s ON pi.Status = s.Oid
                    WHERE pi.GCRecord IS NULL AND s.Code IN ('paid', 'complete','posted') AND 1=1 AND 4=4
                    GROUP BY w.Name, w.Code, c.Code, DATE_FORMAT(pi.Date, '%Y%m%d')";
                break;
            case 3:
                return "SELECT
                    co.Code AS Comp,
                    CONCAT(i.Name, ' - ', i.Code) AS GroupName,
                    DATE_FORMAT(pi.Date, '%e %b %Y') AS Date,
                    CONCAT(w.Name, ' - ',  w.Code) AS Warehouse,
                    c.Code AS CurrencyCode,
                    SUM(IFNULL(pid.Quantity,0)) AS Qty,
                    SUM(IFNULL(pid.TotalAmount,0)) AS Amount,
                    SUM(IFNULL(pid.Quantity,0) * IFNULL(pid.TotalAmount,0)) AS DetailSubtotal,
                    SUM(IFNULL(pid.DiscountAmount,0) + IFNULL(pid.DiscountPercentage,0)) AS DetailDiscount,
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
                    LEFT OUTER JOIN sysstatus s ON pi.Status = s.Oid
                    LEFT OUTER JOIN company co ON pi.Company = co.Oid
                    WHERE pi.GCRecord IS NULL AND s.Code IN ('paid', 'complete','posted') AND 1=1 AND 4=4
                    GROUP BY i.Name, i.Code, c.Code, DATE_FORMAT(pi.Date, '%Y%m%d')";
                break;

            case 4:
                return " SELECT
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
                    LEFT OUTER JOIN sysstatus s ON pi.Status = s.Oid
                    LEFT OUTER JOIN company co ON pi.Company = co.Oid
                    WHERE pi.GCRecord IS NULL AND s.Code IN ('paid', 'complete','posted') AND 1=1 AND 4=4
                    GROUP BY ig.Name, ig.Code, c.Code, DATE_FORMAT(pi.Date, '%Y%m%d')";
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
                    FROM trdpurchaseinvoice pi 
                    LEFT OUTER JOIN trdpurchaseinvoicedetail pid ON pi.Oid = pid.PurchaseInvoice
                    LEFT OUTER JOIN mstbusinesspartner bp ON pi.BusinessPartner = bp.Oid
                    LEFT OUTER JOIN mstbusinesspartnergroup bpg ON bp.BusinessPartnerGroup = bpg.Oid
                    LEFT OUTER JOIN mstcity ct ON bp.City = ct.Oid
                    LEFT OUTER JOIN mstitem i ON pid.Item = i.Oid
                    LEFT OUTER JOIN mstitemgroup ig ON i.ItemGroup = ig.Oid
                    LEFT OUTER JOIN mstwarehouse w ON pi.Warehouse = w.Oid
                    LEFT OUTER JOIN mstemployee e ON pi.Employee = e.Oid
                    LEFT OUTER JOIN mstcurrency c ON pi.Currency = c.Oid
                    LEFT OUTER JOIN sysstatus s ON pi.Status = s.Oid
                    LEFT OUTER JOIN company co ON pi.Company = co.Oid
                    WHERE pi.GCRecord IS NULL AND s.Code IN ('paid', 'complete','posted') AND 1=1 AND 3=3 AND 4=4 AND 6=6
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
                    FROM trdpurchaseinvoice pi 
                    LEFT OUTER JOIN trdpurchaseinvoicedetail pid ON pi.Oid = pid.PurchaseInvoice
                    LEFT OUTER JOIN mstbusinesspartner bp ON pi.BusinessPartner = bp.Oid
                    LEFT OUTER JOIN mstbusinesspartnergroup bpg ON bp.BusinessPartnerGroup = bpg.Oid
                    LEFT OUTER JOIN mstcity ct ON bp.City = ct.Oid
                    LEFT OUTER JOIN mstitem i ON pid.Item = i.Oid
                    LEFT OUTER JOIN mstitemgroup ig ON i.ItemGroup = ig.Oid
                    LEFT OUTER JOIN mstwarehouse w ON pi.Warehouse = w.Oid
                    LEFT OUTER JOIN mstemployee e ON pi.Employee = e.Oid
                    LEFT OUTER JOIN mstcurrency c ON pi.Currency = c.Oid
                    LEFT OUTER JOIN sysstatus s ON pi.Status = s.Oid
                    LEFT OUTER JOIN company co ON pi.Company = co.Oid
                    WHERE pi.GCRecord IS NULL AND s.Code IN ('paid', 'complete','posted') AND 1=1 AND 3=3 AND 4=4 AND 6=6
                    GROUP BY DATE_FORMAT(pi.Date, '%Y%m%d'), i.Name, i.Code, c.Code
                    ";
                break;
            case 7:
                return "SELECT
                    pi.Code,
                    co.Code AS Comp,
                    DATE_FORMAT(pi.Date, '%b %e %Y') AS Date,
                    CONCAT(bp.Name, ' - ', bp.Code) AS BusinessPartner,
                    CONCAT(ig.Name, ' - ',  ig.Code) AS ItemGroup,
                    SUM(IFNULL(pid.Quantity,0)) AS Qty,  
                    SUM(IFNULL(pid.Quantity,0) * IFNULL(pid.price,0))  AS DetailTotal
                    FROM trdpurchaseinvoice pi
                    LEFT OUTER JOIN trdpurchaseinvoicedetail pid ON pi.Oid = pid.PurchaseInvoice
                    LEFT OUTER JOIN mstwarehouse w ON pi.Warehouse = w.Oid
                    LEFT OUTER JOIN mstemployee e ON pi.Employee = e.Oid
                    LEFT OUTER JOIN mstcurrency c ON pi.Currency = c.Oid
                    LEFT OUTER JOIN mstbusinesspartner bp ON pi.BusinessPartner = bp.Oid
                    LEFT OUTER JOIN mstbusinesspartnergroup bpg ON bp.BusinessPartnerGroup = bpg.Oid
                    LEFT OUTER JOIN mstcity ct ON bp.City = ct.Oid
                    LEFT OUTER JOIN mstitem i ON pid.Item = i.Oid
                    LEFT OUTER JOIN mstitemgroup ig ON i.ItemGroup = ig.Oid
                    LEFT OUTER JOIN sysstatus s ON pi.Status = s.Oid
                    LEFT OUTER JOIN company co ON pi.Company = co.Oid
                    WHERE pi.GCRecord IS NULL  AND s.Code IN ('paid', 'complete','posted') AND 1=1 AND 3=3 AND 4=4 AND 6=6
                    GROUP BY ig.Name, DATE_FORMAT(pi.Date, '%Y%m%d'), pi.Code
                    ";
                break;
            // case 8:
            //     return "SELECT 
            //       DATE_FORMAT(ps.Date, '%e %b %Y') AS Date,
            //       m1.Name AS Payment,
            //       w.Code AS Warehouse,
            //       psa.Note AS Description,
            //       psa.Amount AS Amount,
            //       psa.AmountBase AS AmountBase,
            //       psa.Currency AS Currency,
            //       CASE psa.Type WHEN '1' THEN  'Cash In' WHEN '2' THEN  'Cash Out' ELSE 'Opening Balance' END AS Type
                  
            //       FROM possessionamount psa 
            //       LEFT OUTER JOIN possession ps ON ps.Oid = psa.POSSession
            //       LEFT OUTER JOIN user u ON u.Oid = psa.User
            //       LEFT OUTER JOIN possessionamounttype p ON psa.POSSessionAmountType = p.Oid
            //       LEFT OUTER JOIN mstcurrency m ON psa.Currency = m.Oid
            //       LEFT OUTER JOIN mstpaymentmethod m1 ON psa.PaymentMethod = m1.Oid
            //       LEFT OUTER JOIN mstwarehouse w ON ps.Warehouse = w.Oid
            //       LEFT OUTER JOIN sysstatus s ON ps.Status = s.Oid
            //       WHERE ps.GCRecord IS NULL AND s.Code IN ('paid', 'complete','posted')  AND 4=4
            //       GROUP BY DATE_FORMAT(ps.Date, '%Y%m%d')";
            //     break;
            // case 9;
            //     return "SELECT
            //     ac.Code AS Code,
            //     DATE_FORMAT(ac.Date, '%e %b %Y') AS Date,
            //     a.Name AS Account,
            //     ac.Description AS Description,
            //     ac.TotalAmount AS Amount,
            //     ac.TotalAmount AS Amount1,
            //     CASE ac.Type WHEN '0'  THEN 'Income' WHEN '1' THEN 'Expense' WHEN '2' 
            //                  THEN 'Receipt' WHEN '3' THEN 'Payment' WHEN '4' THEN 'Transfer'
            //                  END AS Type
            //   FROM acccashbank ac
            //     LEFT OUTER JOIN accaccount a ON ac.Account = a.Oid
            //     LEFT OUTER JOIN sysstatus s ON ac.Status = s.Oid
            //     LEFT OUTER JOIN mstcurrency m ON ac.Currency = m.Oid
            //     LEFT OUTER JOIN mstbusinesspartner bp ON ac.BusinessPartner = bp.Oid
            //   WHERE ac.GCRecord IS NULL  AND 4=4
            //   GROUP BY DATE_FORMAT(Date, '%Y%m%d');";
            //     break;
            // case 10;
            //     return "SELECT
            // DATE_FORMAT(pi.Date, '%b %d %Y') AS Date,
            // pi.Code AS Code,
            // c.Code AS CurrencyCode,
            // a.Name AS Account,
            // a1.Name AS Description,
            // SUM(IFNULL(pid.TotalAmount,0)) AS Amount,
            // SUM(IFNULL(pid.TotalAmount,0)) AS Amount1
            
            // FROM trdpurchaseinvoice pi 
            // LEFT OUTER JOIN trdpurchaseinvoicedetail  pid ON pi.Oid = pid.PurchaseInvoice
            // LEFT OUTER JOIN mstbusinesspartner bp ON pi.BusinessPartner = bp.Oid
            // LEFT OUTER JOIN mstbusinesspartnergroup bpg ON bp.BusinessPartnerGroup = bpg.Oid
            // LEFT OUTER JOIN mstcity ct ON bp.City = ct.Oid
            // LEFT OUTER JOIN mstitem i ON pid.Item = i.Oid
            // LEFT OUTER JOIN mstitemgroup ig ON i.ItemGroup = ig.Oid
            // LEFT OUTER JOIN mstemployee e ON pi.Employee = e.Oid
            // LEFT OUTER JOIN mstcurrency c ON pi.Currency = c.Oid
            // LEFT OUTER JOIN sysstatus s ON pi.Status = s.Oid
            // LEFT OUTER JOIN accaccount a ON pi.Account = a.Oid
            // LEFT OUTER JOIN accaccountgroup a1 ON a.AccountGroup = a1.Oid
            // WHERE pi.GCRecord IS NULL  AND 1=1 AND 4=4
            // GROUP BY a1.Name, a.Name, c.Code, DATE_FORMAT(pi.Date, '%Y%m%d')";
            //     break;
            // case 11;
            //     return "SELECT
            // DATE_FORMAT(pi.Date, '%b %d %Y') AS Date,
            // pi.Code AS Code,
            // c.Code AS CurrencyCode,
            // a.Name AS Account,
            // a1.Name AS Description,
            // SUM(IFNULL(pid.TotalAmount,0)) AS Amount,
            // SUM(IFNULL(pid.TotalAmount,0)) AS Amount1
            
            // FROM trdpurchaseinvoice pi 
            // LEFT OUTER JOIN trdpurchaseinvoicedetail  pid ON pi.Oid = pid.PurchaseInvoice
            // LEFT OUTER JOIN mstbusinesspartner bp ON pi.BusinessPartner = bp.Oid
            // LEFT OUTER JOIN mstbusinesspartnergroup bpg ON bp.BusinessPartnerGroup = bpg.Oid
            // LEFT OUTER JOIN mstcity ct ON bp.City = ct.Oid
            // LEFT OUTER JOIN mstitem i ON pid.Item = i.Oid
            // LEFT OUTER JOIN mstitemgroup ig ON i.ItemGroup = ig.Oid
            // LEFT OUTER JOIN mstemployee e ON pi.Employee = e.Oid
            // LEFT OUTER JOIN mstcurrency c ON pi.Currency = c.Oid
            // LEFT OUTER JOIN sysstatus s ON pi.Status = s.Oid
            // LEFT OUTER JOIN accaccount a ON pi.Account = a.Oid
            // LEFT OUTER JOIN accaccountgroup a1 ON a.AccountGroup = a1.Oid
            // WHERE pi.GCRecord IS NULL  AND 1=1 AND 4=4
            // GROUP BY a1.Name, a.Name, c.Code, DATE_FORMAT(pi.Date, '%Y%m%d')";
            //     break;
            // case 12;
            //     return "SELECT
            // DATE_FORMAT(pi.Date, '%b %d %Y') AS Date,
            // pi.Code AS Code,
            // c.Code AS CurrencyCode,
            // a.Name AS Account,
            // a1.Name AS Description,
            // bp.Name AS BusinessPartner,
            // SUM(IFNULL(pid.TotalAmount,0)) AS Amount,
            // SUM(IFNULL(pid.TotalAmount,0)) AS Amount1,
            // SUM(IFNULL(pid.DiscountAmount,0) + IFNULL(pid.DiscountPercentage,0)) AS DetailDiscount
            
            // FROM trdpurchaseinvoice pi 
            // LEFT OUTER JOIN trdpurchaseinvoicedetail  pid ON pi.Oid = pid.PurchaseInvoice
            // LEFT OUTER JOIN mstbusinesspartner bp ON pi.BusinessPartner = bp.Oid
            // LEFT OUTER JOIN mstbusinesspartnergroup bpg ON bp.BusinessPartnerGroup = bpg.Oid
            // LEFT OUTER JOIN mstcity ct ON bp.City = ct.Oid
            // LEFT OUTER JOIN mstitem i ON pid.Item = i.Oid
            // LEFT OUTER JOIN mstitemgroup ig ON i.ItemGroup = ig.Oid
            // LEFT OUTER JOIN mstemployee e ON pi.Employee = e.Oid
            // LEFT OUTER JOIN mstcurrency c ON pi.Currency = c.Oid
            // LEFT OUTER JOIN sysstatus s ON pi.Status = s.Oid
            // LEFT OUTER JOIN accaccount a ON pi.Account = a.Oid
            // LEFT OUTER JOIN accaccountgroup a1 ON a.AccountGroup = a1.Oid
            // WHERE pi.GCRecord IS NULL  AND 1=1 AND 4=4
            // GROUP BY a1.Name, a.Name, c.Code, DATE_FORMAT(pi.Date, '%Y%m%d')";
            //     break;
            // case 13;
            //     return "SELECT
            // DATE_FORMAT(pi.Date, '%b %d %Y') AS Date,
            // pi.Code AS Code,
            // c.Code AS CurrencyCode,
            // a.Name AS Account,
            // a1.Name AS Description,
            // bp.Name AS BusinessPartner,
            // SUM(IFNULL(pid.TotalAmount,0)) AS Amount,
            // SUM(IFNULL(pid.TotalAmount,0)) AS Amount1,
            // SUM(IFNULL(pid.DiscountAmount,0) + IFNULL(pid.DiscountPercentage,0)) AS DetailDiscount
            
            // FROM trdpurchaseinvoice pi 
            // LEFT OUTER JOIN trdpurchaseinvoicedetail  pid ON pi.Oid = pid.PurchaseInvoice
            // LEFT OUTER JOIN mstbusinesspartner bp ON pi.BusinessPartner = bp.Oid
            // LEFT OUTER JOIN mstbusinesspartnergroup bpg ON bp.BusinessPartnerGroup = bpg.Oid
            // LEFT OUTER JOIN mstcity ct ON bp.City = ct.Oid
            // LEFT OUTER JOIN mstitem i ON pid.Item = i.Oid
            // LEFT OUTER JOIN mstitemgroup ig ON i.ItemGroup = ig.Oid
            // LEFT OUTER JOIN mstemployee e ON pi.Employee = e.Oid
            // LEFT OUTER JOIN mstcurrency c ON pi.Currency = c.Oid
            // LEFT OUTER JOIN sysstatus s ON pi.Status = s.Oid
            // LEFT OUTER JOIN accaccount a ON pi.Account = a.Oid
            // LEFT OUTER JOIN accaccountgroup a1 ON a.AccountGroup = a1.Oid
            // WHERE pi.GCRecord IS NULL  AND 1=1 AND 4=4
            // GROUP BY a1.Name, a.Name, c.Code, DATE_FORMAT(pi.Date, '%Y%m%d')";
            //     break;
            // case 14;
            //     return "SELECT
            // pi.Code AS Code,
            // DATE_FORMAT(pi.Date, '%b %e %y') AS Date,
            // a.Name AS Accaunt,
            // c.Code AS CurrencyCode,
            // pid.Price AS Amount,
            // pid.Price AS Amount1

            
            // FROM trdpurchaseinvoice pi
            // LEFT OUTER JOIN trdpurchaseinvoicedetail pid ON pi.Oid = pid.PurchaseInvoice
            // LEFT OUTER JOIN mstemployee e ON pi.Employee = e.Oid
            // LEFT OUTER JOIN mstcurrency c ON pi.Currency = c.Oid
            // LEFT OUTER JOIN sysstatus s ON pi.Status = s.Oid
            // LEFT OUTER JOIN accaccount a ON pi.Account = a.Oid
            // WHERE pi.GCRecord  IS NULL AND 1=1 AND 3=3 AND 4=4 AND 6=6
            // ORDER BY pi.Code,DATE_FORMAT(pi.Date, '%Y%m%d'), Accaunt";
            //     break;
        }
        return "";
    }
}
