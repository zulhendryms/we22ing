<?php

namespace App\AdminApi\POS\Controllers;

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
use App\Core\Internal\Entities\PointOfSaleType;
use App\Core\Master\Entities\Item;
use App\Core\Master\Entities\ItemGroupUser;
use App\Core\Master\Entities\Company;
use App\Core\Security\Entities\User;
use Carbon\Carbon;

class ReportSalesPosController extends Controller
{
    protected $reportService;
    protected $reportName;
    public function __construct(ReportService $reportService)
    {
        $this->reportName = 'salespos';
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
        $criteria8POSSessionDate = "";

        $datefrom = Carbon::parse($request->input('datefrom'));
        $dateto = Carbon::parse($request->input('dateto'));
        $warehouse = Warehouse::where('IsActive', 1)->orderBy('Sequence')->whereRaw('Sequence > 0')->get();

        $firstDayOfMonth = date("Y-m-01", strtotime($datefrom));
        $lastDayOfMonth = date("Y-m-t", strtotime($datefrom));

        $criteria1 = $criteria1 . " AND DATE_FORMAT(pos.Date, '%Y-%m-%d') >= '" . $datefrom->format('Y-m-d') . "'";
        $filter = $filter . "Date From = '" . strtoupper($datefrom->format('d-m-Y')) . "'; ";
        $criteria1 = $criteria1 . " AND DATE_FORMAT(pos.Date, '%Y-%m-%d') <= '" . $dateto->format('Y-m-d') . "'";
        $filter = $filter . "Date To = '" . strtoupper($dateto->format('d-m-Y')) . "'; ";

        // $filter = $filter."Date From = '".strtoupper($datefrom->format('d-m-Y'))."'; "; 
        // $filter = $filter."Date To = '".strtoupper($dateto->format('d-m-Y'))."'; "; 

        $criteria5 = $criteria5 . " AND DATE_FORMAT(p.Date, '%Y-%m-%d') >= '" . $datefrom->format('Y-m-d') . "'";
        $criteria5 = $criteria5 . " AND DATE_FORMAT(p.Date, '%Y-%m-%d') <= '" . $dateto->format('Y-m-d') . "'";

        $criteria8POSSessionDate = $criteria8POSSessionDate . " AND DATE_FORMAT(psa.Date, '%Y-%m-%d') >= '" . $datefrom->format('Y-m-d') . "'";
        $criteria8POSSessionDate = $criteria8POSSessionDate . " AND DATE_FORMAT(psa.Date, '%Y-%m-%d') <= '" . $dateto->format('Y-m-d') . "'";

        $criteria4 = $criteria4 . reportQueryCompany('pospointofsale');
        if ($request->input('company')) {
            $val = Company::findOrFail($request->input('company'));
            $criteria4 = $criteria4 . " AND co.Oid = '" . $val->Oid . "'";
            $filter = $filter . " AND Company = '" . strtoupper($val->Name) . "'";
        }
        if ($request->input('p_BusinessPartner')) {
            $val = BusinessPartner::findOrFail($request->input('p_BusinessPartner'));
            $criteria4 = $criteria4 . " AND bp.Oid = '" . $val->Oid . "'";
            $filter = $filter . "businesspartner = '" . strtoupper($val->Name) . "'; ";
        }
        if ($request->input('p_Businesspartnergroup')) {
            $val = BusinessPartnerGroup::findOrFail($request->input('p_Businesspartnergroup'));
            $criteria4 = $criteria4 . " AND bpg.Oid = '" . $val->Oid . "'";
            $filter = $filter . " AND B.PartnerGroup = '" . strtoupper($val->Name) . "'";
        }
        if ($request->input('p_PaymentMethod')) {
            $val = PaymentMethod::findOrFail($request->input('p_PaymentMethod'));
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
        if ($request->input('p_Postable')) {
            $val = POSTable::findOrFail($request->input('p_Postable'));
            $criteria4 = $criteria4 . " AND pt.Oid = '" . $val->Oid . "'";
            $filter = $filter . " AND PosTable = '" . strtoupper($val->Name) . "'";
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
        $itemGroupUser = ItemGroupUser::select('ItemGroup')->where('User', $user->Oid)->get();
        if ($itemGroupUser->count() > 0) {
            $itemGroupUser = pluckComma($itemGroupUser, 'ItemGroup');
            // dd($itemGroupUser);
            $criteria6 = $criteria6 . " AND i.ItemGroup IN (" . $itemGroupUser . ")";
        }
        // if ($request->input('itemgroup')) {
        //   $val = ItemGroup::findOrFail($request->input('itemgroup'));
        //   $criteria3 = $criteria3." AND ig.Oid = '".$val->Oid."'";
        //   $filter = $filter." AND ItemGroup = '".strtoupper($val->Name)."'";
        // }

        $criteria2 = $criteria2 . " AND pos.Date >= '" . $firstDayOfMonth . "'";
        $criteria2 = $criteria2 . " AND pos.Date <= '" . $lastDayOfMonth . "'";

        if ($request->input('p_User')) {
            $val = User::findOrFail($request->input('p_User'));
            $criteria5 = $criteria5 . " AND p.User = '" . $val->Oid . "'";
            $criteria4 = $criteria4 . " AND u.Oid = '" . $val->Oid . "'";
            $filter = $filter . "Account = '" . strtoupper($val->Name) . "'; ";
        }

        if ($filter) $filter = substr($filter, 0);
        if ($criteria1) $query = str_replace(" AND 1=1", $criteria1, $query);
        if ($criteria2) $query = str_replace(" AND 2=2", $criteria2, $query);
        if ($criteria3) $query = str_replace(" AND 3=3", $criteria3, $query);
        if ($criteria4) $query = str_replace(" AND 4=4", $criteria4, $query);
        if ($criteria5) $query = str_replace(" AND 5=5", $criteria5, $query);
        if ($criteria6) $query = str_replace(" AND 6=6", $criteria6, $query);
        if ($criteria8POSSessionDate) $query = str_replace(" AND 8=8", $criteria8POSSessionDate, $query);

        $data = DB::select($query);
        switch ($reportname) {
            case 1:  //2.1
                $reporttitle = "Sales POS Daily By Cashier";
                $pdf = SnappyPdf::loadView('AdminApi\POS::pdf.salespos_02', compact('data', 'user', 'reportname', 'filter', 'reporttitle'))->setPaper('A4', 'landscape');
                break;
            case 2: //2.2
                $reporttitle = 'Sales POS Daily By Sales';
                $pdf = SnappyPdf::loadView('AdminApi\POS::pdf.salespos_01', compact('data', 'user', 'reportname', 'filter', 'reporttitle'));
                break;
            case 3: //2.3
                $reporttitle = 'Sales POS Daily By Payment Method';
                $pdf = SnappyPdf::loadView('AdminApi\POS::pdf.salespos_01', compact('data', 'user', 'reportname', 'filter', 'reporttitle'));
                break;
            case 4: //2.4
                $reporttitle = 'Sales POS Daily By Warehouse';
                $pdf = SnappyPdf::loadView('AdminApi\POS::pdf.salespos_01', compact('data', 'user', 'reportname', 'filter', 'reporttitle'));
                break;
            case 5: //2.5
                $reporttitle = 'Sales POS Daily By Table';
                $pdf = SnappyPdf::loadView('AdminApi\POS::pdf.salespos_01', compact('data', 'user', 'reportname', 'filter', 'reporttitle'));
                break;
            case 6: //2.6
                $reporttitle = 'Sales POS Daily By Customer';
                $pdf = SnappyPdf::loadView('AdminApi\POS::pdf.salespos_01', compact('data', 'user', 'reportname', 'filter', 'reporttitle'));
                break;
            case 7: //2.7
                $reporttitle = 'Sales POS Daily By item';
                $pdf = SnappyPdf::loadView('AdminApi\POS::pdf.salespos_07', compact('data', 'user', 'reportname', 'filter', 'reporttitle'));
                break;
            case 8: //2.8
                $reporttitle = 'Sales POS Daily By Item Group';
                $pdf = SnappyPdf::loadView('AdminApi\POS::pdf.salespos_01', compact('data', 'user', 'reportname', 'filter', 'reporttitle'));
                break;
            case 9: //2.9
                $reporttitle = 'Sales POS Daily By City';
                $pdf = SnappyPdf::loadView('AdminApi\POS::pdf.salespos_01', compact('data', 'user', 'reportname', 'filter', 'reporttitle'));
                break;
            case 10: //1.1
                $reporttitle = 'Detail List Item By Warehouse'; //Warehouse1
                $pdf = SnappyPdf::loadView('AdminApi\POS::pdf.salespos_' . $reportname, compact('data', 'user', 'reportname', 'filter', 'reporttitle'))->setPaper('A4', 'landscape');
                break;
            case 10.1: //1.2
                $reporttitle = 'Detail List Item By Warehouse'; //Warehouse2
                $pdf = SnappyPdf::loadView('AdminApi\POS::pdf.salespos_09', compact('data', 'user', 'reportname', 'filter', 'reporttitle'))->setPaper('A4', 'landscape');
                break;
            case 11:  //1.3
                $reporttitle = 'Sales POS Detail List Group By Cashier';
                $pdf = SnappyPdf::loadView('AdminApi\POS::pdf.salespos_' . $reportname, compact('data', 'user', 'reportname', 'filter', 'reporttitle'));
                break;
            case 12: //1.4
                $reporttitle = 'Sales POS Detail List Group By Table';
                $pdf = SnappyPdf::loadView('AdminApi\POS::pdf.salespos_' . $reportname, compact('data', 'user', 'reportname', 'filter', 'reporttitle'));
                break;
            case 13: //1.5
                $reporttitle = 'Sales POS Detail List Group By Sales';
                $pdf = SnappyPdf::loadView('AdminApi\POS::pdf.salespos_' . $reportname, compact('data', 'user', 'reportname', 'filter', 'reporttitle'));
                break;
            case 14: //2.3
                $reporttitle = 'Sales POS Crosstab Amount Daily By Payment Method';
                $pdf = SnappyPdf::loadView('AdminApi\POS::pdf.salespos_211', compact('data', 'user', 'reportname', 'filter', 'reporttitle'))->setPaper('A4', 'landscape');
                break;
            case 15: //3.1
                $reporttitle = "Report POS Session Detail";
                $pdf = SnappyPdf::loadView('AdminApi\POS::pdf.salespos_' . $reportname, compact('data', 'user', 'reportname', 'filter', 'reporttitle'));
                break;
            case 16: //3.2
                $reporttitle = "Report POS Session Summary";
                $pdf = SnappyPdf::loadView('AdminApi\POS::pdf.salespos_' . $reportname, compact('data', 'user', 'reportname', 'filter', 'reporttitle'));
                break;
            case 17: //3.3
                $reporttitle = "Report Daily By Date";
                $pdf = SnappyPdf::loadView('AdminApi\POS::pdf.salespos_' . $reportname, compact('data', 'user', 'reportname', 'filter', 'reporttitle'));
                break;
            case 18: //3.4
                $reporttitle = "Sales POS By Item Daily (Warehouse)";
                $pdf = SnappyPdf::loadView('AdminApi\POS::pdf.salespos_' . $reportname, compact('data', 'user', 'reportname', 'filter', 'reporttitle', 'warehouse'));
                break;
            case 19: //3.5
                $reporttitle = "Sales POS By Daily Item (Warehouse)";
                $pdf = SnappyPdf::loadView('AdminApi\POS::pdf.salespos_' . $reportname, compact('data', 'user', 'reportname', 'filter', 'reporttitle', 'warehouse'));
                break;
            case 20: //1.6
                $reporttitle = "Detail List Group By Item";
                $pdf = SnappyPdf::loadView('AdminApi\POS::pdf.salespos_' . $reportname, compact('data', 'user', 'reportname', 'filter', 'reporttitle'));
                break;
            case 21: //3.6
                $reporttitle = "Sales POS Daily By Project";
                $pdf = SnappyPdf::loadView('AdminApi\POS::pdf.salespos_01', compact('data', 'user', 'reportname', 'filter', 'reporttitle'));
                break;
            case 22: //3.7
                $reporttitle = "Sales POS Daily By Employee2";
                $pdf = SnappyPdf::loadView('AdminApi\POS::pdf.salespos_01', compact('data', 'user', 'reportname', 'filter', 'reporttitle'));
                break;
            case 23:
                $reporttitle = "Sales Qty Crosstab by Item";
                $pdf = SnappyPdf::loadView('AdminApi\POS::pdf.salespos_211', compact('data', 'user', 'reportname', 'filter', 'reporttitle'))->setPaper('A4', 'landscape');
                break;
            case 24: //10.2
                $reporttitle = "Sales Qty Crosstab by Item Group";
                $pdf = SnappyPdf::loadView('AdminApi\POS::pdf.salespos_211', compact('data', 'user', 'reportname', 'filter', 'reporttitle'))->setPaper('A4', 'landscape');
                break;
            case 25:
                $reporttitle = "Sales Report Group By Item Per Day"; //5.1
                $pdf = SnappyPdf::loadView('AdminApi\POS::pdf.salespos_25', compact('data', 'user', 'reportname', 'filter', 'reporttitle'));
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
        if ($request->input('action') == 'download') return response()->download($reportFile->getFilePath())->deleteFileAfterSend(true);

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
        $return = PointOfSaleType::where('Code', 'SRETURN')->first()->Oid;

        switch ($reportname) {
            case 1:
                return "SELECT 
                    pos.User, co.Code AS Comp,
                    u.Name AS GroupName,
                    c.Code AS CurrencyCode,
                    DATE_FORMAT(pos.Date, '%e %b %Y') AS Date,
                    DATE_FORMAT(pos.Date, '%Y%m%d') AS DateOrder,
                    COUNT(pos.Oid) AS Qty,
                    SUM(IFNULL(posd.DetailSubtotal,0)) AS DetailSubtotal,
                    SUM(IFNULL(posd.DetailDiscount,0)) AS DetailDiscount,
                    SUM(IFNULL(posd.DetailTotal,0)) AS SubtotalAmount,
                    SUM(IFNULL(pos.SubtotalAmount,0) * CASE WHEN pos.PointOfSaleType='{$return}' THEN -1 ELSE 1 END) AS SubtotalAmount1,
                    (SUM(IFNULL(pos.DiscountAmount,0) + IFNULL(pos.DiscountPercentageAmount,0)) * CASE WHEN pos.PointOfSaleType='{$return}' THEN -1 ELSE 1 END) AS DiscountAmount,
                    SUM(IFNULL(pos.TotalAmount,0) * CASE WHEN pos.PointOfSaleType='{$return}' THEN -1 ELSE 1 END) AS TotalAmount,
                    IFNULL(psa.TotalSessionAmount,0) AS TotalSessionAmount
                    FROM pospointofsale pos 
                    LEFT OUTER JOIN 
                        (SELECT pos.Oid,
                        SUM(IFNULL(posd.Quantity,0) * IFNULL(posd.Amount,0)) AS DetailSubtotal,
                        SUM(IFNULL(posd.DiscountAmount,0) + IFNULL(posd.DiscountPercentageAmount,0)) AS DetailDiscount,
                        SUM((IFNULL(posd.Quantity,0) * IFNULL(posd.Amount,0)) - IFNULL(posd.DiscountAmount,0) - IFNULL(posd.DiscountPercentageAmount,0)) AS DetailTotal
                        FROM pospointofsale pos
                        LEFT OUTER JOIN sysstatus s ON pos.Status = s.Oid
                        LEFT OUTER JOIN pospointofsaledetail posd ON posd.PointOfSale = pos.Oid
                        WHERE s.Code IN ('paid', 'complete') AND 1=1 AND 3=3 GROUP BY pos.Oid) AS posd ON posd.Oid = pos.Oid  
                    LEFT OUTER JOIN (
                        SELECT  DATE_FORMAT(ps.Date, '%e %b %Y') AS Date, ps.User, 
                        SUM(IFNULL(CASE WHEN psa.Type = 2 THEN IFNULL(psa.AmountBase,0) * -1 ELSE IFNULL(psa.AmountBase,0) END,0)) AS TotalSessionAmount
                        FROM possessionamount psa
                        LEFT OUTER JOIN possession ps ON ps.Oid = psa.POSSession
                        WHERE 8=8
                        GROUP BY DATE_FORMAT(ps.Date, '%e %b %Y'), ps.User
                    ) psa ON psa.Date = DATE_FORMAT(pos.Date, '%e %b %Y')  AND psa.User = pos.User
                    LEFT OUTER JOIN mstbusinesspartner bp ON pos.Customer = bp.Oid
                    LEFT OUTER JOIN mstbusinesspartnergroup bpg ON bp.BusinessPartnerGroup = bpg.Oid
                    LEFT OUTER JOIN mstcity ct ON bp.City = ct.Oid
                    
                    LEFT OUTER JOIN mstpaymentmethod pm ON pos.PaymentMethod = pm.Oid
                    LEFT OUTER JOIN mstwarehouse w ON pos.Warehouse = w.Oid
                    LEFT OUTER JOIN mstemployee e ON pos.Employee = e.Oid
                    LEFT OUTER JOIN postable pt ON pos.POSTable = pt.Oid 
                    LEFT OUTER JOIN user u ON pos.User = u.Oid
                    LEFT OUTER JOIN mstcurrency c ON pos.Currency = c.Oid
                    LEFT OUTER JOIN sysstatus s ON pos.Status = s.Oid
                    LEFT OUTER JOIN company co ON co.Oid = pos.Company
                    WHERE pos.GCRecord IS NULL AND s.Code IN ('paid', 'complete','posted') AND 1=1 AND 4=4 
                    GROUP BY u.Name, c.Code, DATE_FORMAT(pos.Date, '%Y%m%d'), pos.User, psa.TotalSessionAmount,pos.PointOfSaleType";
            case 2:
                return "SELECT * FROM (SELECT
                co.Code AS Comp,
                CONCAT(e.Name, ' - ', e.Code) AS GroupName,
                c.Code AS CurrencyCode,
                pos.Date AS Date,
                pos.Date AS DateOrder,
                COUNT(pos.Oid) AS Qty,
                SUM(IFNULL(posd.DetailSubtotal,0)) AS DetailSubtotal,
                SUM(IFNULL(posd.DetailDiscount,0)) AS DetailDiscount,
                SUM(IFNULL(posd.DetailTotal,0)) AS SubtotalAmount,
                SUM(IFNULL(pos.SubtotalAmount,0) * CASE WHEN pos.PointOfSaleType='{$return}' THEN -1 ELSE 1 END) AS SubtotalAmount1,
                (SUM(IFNULL(pos.DiscountAmount,0) + IFNULL(pos.DiscountPercentageAmount,0)) * CASE WHEN pos.PointOfSaleType='{$return}' THEN -1 ELSE 1 END) AS DiscountAmount,
                SUM(IFNULL(pos.TotalAmount,0) * CASE WHEN pos.PointOfSaleType='{$return}' THEN -1 ELSE 1 END) AS TotalAmount
                FROM pospointofsale pos
                LEFT OUTER JOIN company co ON co.Oid = pos.Company
                LEFT OUTER JOIN 
                (SELECT pos.Oid,
                SUM(IFNULL(posd.Quantity,0) * IFNULL(posd.Amount,0)) AS DetailSubtotal,
                SUM(IFNULL(posd.DiscountAmount,0) + IFNULL(posd.DiscountPercentageAmount,0)) AS DetailDiscount,
                SUM((IFNULL(posd.Quantity,0) * IFNULL(posd.Amount,0)) - IFNULL(posd.DiscountAmount,0) - IFNULL(posd.DiscountPercentageAmount,0)) AS DetailTotal
                FROM pospointofsale pos
                LEFT OUTER JOIN sysstatus s ON pos.Status = s.Oid
                LEFT OUTER JOIN pospointofsaledetail posd ON posd.PointOfSale = pos.Oid
                WHERE s.Code IN ('paid', 'complete') AND 1=1 AND 3=3 GROUP BY pos.Oid) AS posd ON posd.Oid = pos.Oid
                LEFT OUTER JOIN mstbusinesspartner bp ON pos.Customer = bp.Oid
                LEFT OUTER JOIN mstbusinesspartnergroup bpg ON bp.BusinessPartnerGroup = bpg.Oid
                LEFT OUTER JOIN mstcity ct ON bp.City = ct.Oid
                LEFT OUTER JOIN mstpaymentmethod pm ON pos.PaymentMethod = pm.Oid
                LEFT OUTER JOIN mstwarehouse w ON pos.Warehouse = w.Oid
                LEFT OUTER JOIN mstemployee e ON pos.Employee = e.Oid
                LEFT OUTER JOIN postable pt ON pos.POSTable = pt.Oid 
                LEFT OUTER JOIN mstcurrency c ON pos.Currency = c.Oid
                LEFT OUTER JOIN sysstatus s ON pos.Status = s.Oid
                LEFT OUTER JOIN user u ON pos.User = u.Oid
                WHERE pos.GCRecord IS NULL AND s.Code IN ('paid', 'complete','posted') AND 1=1 AND 4=4
                  GROUP BY e.Name, e.Code, c.Code, `Date`
                UNION ALL
                SELECT '','', cur.Code, psa.Date, psa.Date, 0,0,0,0,psa.AmountBase,0, psa.AmountBase
                FROM possessionamount psa
                LEFT OUTER JOIN mstpaymentmethod pm ON pm.Oid = psa.PaymentMethod
                LEFT OUTER JOIN possession ps ON ps.Oid = psa.POSSession 
                LEFT OUTER JOIN user u ON u.Oid = ps.User
                LEFT OUTER JOIN mstwarehouse w ON w.Oid = ps.Warehouse
                LEFT OUTER JOIN mstcurrency cur ON cur.Oid = psa.Currency
                WHERE psa.GCRecord IS NULL AND 8=8
                ) AS Result
                ";
            case 3:
                return "SELECT * FROM (SELECT
                co.Code AS Comp,
                CONCAT(pm.Name, ' - ', pm.Code) AS GroupName,
                c.Code AS CurrencyCode,
                DATE_FORMAT(pos.Date, '%e %b %Y') AS Date,
                DATE_FORMAT(pos.Date, '%Y%m%d') AS DateOrder,
                COUNT(pos.Oid) AS Qty,
                SUM(IFNULL(posd.DetailSubtotal,0)) AS DetailSubtotal,
                SUM(IFNULL(posd.DetailDiscount,0)) AS DetailDiscount,
                SUM(IFNULL(posd.DetailTotal,0)) AS SubtotalAmount,
                SUM(IFNULL(pos.SubtotalAmount,0) * CASE WHEN pos.PointOfSaleType='{$return}' THEN -1 ELSE 1 END) AS SubtotalAmount1,
                (SUM(IFNULL(pos.DiscountAmount,0) + IFNULL(pos.DiscountPercentageAmount,0)) * CASE WHEN pos.PointOfSaleType='{$return}' THEN -1 ELSE 1 END) AS DiscountAmount,
                SUM(IFNULL(pos.TotalAmount,0) * CASE WHEN pos.PointOfSaleType='{$return}' THEN -1 ELSE 1 END) AS TotalAmount
                FROM pospointofsale pos 
                LEFT OUTER JOIN company co ON co.Oid = pos.Company
                LEFT OUTER JOIN 
                (SELECT pos.Oid,
                SUM(IFNULL(posd.Quantity,0) * IFNULL(posd.Amount,0)) AS DetailSubtotal,
                SUM(IFNULL(posd.DiscountAmount,0) + IFNULL(posd.DiscountPercentageAmount,0)) AS DetailDiscount,
                SUM((IFNULL(posd.Quantity,0) * IFNULL(posd.Amount,0)) - IFNULL(posd.DiscountAmount,0) - IFNULL(posd.DiscountPercentageAmount,0)) AS DetailTotal
                FROM pospointofsale pos
                LEFT OUTER JOIN sysstatus s ON pos.Status = s.Oid
                LEFT OUTER JOIN pospointofsaledetail posd ON posd.PointOfSale = pos.Oid
                WHERE s.Code IN ('paid', 'complete','posted') AND 1=1 AND 3=3 GROUP BY pos.Oid) AS posd ON posd.Oid = pos.Oid
                LEFT OUTER JOIN mstbusinesspartner bp ON pos.Customer = bp.Oid
                LEFT OUTER JOIN mstbusinesspartnergroup bpg ON bp.BusinessPartnerGroup = bpg.Oid
                LEFT OUTER JOIN mstcity ct ON bp.City = ct.Oid
                LEFT OUTER JOIN mstpaymentmethod pm ON pos.PaymentMethod = pm.Oid
                LEFT OUTER JOIN mstwarehouse w ON pos.Warehouse = w.Oid
                LEFT OUTER JOIN mstemployee e ON pos.Employee = e.Oid
                LEFT OUTER JOIN postable pt ON pos.POSTable = pt.Oid 
                LEFT OUTER JOIN mstcurrency c ON pos.Currency = c.Oid
                LEFT OUTER JOIN sysstatus s ON pos.Status = s.Oid
                LEFT OUTER JOIN user u ON pos.User = u.Oid
                WHERE pos.GCRecord IS NULL AND s.Code IN ('paid', 'complete','posted') AND 1=1 AND 4=4
                GROUP BY pm.Name, pm.Code, c.Code, DATE_FORMAT(pos.Date, '%Y%m%d')
                UNION ALL
                SELECT '',pm.Name, cur.Code, psa.Date, psa.Date,0,0,0,0,psa.AmountBase,0, psa.AmountBase
                FROM possessionamount psa
                LEFT OUTER JOIN mstpaymentmethod pm ON pm.Oid = psa.PaymentMethod
                LEFT OUTER JOIN possession ps ON ps.Oid = psa.POSSession 
                LEFT OUTER JOIN user u ON u.Oid = ps.User
                LEFT OUTER JOIN mstwarehouse w ON w.Oid = ps.Warehouse
                LEFT OUTER JOIN mstcurrency cur ON cur.Oid = psa.Currency
                WHERE psa.GCRecord IS NULL AND 8=8
                ) AS Result";
            case 4:
                return "SELECT * FROM (SELECT
                co.Code AS Comp,
                CONCAT(w.Name, ' - ', w.Code) AS GroupName,
                c.Code AS CurrencyCode,
                DATE_FORMAT(pos.Date, '%e %b %Y') AS Date,
                DATE_FORMAT(pos.Date, '%Y%m%d') AS DateOrder,
                COUNT(pos.Oid) AS Qty,
                SUM(IFNULL(posd.DetailSubtotal,0)) AS DetailSubtotal,
                SUM(IFNULL(posd.DetailDiscount,0)) AS DetailDiscount,
                SUM(IFNULL(posd.DetailTotal,0)) AS SubtotalAmount,
                SUM(IFNULL(pos.SubtotalAmount,0) * CASE WHEN pos.PointOfSaleType='{$return}' THEN -1 ELSE 1 END) AS SubtotalAmount1,
                (SUM(IFNULL(pos.DiscountAmount,0) + IFNULL(pos.DiscountPercentageAmount,0)) * CASE WHEN pos.PointOfSaleType='{$return}' THEN -1 ELSE 1 END) AS DiscountAmount,
                SUM(IFNULL(pos.TotalAmount,0) * CASE WHEN pos.PointOfSaleType='{$return}' THEN -1 ELSE 1 END) AS TotalAmount
                FROM pospointofsale pos 
                LEFT OUTER JOIN company co ON co.Oid = pos.Company
                LEFT OUTER JOIN 
                (SELECT pos.Oid,
                SUM(IFNULL(posd.Quantity,0) * IFNULL(posd.Amount,0)) AS DetailSubtotal,
                SUM(IFNULL(posd.DiscountAmount,0) + IFNULL(posd.DiscountPercentageAmount,0)) AS DetailDiscount,
                SUM((IFNULL(posd.Quantity,0) * IFNULL(posd.Amount,0)) - IFNULL(posd.DiscountAmount,0) - IFNULL(posd.DiscountPercentageAmount,0)) AS DetailTotal
                FROM pospointofsale pos
                LEFT OUTER JOIN sysstatus s ON pos.Status = s.Oid
                LEFT OUTER JOIN pospointofsaledetail posd ON posd.PointOfSale = pos.Oid
                WHERE s.Code IN ('paid', 'complete','posted') AND 1=1 AND 3=3 GROUP BY pos.Oid) AS posd ON posd.Oid = pos.Oid
                LEFT OUTER JOIN mstbusinesspartner bp ON pos.Customer = bp.Oid
                LEFT OUTER JOIN mstbusinesspartnergroup bpg ON bp.BusinessPartnerGroup = bpg.Oid
                LEFT OUTER JOIN mstcity ct ON bp.City = ct.Oid
                LEFT OUTER JOIN mstpaymentmethod pm ON pos.PaymentMethod = pm.Oid
                LEFT OUTER JOIN mstwarehouse w ON pos.Warehouse = w.Oid
                LEFT OUTER JOIN mstemployee e ON pos.Employee = e.Oid
                LEFT OUTER JOIN postable pt ON pos.POSTable = pt.Oid 
                LEFT OUTER JOIN mstcurrency c ON pos.Currency = c.Oid
                LEFT OUTER JOIN user u ON pos.User = u.Oid
                LEFT OUTER JOIN sysstatus s ON pos.Status = s.Oid
                WHERE pos.GCRecord IS NULL AND s.Code IN ('paid', 'complete','posted') AND 1=1 AND 4=4
                GROUP BY w.Name, w.Code, c.Code, `Date`
                UNION ALL
                SELECT '',w.Name, cur.Code, psa.Date, psa.Date,0,0,0,0,psa.AmountBase,0, psa.AmountBase
                FROM possessionamount psa
                LEFT OUTER JOIN mstpaymentmethod pm ON pm.Oid = psa.PaymentMethod
                LEFT OUTER JOIN possession ps ON ps.Oid = psa.POSSession 
                LEFT OUTER JOIN user u ON u.Oid = ps.User
                LEFT OUTER JOIN mstwarehouse w ON w.Oid = ps.Warehouse
                LEFT OUTER JOIN mstcurrency cur ON cur.Oid = psa.Currency
                WHERE psa.GCRecord IS NULL AND 8=8
                ) AS Result";
            case 5:
                return "SELECT * FROM (SELECT
                co.Code AS Comp,
                pt.Name AS GroupName,
                c.Code AS CurrencyCode,
                DATE_FORMAT(pos.Date, '%e %b %Y') AS Date,
                DATE_FORMAT(pos.Date, '%Y%m%d') AS DateOrder,
                COUNT(pos.Oid) AS Qty,
                SUM(IFNULL(posd.DetailSubtotal,0)) AS DetailSubtotal,
                SUM(IFNULL(posd.DetailDiscount,0)) AS DetailDiscount,
                SUM(IFNULL(posd.DetailTotal,0)) AS SubtotalAmount,
                SUM(IFNULL(pos.SubtotalAmount,0) * CASE WHEN pos.PointOfSaleType='{$return}' THEN -1 ELSE 1 END) AS SubtotalAmount1,
                (SUM(IFNULL(pos.DiscountAmount,0) + IFNULL(pos.DiscountPercentageAmount,0)) * CASE WHEN pos.PointOfSaleType='{$return}' THEN -1 ELSE 1 END) AS DiscountAmount,
                SUM(IFNULL(pos.TotalAmount,0) * CASE WHEN pos.PointOfSaleType='{$return}' THEN -1 ELSE 1 END) AS TotalAmount
                FROM pospointofsale pos 
                LEFT OUTER JOIN company co ON co.Oid = pos.Company
                LEFT OUTER JOIN 
                (SELECT pos.Oid,
                SUM(IFNULL(posd.Quantity,0) * IFNULL(posd.Amount,0)) AS DetailSubtotal,
                SUM(IFNULL(posd.DiscountAmount,0) + IFNULL(posd.DiscountPercentageAmount,0)) AS DetailDiscount,
                SUM((IFNULL(posd.Quantity,0) * IFNULL(posd.Amount,0)) - IFNULL(posd.DiscountAmount,0) - IFNULL(posd.DiscountPercentageAmount,0)) AS DetailTotal
                FROM pospointofsale pos
                LEFT OUTER JOIN sysstatus s ON pos.Status = s.Oid
                LEFT OUTER JOIN pospointofsaledetail posd ON posd.PointOfSale = pos.Oid
                WHERE s.Code IN ('paid', 'complete','posted') AND 1=1 AND 3=3 GROUP BY pos.Oid) AS posd ON posd.Oid = pos.Oid
                LEFT OUTER JOIN mstbusinesspartner bp ON pos.Customer = bp.Oid
                LEFT OUTER JOIN mstbusinesspartnergroup bpg ON bp.BusinessPartnerGroup = bpg.Oid
                LEFT OUTER JOIN mstcity ct ON bp.City = ct.Oid
                LEFT OUTER JOIN mstpaymentmethod pm ON pos.PaymentMethod = pm.Oid
                LEFT OUTER JOIN mstwarehouse w ON pos.Warehouse = w.Oid
                LEFT OUTER JOIN mstemployee e ON pos.Employee = e.Oid
                LEFT OUTER JOIN postable pt ON pos.POSTable = pt.Oid
                LEFT OUTER JOIN mstcurrency c ON pos.Currency = c.Oid
                LEFT OUTER JOIN sysstatus s ON pos.Status = s.Oid
                LEFT OUTER JOIN user u ON pos.User = u.Oid
                WHERE pos.GCRecord IS NULL AND s.Code IN ('paid', 'complete','posted') AND 1=1 AND 4=4
                GROUP BY pt.Name, c.Code, `Date`
                UNION ALL
                SELECT '','', cur.Code, psa.Date, psa.Date,0,0,0,0,psa.AmountBase,0, psa.AmountBase
                FROM possessionamount psa
                LEFT OUTER JOIN mstpaymentmethod pm ON pm.Oid = psa.PaymentMethod
                LEFT OUTER JOIN possession ps ON ps.Oid = psa.POSSession 
                LEFT OUTER JOIN user u ON u.Oid = ps.User
                LEFT OUTER JOIN mstwarehouse w ON w.Oid = ps.Warehouse
                LEFT OUTER JOIN mstcurrency cur ON cur.Oid = psa.Currency
                WHERE psa.GCRecord IS NULL AND 8=8
                ) AS Result";
            case 6:
                return "SELECT * FROM (SELECT
                co.Code AS Comp,
                CONCAT(bp.Name, ' - ', bp.Code) AS GroupName,
                c.Code AS CurrencyCode,
                DATE_FORMAT(pos.Date, '%e %b %Y') AS Date,
                DATE_FORMAT(pos.Date, '%Y%m%d') AS DateOrder,
                COUNT(pos.Oid) AS Qty,
                SUM(IFNULL(posd.DetailSubtotal,0)) AS DetailSubtotal,
                SUM(IFNULL(posd.DetailDiscount,0)) AS DetailDiscount,
                SUM(IFNULL(posd.DetailTotal,0)) AS SubtotalAmount,
                SUM(IFNULL(pos.SubtotalAmount,0) * CASE WHEN pos.PointOfSaleType='{$return}' THEN -1 ELSE 1 END) AS SubtotalAmount1,
                (SUM(IFNULL(pos.DiscountAmount,0) + IFNULL(pos.DiscountPercentageAmount,0)) * CASE WHEN pos.PointOfSaleType='{$return}' THEN -1 ELSE 1 END) AS DiscountAmount,
                SUM(IFNULL(pos.TotalAmount,0) * CASE WHEN pos.PointOfSaleType='{$return}' THEN -1 ELSE 1 END) AS TotalAmount
                FROM pospointofsale pos 
                LEFT OUTER JOIN company co ON co.Oid = pos.Company
                LEFT OUTER JOIN 
                (SELECT pos.Oid,
                SUM(IFNULL(posd.Quantity,0) * IFNULL(posd.Amount,0)) AS DetailSubtotal,
                SUM(IFNULL(posd.DiscountAmount,0) + IFNULL(posd.DiscountPercentageAmount,0)) AS DetailDiscount,
                SUM((IFNULL(posd.Quantity,0) * IFNULL(posd.Amount,0)) - IFNULL(posd.DiscountAmount,0) - IFNULL(posd.DiscountPercentageAmount,0)) AS DetailTotal
                FROM pospointofsale pos
                LEFT OUTER JOIN sysstatus s ON pos.Status = s.Oid
                LEFT OUTER JOIN pospointofsaledetail posd ON posd.PointOfSale = pos.Oid
                WHERE s.Code IN ('paid', 'complete','posted') AND 1=1 AND 3=3 GROUP BY pos.Oid) AS posd ON posd.Oid = pos.Oid
                LEFT OUTER JOIN mstpaymentmethod pm ON pos.PaymentMethod = pm.Oid
                LEFT OUTER JOIN mstwarehouse w ON pos.Warehouse = w.Oid
                LEFT OUTER JOIN mstemployee e ON pos.Employee = e.Oid
                LEFT OUTER JOIN postable pt ON pos.POSTable = pt.Oid 
                LEFT OUTER JOIN mstcurrency c ON pos.Currency = c.Oid
                LEFT OUTER JOIN mstbusinesspartner bp ON pos.Customer = bp.Oid
                LEFT OUTER JOIN mstbusinesspartnergroup bpg ON bp.BusinessPartnerGroup = bpg.Oid
                LEFT OUTER JOIN mstcity ct ON bp.City = ct.Oid
                LEFT OUTER JOIN sysstatus s ON pos.Status = s.Oid
                LEFT OUTER JOIN user u ON pos.User = u.Oid
                WHERE pos.GCRecord IS NULL AND s.Code IN ('paid', 'complete','posted') AND 1=1 AND 4=4
                GROUP BY bp.name, bp.Code, c.Code, DATE_FORMAT(pos.Date, '%Y%m%d')
                UNION ALL
                SELECT '','', cur.Code, psa.Date, psa.Date,0,0,0,0,psa.AmountBase,0, psa.AmountBase
                FROM possessionamount psa
                LEFT OUTER JOIN mstpaymentmethod pm ON pm.Oid = psa.PaymentMethod
                LEFT OUTER JOIN possession ps ON ps.Oid = psa.POSSession 
                LEFT OUTER JOIN user u ON u.Oid = ps.User
                LEFT OUTER JOIN mstwarehouse w ON w.Oid = ps.Warehouse
                LEFT OUTER JOIN mstcurrency cur ON cur.Oid = psa.Currency
                WHERE psa.GCRecord IS NULL AND 8=8
                ) AS Result";
            case 7:
                return "SELECT * FROM (SELECT
                co.Code AS Comp,
                CONCAT(i.Name, ' - ', i.Code) AS GroupName,
                c.Code AS CurrencyCode,
                DATE_FORMAT(pos.Date, '%e %b %Y') AS Date,
                DATE_FORMAT(pos.Date, '%Y%m%d') AS DateOrder,
                SUM(IFNULL(posd.Quantity,0)) AS Qty,
                SUM(IFNULL(posd.Quantity,0) * IFNULL(posd.Amount,0)) AS DetailSubtotal,
                SUM(IFNULL(posd.DiscountAmount,0) + IFNULL(posd.DiscountPercentageAmount,0)) AS DetailDiscount,
                (SUM((IFNULL(posd.Amount,0) * IFNULL(posd.Quantity,0)) - IFNULL(posd.DiscountAmount,0) - IFNULL(posd.DiscountPercentageAmount,0)) * CASE WHEN pos.PointOfSaleType='{$return}' THEN -1 ELSE 1 END) AS SubtotalAmount,
                0 AS DiscountAmount,
                (SUM((IFNULL(posd.Amount,0) * IFNULL(posd.Quantity,0)) - IFNULL(posd.DiscountAmount,0) - IFNULL(posd.DiscountPercentageAmount,0)) * CASE WHEN pos.PointOfSaleType='{$return}' THEN -1 ELSE 1 END) AS TotalAmount,
                psa.TotalSessionAmount, pd.TotalDiscountAmount
                FROM pospointofsale pos 
                LEFT OUTER JOIN company co ON co.Oid = pos.Company
                LEFT OUTER JOIN pospointofsaledetail posd ON pos.Oid = posd.PointOfSale
                LEFT OUTER JOIN mstbusinesspartner bp ON pos.Customer = bp.Oid
                LEFT OUTER JOIN mstbusinesspartnergroup bpg ON bp.BusinessPartnerGroup = bpg.Oid
                LEFT OUTER JOIN mstcity ct ON bp.City = ct.Oid
                LEFT OUTER JOIN mstitem i ON posd.Item = i.Oid
                LEFT OUTER JOIN mstitemgroup ig ON i.ItemGroup = ig.Oid
                LEFT OUTER JOIN mstwarehouse w ON pos.Warehouse = w.Oid
                LEFT OUTER JOIN mstpaymentmethod pm ON pos.PaymentMethod = pm.Oid
                LEFT OUTER JOIN postable pt ON pos.POSTable = pt.Oid 
                LEFT OUTER JOIN mstemployee e ON pos.Employee = e.Oid
                LEFT OUTER JOIN mstcurrency c ON pos.Currency = c.Oid
                LEFT OUTER JOIN sysstatus s ON pos.Status = s.Oid
                LEFT OUTER JOIN user u ON pos.User = u.Oid
                LEFT OUTER JOIN (
                SELECT ps.Company, SUM(psa.AmountBase) AS TotalSessionAmount
                FROM possessionamount psa
                LEFT OUTER JOIN possession ps ON ps.Oid = psa.POSSession
                WHERE 8=8
                ) psa ON psa.Company = pos.Company
                LEFT OUTER JOIN (
                SELECT pd.Company, SUM(IFNULL(pd.DiscountAmountBase,0) + IFNULL(pd.DiscountPercentageAmount,0)) AS TotalDiscountAmount
                FROM pospointofsale pd
                WHERE 1=1 AND 3=3
                ) pd ON pd.Company = pos.Company
                WHERE pos.GCRecord IS NULL AND s.Code IN ('paid', 'complete','posted') AND 1=1 AND 3=3 AND 4=4 AND 6=6
                GROUP BY i.Name, i.Code, c.Code, DATE_FORMAT(pos.Date, '%Y%m%d')
                UNION ALL
                SELECT '','', cur.Code, psa.Date, psa.Date,0,0,0,psa.AmountBase,0, psa.AmountBase,0,0
                FROM possessionamount psa
                LEFT OUTER JOIN mstpaymentmethod pm ON pm.Oid = psa.PaymentMethod
                LEFT OUTER JOIN possession ps ON ps.Oid = psa.POSSession 
                LEFT OUTER JOIN user u ON u.Oid = ps.User
                LEFT OUTER JOIN mstwarehouse w ON w.Oid = ps.Warehouse
                LEFT OUTER JOIN mstcurrency cur ON cur.Oid = psa.Currency
                WHERE psa.GCRecord IS NULL AND 8=8
                ) AS Result";
            case 8: //ITEMGROUP
                return "SELECT * FROM( SELECT
                co.Code AS Comp,
                CONCAT(ig.Name, ' - ', ig.Code) AS GroupName,
                c.Code AS CurrencyCode,
                DATE_FORMAT(pos.Date, '%e %b %Y') AS Date,
                SUM(IFNULL(posd.Quantity,0)) AS Qty,
                SUM(IFNULL(posd.Amount,0) * IFNULL(posd.Quantity,0)) AS DetailSubtotal,
                SUM(IFNULL(posd.DiscountAmount,0) + IFNULL(posd.DiscountPercentageAmount,0)) AS DetailDiscount,
                (SUM((IFNULL(posd.Amount,0) * IFNULL(posd.Quantity,0)) - IFNULL(posd.DiscountAmount,0) - IFNULL(posd.DiscountPercentageAmount,0)) * CASE WHEN pos.PointOfSaleType='{$return}' THEN -1 ELSE 1 END) AS SubtotalAmount,
                0 AS DiscountAmount,
                (SUM((IFNULL(posd.Amount,0) * IFNULL(posd.Quantity,0)) - IFNULL(posd.DiscountAmount,0) - IFNULL(posd.DiscountPercentageAmount,0)) * CASE WHEN pos.PointOfSaleType='{$return}' THEN -1 ELSE 1 END) AS TotalAmount,
                psa.TotalSessionAmount, pd.TotalDiscountAmount
                FROM pospointofsale pos 
                LEFT OUTER JOIN company co ON co.Oid = pos.Company
                LEFT OUTER JOIN pospointofsaledetail posd ON pos.Oid = posd.PointOfSale
                LEFT OUTER JOIN mstbusinesspartner bp ON pos.Customer = bp.Oid
                LEFT OUTER JOIN mstbusinesspartnergroup bpg ON bp.BusinessPartnerGroup = bpg.Oid
                LEFT OUTER JOIN mstitem i ON posd.Item = i.Oid
                LEFT OUTER JOIN mstpaymentmethod pm ON pos.PaymentMethod = pm.Oid
                LEFT OUTER JOIN mstwarehouse w ON pos.Warehouse = w.Oid
                LEFT OUTER JOIN mstemployee e ON pos.Employee = e.Oid
                LEFT OUTER JOIN postable pt ON pos.POSTable = pt.Oid 
                LEFT OUTER JOIN mstcity ct ON bp.City = ct.Oid
                LEFT OUTER JOIN mstitemgroup ig ON i.ItemGroup = ig.Oid
                LEFT OUTER JOIN mstcurrency c ON pos.Currency = c.Oid
                LEFT OUTER JOIN sysstatus s ON pos.Status = s.Oid
                LEFT OUTER JOIN user u ON pos.User = u.Oid
                LEFT OUTER JOIN (
                SELECT ps.Company, SUM(psa.AmountBase) AS TotalSessionAmount
                FROM possessionamount psa
                LEFT OUTER JOIN possession ps ON ps.Oid = psa.POSSession
                WHERE 8=8
                ) psa ON psa.Company = pos.Company
                LEFT OUTER JOIN (
                SELECT pd.Company, SUM(IFNULL(pd.DiscountAmountBase,0) + IFNULL(pd.DiscountPercentageAmount,0)) AS TotalDiscountAmount
                FROM pospointofsale pd
                WHERE 1=1 AND 3=3
                ) pd ON pd.Company = pos.Company
                WHERE pos.GCRecord IS NULL AND s.Code IN ('paid', 'complete','posted') AND 1=1 AND 3=3 AND 4=4
                GROUP BY ig.Name, ig.Code, c.Code, DATE_FORMAT(pos.Date, '%Y%m%d')
                UNION ALL
                SELECT '','', cur.Code, psa.Date,0,0,0,psa.AmountBase,0, psa.AmountBase,0,0
                FROM possessionamount psa
                LEFT OUTER JOIN mstpaymentmethod pm ON pm.Oid = psa.PaymentMethod
                LEFT OUTER JOIN possession ps ON ps.Oid = psa.POSSession 
                LEFT OUTER JOIN user u ON u.Oid = ps.User
                LEFT OUTER JOIN mstwarehouse w ON w.Oid = ps.Warehouse
                LEFT OUTER JOIN mstcurrency cur ON cur.Oid = psa.Currency
                WHERE psa.GCRecord IS NULL AND 8=8
                ) AS Result";
            case 9:
                return "SELECT * FROM (SELECT
                co.Code AS Comp,
                CONCAT(ct.Name, ' - ', ct.Code) AS GroupName,
                c.Code AS CurrencyCode,
                DATE_FORMAT(pos.Date, '%e %b %Y') AS Date,
                DATE_FORMAT(pos.Date, '%Y%m%d') AS DateOrder,
                COUNT(pos.Oid) AS Qty,
                SUM(IFNULL(posd.DetailSubtotal,0)) AS DetailSubtotal,
                SUM(IFNULL(posd.DetailDiscount,0)) AS DetailDiscount,
                SUM(IFNULL(posd.DetailTotal,0)) AS SubtotalAmount,
                SUM(IFNULL(pos.SubtotalAmount,0) * CASE WHEN pos.PointOfSaleType='{$return}' THEN -1 ELSE 1 END) AS SubtotalAmount1,
                (SUM(IFNULL(pos.DiscountAmount,0) + IFNULL(pos.DiscountPercentageAmount,0)) * CASE WHEN pos.PointOfSaleType='{$return}' THEN -1 ELSE 1 END) AS DiscountAmount,
                SUM(IFNULL(pos.TotalAmount,0) * CASE WHEN pos.PointOfSaleType='{$return}' THEN -1 ELSE 1 END) AS TotalAmount
                FROM pospointofsale pos 
                LEFT OUTER JOIN company co ON co.Oid = pos.Company
                LEFT OUTER JOIN 
                (SELECT pos.Oid,
                SUM(IFNULL(posd.Quantity,0) * IFNULL(posd.Amount,0)) AS DetailSubtotal,
                SUM(IFNULL(posd.DiscountAmount,0) + IFNULL(posd.DiscountPercentageAmount,0)) AS DetailDiscount,
                SUM((IFNULL(posd.Quantity,0) * IFNULL(posd.Amount,0)) - IFNULL(posd.DiscountAmount,0) - IFNULL(posd.DiscountPercentageAmount,0)) AS DetailTotal
                FROM pospointofsale pos
                LEFT OUTER JOIN sysstatus s ON pos.Status = s.Oid
                LEFT OUTER JOIN pospointofsaledetail posd ON posd.PointOfSale = pos.Oid
                WHERE s.Code IN ('paid', 'complete','posted') AND 1=1 AND 3=3 GROUP BY pos.Oid) AS posd ON posd.Oid = pos.Oid
                LEFT OUTER JOIN mstpaymentmethod pm ON pos.PaymentMethod = pm.Oid
                LEFT OUTER JOIN mstwarehouse w ON pos.Warehouse = w.Oid
                LEFT OUTER JOIN mstbusinesspartner bp ON pos.Customer = bp.Oid
                LEFT OUTER JOIN mstbusinesspartnergroup bpg ON bp.BusinessPartnerGroup = bpg.Oid
                LEFT OUTER JOIN mstemployee e ON pos.Employee = e.Oid
                LEFT OUTER JOIN postable pt ON pos.POSTable = pt.Oid 
                LEFT OUTER JOIN mstcity ct ON bp.City = ct.Oid
                LEFT OUTER JOIN mstcurrency c ON pos.Currency = c.Oid
                LEFT OUTER JOIN sysstatus s ON pos.Status = s.Oid
                LEFT OUTER JOIN user u ON pos.User = u.Oid
                WHERE pos.GCRecord IS NULL AND s.Code IN ('paid', 'complete','posted') AND 1=1 AND 4=4
                GROUP BY pos.Currency, ct.Name, ct.Code, c.Code, `Date`
                UNION ALL
                SELECT '','', cur.Code, psa.Date, psa.Date,0,0,0,0,psa.AmountBase,0, psa.AmountBase
                FROM possessionamount psa
                LEFT OUTER JOIN mstpaymentmethod pm ON pm.Oid = psa.PaymentMethod
                LEFT OUTER JOIN possession ps ON ps.Oid = psa.POSSession 
                LEFT OUTER JOIN user u ON u.Oid = ps.User
                LEFT OUTER JOIN mstwarehouse w ON w.Oid = ps.Warehouse
                LEFT OUTER JOIN mstcurrency cur ON cur.Oid = psa.Currency
                WHERE psa.GCRecord IS NULL AND 8=8
                ) AS Result";
            case 10:
                return "SELECT * FROM (SELECT
                    co.Code AS Comp,
                    pos.Oid,
                    CONCAT(pos.Code, CASE WHEN pos.PointOfSaleType='{$return}' THEN ' (ret)' ELSE '' END) AS Code,
                    pos.Date AS DateOrder,
                    pos.Date AS Date,
                    CONCAT(bp.Name) AS BusinessPartner,
                    CONCAT(pm.Name) AS PaymentMethod,
                    u.Name AS Cashier,
                    CONCAT(w.Name, ' - ',  w.Code) AS Warehouse,
                    CONCAT(pt.Name) AS TableName,
                    CONCAT(e.Name) AS EmployeeName,
                    c.Code AS CurrencyCode,
                    ((IFNULL(pos.DiscountAmount,0) + IFNULL(pos.DiscountPercentageAmount,0)) * CASE WHEN pos.PointOfSaleType='{$return}' THEN -1 ELSE 1 END) AS DiscountAmount,
                    pos.DiscountPercentage,
                    (IFNULL(pos.SubtotalAmount,0) * CASE WHEN pos.PointOfSaleType='{$return}' THEN -1 ELSE 1 END) AS SubtotalAmount,
                    (IFNULL(pos.TotalAmount,0) * CASE WHEN pos.PointOfSaleType='{$return}' THEN -1 ELSE 1 END) AS TotalAmount,
                    CONCAT(i.Name, ' - ',  i.Code) AS ItemName,
                    posd.Quantity DetailQuantity,
                    posd.Amount DetailAmount,
                    posd.DiscountPercentage DetailDiscount,
                    posd.DiscountAmount + posd.DiscountPercentageAmount AS DetailDiscountAmount,
                    (posd.Quantity * posd.Amount) - (posd.DiscountAmount + posd.DiscountPercentageAmount) AS DetailTotal,
                    pos.Note
                    FROM pospointofsale pos
                    LEFT OUTER JOIN pospointofsaledetail posd ON pos.Oid = posd.PointOfSale
                    LEFT OUTER JOIN user u ON pos.User = u.Oid
                    LEFT OUTER JOIN postable pt ON pos.POSTable = pt.Oid 
                    LEFT OUTER JOIN mstwarehouse w ON pos.Warehouse = w.Oid
                    LEFT OUTER JOIN mstpaymentmethod pm ON pos.PaymentMethod = pm.Oid
                    LEFT OUTER JOIN mstemployee e ON pos.Employee = e.Oid
                    LEFT OUTER JOIN mstcurrency c ON pos.Currency = c.Oid
                    LEFT OUTER JOIN mstbusinesspartner bp ON pos.Customer = bp.Oid
                    LEFT OUTER JOIN mstbusinesspartnergroup bpg ON bp.BusinessPartnerGroup = bpg.Oid
                    LEFT OUTER JOIN mstcity ct ON bp.City = ct.Oid
                    LEFT OUTER JOIN mstitem i ON posd.Item = i.Oid
                    LEFT OUTER JOIN mstitemgroup ig ON i.ItemGroup = ig.Oid
                    LEFT OUTER JOIN company co ON co.Oid = pos.Company
                    LEFT OUTER JOIN sysstatus s ON pos.Status = s.Oid
                    WHERE pos.GCRecord IS NULL AND s.Code IN ('paid', 'complete', 'posted') AND 1=1 AND 3=3 AND 4=4 AND 6=6
                    UNION ALL
                    SELECT '',psa.Oid, psa.Date, psa.Date, psa.Date, '', pm.Name AS PaymentMethod, u.UserName, w.Name, '','',cur.Code,0,0,psa.AmountBase, psa.AmountBase, '', 0,psa.AmountBase,0,0,psa.AmountBase,''
                    FROM possessionamount psa 
                    LEFT OUTER JOIN mstpaymentmethod pm ON pm.Oid = psa.PaymentMethod
                    LEFT OUTER JOIN possession ps ON ps.Oid = psa.POSSession 
                    LEFT OUTER JOIN user u ON u.Oid = ps.User
                    LEFT OUTER JOIN mstwarehouse w ON w.Oid = ps.Warehouse
                    LEFT OUTER JOIN mstcurrency cur ON cur.Oid = psa.Currency
                    WHERE psa.GCRecord IS NULL AND 8=8
                    ) AS Result
                    ORDER BY Warehouse, `Date`, Code, Oid";
            case 10.1:
                return "SELECT * FROM(SELECT
                    co.Code AS Comp,
                    pos.Oid,
                    CONCAT(pos.Code, CASE WHEN pos.PointOfSaleType='{$return}' THEN ' (ret)' ELSE '' END) AS Code,
                    pos.Date AS DateOrder,
                    pos.Date AS Date,
                    bp.Name AS BusinessPartner,
                    pm.Name AS PaymentMethod,
                    u.Name AS Cashier,
                    CONCAT(w.Name, ' - ',  w.Code) AS Warehouse,
                    pt.Name AS TableName,
                    e.Name AS EmployeeName,
                    p.Name AS Project,
                    e2.Name AS EmployeeName2,
                    c.Code AS CurrencyCode,
                    ((IFNULL(pos.DiscountAmount,0) + IFNULL(pos.DiscountPercentageAmount,0)) * CASE WHEN pos.PointOfSaleType='{$return}' THEN -1 ELSE 1 END) AS DiscountAmount,
                    pos.DiscountPercentage,
                    (IFNULL(pos.SubtotalAmount,0) * CASE WHEN pos.PointOfSaleType='{$return}' THEN -1 ELSE 1 END) AS SubtotalAmount,
                    (IFNULL(pos.TotalAmount,0) * CASE WHEN pos.PointOfSaleType='{$return}' THEN -1 ELSE 1 END) AS TotalAmount,
                    CONCAT(i.Name, ' - ',  i.Code) AS ItemName,
                    posd.Quantity DetailQuantity,
                    posd.Amount DetailAmount,
                    posd.DiscountPercentage DetailDiscount,
                    posd.DiscountAmount + posd.DiscountPercentageAmount AS DetailDiscountAmount,
                    (posd.Quantity * posd.Amount) - (posd.DiscountAmount + posd.DiscountPercentageAmount) AS DetailTotal,
                    pos.Note
                    FROM pospointofsale pos
                    LEFT OUTER JOIN company co ON co.Oid = pos.Company
                    LEFT OUTER JOIN pospointofsaledetail posd ON pos.Oid = posd.PointOfSale
                    LEFT OUTER JOIN user u ON pos.User = u.Oid
                    LEFT OUTER JOIN postable pt ON pos.POSTable = pt.Oid 
                    LEFT OUTER JOIN mstwarehouse w ON pos.Warehouse = w.Oid
                    LEFT OUTER JOIN mstpaymentmethod pm ON pos.PaymentMethod = pm.Oid
                    LEFT OUTER JOIN mstemployee e ON pos.Employee = e.Oid
                    LEFT OUTER JOIN mstemployee e2 ON pos.Employee2 = e2.Oid
                    LEFT OUTER JOIN mstcurrency c ON pos.Currency = c.Oid
                    LEFT OUTER JOIN mstbusinesspartner bp ON pos.Customer = bp.Oid
                    LEFT OUTER JOIN mstbusinesspartnergroup bpg ON bp.BusinessPartnerGroup = bpg.Oid
                    LEFT OUTER JOIN mstcity ct ON bp.City = ct.Oid
                    LEFT OUTER JOIN mstitem i ON posd.Item = i.Oid
                    LEFT OUTER JOIN mstitemgroup ig ON i.ItemGroup = ig.Oid
                    LEFT OUTER JOIN sysstatus s ON pos.Status = s.Oid
                    LEFT OUTER JOIN mstproject p ON pos.Project = p.Oid
                    WHERE pos.GCRecord IS NULL AND s.Code IN ('paid', 'complete','posted') AND 1=1 AND 3=3 AND 4=4 AND 6=6
                    UNION ALL
                    SELECT '',psa.Oid, psa.Date, psa.Date, psa.Date, '', pm.Name AS PaymentMethod, u.Name, w.Name, '','','','',cur.Code,0,0,psa.AmountBase, psa.AmountBase, '', 1,psa.AmountBase,0,0,psa.AmountBase,''
                    FROM possessionamount psa
                    LEFT OUTER JOIN mstpaymentmethod pm ON pm.Oid = psa.PaymentMethod
                    LEFT OUTER JOIN possession ps ON ps.Oid = psa.POSSession 
                    LEFT OUTER JOIN user u ON u.Oid = ps.User
                    LEFT OUTER JOIN mstwarehouse w ON w.Oid = ps.Warehouse
                    LEFT OUTER JOIN mstcurrency cur ON cur.Oid = psa.Currency
                    WHERE psa.GCRecord IS NULL AND 8=8
                    ) AS Result
                    ORDER BY Warehouse, `Date`, Code, Oid";
            case 11:
                return "SELECT * FROM (SELECT
                co.Code AS Comp,
                pos.Oid,
                CONCAT(pos.Code, CASE WHEN pos.PointOfSaleType='{$return}' THEN ' (ret)' ELSE '' END) AS Code,
                pos.Date AS DateOrder,
                pos.Date AS Date,
                bp.Name AS BusinessPartner,
                pm.Name AS PaymentMethod,
                w.Name AS Warehouse,
                pt.Name AS TableName,
                e.Name AS EmployeeName,
                pos.Quantity,
                u.Name AS Cashier,
                c.Code AS CurrencyCode,
                ((IFNULL(pos.DiscountAmount,0) + IFNULL(pos.DiscountPercentageAmount,0)) * CASE WHEN pos.PointOfSaleType='{$return}' THEN -1 ELSE 1 END) AS DiscountAmount,
                (IFNULL(pos.SubtotalAmount,0) * CASE WHEN pos.PointOfSaleType='{$return}' THEN -1 ELSE 1 END) AS SubtotalAmount,
                (IFNULL(pos.TotalAmount,0) * CASE WHEN pos.PointOfSaleType='{$return}' THEN -1 ELSE 1 END) AS TotalAmount
                FROM pospointofsale pos
                LEFT OUTER JOIN user u ON pos.User = u.Oid
                LEFT OUTER JOIN postable pt ON pos.POSTable = pt.Oid 
                LEFT OUTER JOIN mstwarehouse w ON pos.Warehouse = w.Oid
                LEFT OUTER JOIN mstpaymentmethod pm ON pos.PaymentMethod = pm.Oid
                LEFT OUTER JOIN mstemployee e ON pos.Employee = e.Oid
                LEFT OUTER JOIN mstcurrency c ON pos.Currency = c.Oid
                LEFT OUTER JOIN mstbusinesspartner bp ON pos.Customer = bp.Oid
                LEFT OUTER JOIN mstbusinesspartnergroup bpg ON bp.BusinessPartnerGroup = bpg.Oid
                LEFT OUTER JOIN company co ON co.Oid = pos.Company
                LEFT OUTER JOIN sysstatus s ON pos.Status = s.Oid
                WHERE pos.GCRecord IS NULL AND s.Code IN ('paid', 'complete','posted') AND 1=1 AND 3=3 AND 4=4
                UNION ALL
                SELECT '',psa.Oid, psa.Date, psa.Date, psa.Date, '', pm.Name AS PaymentMethod, w.Name, '','',0,'',cur.Code,0, psa.AmountBase, psa.AmountBase
                FROM possessionamount psa
                LEFT OUTER JOIN mstpaymentmethod pm ON pm.Oid = psa.PaymentMethod
                LEFT OUTER JOIN possession ps ON ps.Oid = psa.POSSession 
                LEFT OUTER JOIN user u ON u.Oid = ps.User
                LEFT OUTER JOIN mstwarehouse w ON w.Oid = ps.Warehouse
                LEFT OUTER JOIN mstcurrency cur ON cur.Oid = psa.Currency
                WHERE psa.GCRecord IS NULL AND 8=8
                ) AS Result
                ORDER BY Warehouse, `Date`, Code, Oid";
                    //   -- ORDER BY pos.Date, u.Name,pos.Oid
            case 12:
                return "SELECT * FROM (SELECT
                co.Code AS Comp,
                pos.Oid,
                CONCAT(pos.Code, CASE WHEN pos.PointOfSaleType='{$return}' THEN ' (ret)' ELSE '' END) AS Code,
                pos.Date AS DateOrder,
                pos.Date AS Date,
                bp.Name AS BusinessPartner,
                pm.Name AS PaymentMethod,
                w.Name AS Warehouse,
                pt.Name AS TableName,
                e.Name AS EmployeeName,
                pos.Quantity,
                u.Name AS Cashier,
                c.Code AS CurrencyCode,
                ((IFNULL(pos.DiscountAmount,0) + IFNULL(pos.DiscountPercentageAmount,0)) * CASE WHEN pos.PointOfSaleType='{$return}' THEN -1 ELSE 1 END) AS DiscountAmount,
                (IFNULL(pos.SubtotalAmount,0) * CASE WHEN pos.PointOfSaleType='{$return}' THEN -1 ELSE 1 END) AS SubtotalAmount,
                (IFNULL(pos.TotalAmount,0) * CASE WHEN pos.PointOfSaleType='{$return}' THEN -1 ELSE 1 END) AS TotalAmount
                FROM pospointofsale pos
                LEFT OUTER JOIN user u ON pos.User = u.Oid
                LEFT OUTER JOIN postable pt ON pos.POSTable = pt.Oid 
                LEFT OUTER JOIN mstwarehouse w ON pos.Warehouse = w.Oid
                LEFT OUTER JOIN mstpaymentmethod pm ON pos.PaymentMethod = pm.Oid
                LEFT OUTER JOIN mstemployee e ON pos.Employee = e.Oid
                LEFT OUTER JOIN company co ON co.Oid = pos.Company
                LEFT OUTER JOIN mstcurrency c ON pos.Currency = c.Oid
                LEFT OUTER JOIN mstbusinesspartner bp ON pos.Customer = bp.Oid
                LEFT OUTER JOIN mstbusinesspartnergroup bpg ON bp.BusinessPartnerGroup = bpg.Oid
                LEFT OUTER JOIN mstcity ct ON bp.City = ct.Oid
                LEFT OUTER JOIN sysstatus s ON pos.Status = s.Oid
                WHERE pos.GCRecord IS NULL AND s.Code IN ('paid', 'complete','posted') AND 1=1 AND 3=3 AND 4=4
                UNION ALL
                SELECT '',psa.Oid, psa.Date, psa.Date, psa.Date, '', pm.Name AS PaymentMethod, w.Name, '','',0,'',cur.Code,psa.AmountBase, psa.AmountBase, psa.AmountBase
                FROM possessionamount psa
                LEFT OUTER JOIN mstpaymentmethod pm ON pm.Oid = psa.PaymentMethod
                LEFT OUTER JOIN possession ps ON ps.Oid = psa.POSSession 
                LEFT OUTER JOIN user u ON u.Oid = ps.User
                LEFT OUTER JOIN mstwarehouse w ON w.Oid = ps.Warehouse
                LEFT OUTER JOIN mstcurrency cur ON cur.Oid = psa.Currency
                WHERE psa.GCRecord IS NULL AND 8=8
                ) AS Result
                ORDER BY Warehouse, `Date`, Code, Oid";
                    //   ORDER BY pos.Date, pt.Name, pos.Oid 
            case 13:
                return "SELECT * FROM (SELECT
                co.Code AS Comp,
                pos.Oid,
                CONCAT(pos.Code, CASE WHEN pos.PointOfSaleType='{$return}' THEN ' (ret)' ELSE '' END) AS Code,
                pos.Date AS DateOrder,
                pos.Date AS Date,
                bp.Name AS BusinessPartner,
                pm.Name AS PaymentMethod,
                w.Name AS Warehouse,
                pt.Name AS TableName,
                e.Name AS EmployeeName,
                pos.Quantity,
                u.Name AS Cashier,
                c.Code AS CurrencyCode,
                ((IFNULL(pos.DiscountAmount,0) + IFNULL(pos.DiscountPercentageAmount,0)) * CASE WHEN pos.PointOfSaleType='{$return}' THEN -1 ELSE 1 END) AS DiscountAmount,
                (IFNULL(pos.SubtotalAmount,0) * CASE WHEN pos.PointOfSaleType='{$return}' THEN -1 ELSE 1 END) AS SubtotalAmount,
                (IFNULL(pos.TotalAmount,0) * CASE WHEN pos.PointOfSaleType='{$return}' THEN -1 ELSE 1 END) AS TotalAmount
                FROM pospointofsale pos
                LEFT OUTER JOIN user u ON pos.User = u.Oid
                LEFT OUTER JOIN postable pt ON pos.POSTable = pt.Oid 
                LEFT OUTER JOIN mstwarehouse w ON pos.Warehouse = w.Oid
                LEFT OUTER JOIN mstpaymentmethod pm ON pos.PaymentMethod = pm.Oid
                LEFT OUTER JOIN mstemployee e ON pos.Employee = e.Oid
                LEFT OUTER JOIN mstcurrency c ON pos.Currency = c.Oid
                LEFT OUTER JOIN mstbusinesspartner bp ON pos.Customer = bp.Oid
                LEFT OUTER JOIN mstbusinesspartnergroup bpg ON bp.BusinessPartnerGroup = bpg.Oid
                LEFT OUTER JOIN mstcity ct ON bp.City = ct.Oid
                LEFT OUTER JOIN company co ON co.Oid = pos.Company
                LEFT OUTER JOIN sysstatus s ON pos.Status = s.Oid
                WHERE pos.GCRecord IS NULL AND s.Code IN ('paid', 'complete','posted') AND 1=1 AND 3=3 AND 4=4
                UNION ALL
                SELECT '',psa.Oid, psa.Date, psa.Date, psa.Date, '', pm.Name AS PaymentMethod, w.Name, '','',0,'',cur.Code,0, psa.AmountBase, psa.AmountBase
                FROM possessionamount psa
                LEFT OUTER JOIN mstpaymentmethod pm ON pm.Oid = psa.PaymentMethod
                LEFT OUTER JOIN possession ps ON ps.Oid = psa.POSSession 
                LEFT OUTER JOIN user u ON u.Oid = ps.User
                LEFT OUTER JOIN mstwarehouse w ON w.Oid = ps.Warehouse
                LEFT OUTER JOIN mstcurrency cur ON cur.Oid = psa.Currency
                WHERE psa.GCRecord IS NULL AND 8=8
                ) AS Result
                ORDER BY Warehouse, `Date`, Code, Oid";
                    //   ORDER BY pos.Date, e.Name, pos.Oid 
            case 14:
                return "SELECT CONCAT(pm.Name,' - ',pm.Code) AS GroupName,
                      co.Code AS Comp,
                      pm.Name AS Item,
                      'Payment Method' AS Judul,
                      sum(case when DATE_FORMAT(pos.Date,'%d')=1 then pos.TotalAmount else 0 END) AS d1,
                      sum(case when DATE_FORMAT(pos.Date,'%d')=2 then pos.TotalAmount else 0 END) AS d2,
                      sum(case when DATE_FORMAT(pos.Date,'%d')=3 then pos.TotalAmount else 0 END) AS d3,
                      sum(case when DATE_FORMAT(pos.Date,'%d')=4 then pos.TotalAmount else 0 END) AS d4,
                      sum(case when DATE_FORMAT(pos.Date,'%d')=5 then pos.TotalAmount else 0 END) AS d5,
                      sum(case when DATE_FORMAT(pos.Date,'%d')=6 then pos.TotalAmount else 0 END) AS d6,
                      sum(case when DATE_FORMAT(pos.Date,'%d')=7 then pos.TotalAmount else 0 END) AS d7,
                      sum(case when DATE_FORMAT(pos.Date,'%d')=8 then pos.TotalAmount else 0 END) AS d8,
                      sum(case when DATE_FORMAT(pos.Date,'%d')=9 then pos.TotalAmount else 0 END) AS d9,
                      sum(case when DATE_FORMAT(pos.Date,'%d')=10 then pos.TotalAmount else 0 END) AS d10,
                      sum(case when DATE_FORMAT(pos.Date,'%d')=11 then pos.TotalAmount else 0 END) AS d11,
                      sum(case when DATE_FORMAT(pos.Date,'%d')=12 then pos.TotalAmount else 0 END) AS d12,
                      sum(case when DATE_FORMAT(pos.Date,'%d')=13 then pos.TotalAmount else 0 END) AS d13,
                      sum(case when DATE_FORMAT(pos.Date,'%d')=14 then pos.TotalAmount else 0 END) AS d14,
                      sum(case when DATE_FORMAT(pos.Date,'%d')=15 then pos.TotalAmount else 0 END) AS d15,
                      sum(case when DATE_FORMAT(pos.Date,'%d')=16 then pos.TotalAmount else 0 END) AS d16,
                      sum(case when DATE_FORMAT(pos.Date,'%d')=17 then pos.TotalAmount else 0 END) AS d17,
                      sum(case when DATE_FORMAT(pos.Date,'%d')=18 then pos.TotalAmount else 0 END) AS d18,
                      sum(case when DATE_FORMAT(pos.Date,'%d')=19 then pos.TotalAmount else 0 END) AS d19,
                      sum(case when DATE_FORMAT(pos.Date,'%d')=20 then pos.TotalAmount else 0 END) AS d20,
                      sum(case when DATE_FORMAT(pos.Date,'%d')=21 then pos.TotalAmount else 0 END) AS d21,
                      sum(case when DATE_FORMAT(pos.Date,'%d')=22 then pos.TotalAmount else 0 END) AS d22,
                      sum(case when DATE_FORMAT(pos.Date,'%d')=23 then pos.TotalAmount else 0 END) AS d23,
                      sum(case when DATE_FORMAT(pos.Date,'%d')=24 then pos.TotalAmount else 0 END) AS d24,
                      sum(case when DATE_FORMAT(pos.Date,'%d')=25 then pos.TotalAmount else 0 END) AS d25,
                      sum(case when DATE_FORMAT(pos.Date,'%d')=26 then pos.TotalAmount else 0 END) AS d26,
                      sum(case when DATE_FORMAT(pos.Date,'%d')=27 then pos.TotalAmount else 0 END) AS d27,
                      sum(case when DATE_FORMAT(pos.Date,'%d')=28 then pos.TotalAmount else 0 END) AS d28,
                      sum(case when DATE_FORMAT(pos.Date,'%d')=29 then pos.TotalAmount else 0 END) AS d29,
                      sum(case when DATE_FORMAT(pos.Date,'%d')=30 then pos.TotalAmount else 0 END) AS d30,
                      sum(case when DATE_FORMAT(pos.Date,'%d')=31 then pos.TotalAmount else 0 END) AS d31
                      FROM pospointofsale  pos
                      LEFT OUTER JOIN mstpaymentmethod pm ON pos.PaymentMethod = pm.Oid
                      LEFT OUTER JOIN company co ON co.Oid = pos.Company
                      LEFT OUTER JOIN sysstatus s ON pos.Status = s.Oid
                      WHERE pos.GCRecord IS NULL AND s.Code IN ('paid', 'complete','posted') AND pm.GCRecord IS NULL AND 2=2 AND 4=4
                      GROUP BY pm.Oid";
            case 15:
                return
                    "SELECT pos.Type,
                        pos.Code,
                        pos.Date,
                        pos.PaymentMethod,
                        pos.PaymentAmount,
                        pos.Currency,
                        pos.PaymentBase
                    FROM
                    (SELECT co.Code AS Comp,
                        pty.Code AS Type,
                        p.Oid, 
                        p.Code, 
                        p.Date, pm.Name AS PaymentMethod, 
                        (IFNULL(p.PaymentAmount,0) * CASE WHEN pty.Code='SRETURN' THEN -1 ELSE 1 END) AS PaymentAmount, 
                        c.Code AS Currency, 
                        (IFNULL(p.PaymentAmountBase,0) * CASE WHEN pty.Code='SRETURN' THEN -1 ELSE 1 END) AS PaymentBase
                    FROM pospointofsale p
                        LEFT OUTER JOIN mstpaymentmethod pm ON p.PaymentMethod = pm.Oid
                        LEFT OUTER JOIN accaccount a ON pm.Account = a.Oid
                        LEFT OUTER JOIN mstcurrency c ON a.Currency = c.Oid
                        LEFT OUTER JOIN sysstatus s ON p.Status = s.Oid
                        LEFT OUTER JOIN user u ON p.User = u.Oid
                        LEFT OUTER JOIN postable pt ON p.POSTable = pt.Oid 
                        LEFT OUTER JOIN syspointofsaletype pty ON pty.Oid = p.PointOfSaleType
                        LEFT OUTER JOIN company co ON p.Company = co.Oid
                        WHERE s.Code IN ('Paid','Posted') AND p.PaymentMethod IS NOT NULL AND 5=5 AND 4=4
                    UNION ALL
                    SELECT co.Code AS Comp,pty.Code AS Type, p.Oid, p.Code, p.Date, pm.Name AS PaymentMethod, 
                        (IFNULL(p.PaymentAmount2,0) * CASE WHEN pty.Code='SRETURN' THEN -1 ELSE 1 END) AS PaymentAmount, 
                        c.Code AS Currency, 
                        (IFNULL(p.PaymentAmountBase2,0) * CASE WHEN pty.Code='SRETURN' THEN -1 ELSE 1 END) AS PaymentBase
                    FROM pospointofsale p
                        LEFT OUTER JOIN mstpaymentmethod pm ON p.PaymentMethod2 = pm.Oid
                        LEFT OUTER JOIN accaccount a ON pm.Account = a.Oid
                        LEFT OUTER JOIN mstcurrency c ON a.Currency = c.Oid
                        LEFT OUTER JOIN sysstatus s ON p.Status = s.Oid
                        LEFT OUTER JOIN user u ON p.User = u.Oid
                        LEFT OUTER JOIN postable pt ON p.POSTable = pt.Oid 
                        LEFT OUTER JOIN company co ON p.Company = co.Oid
                        LEFT OUTER JOIN syspointofsaletype pty ON pty.Oid = p.PointOfSaleType
                        WHERE s.Code IN ('Paid','Posted') AND p.PaymentMethod2 IS NOT NULL AND 5=5 AND 4=4
                    UNION ALL
                    SELECT co.Code AS Comp,pty.Code AS Type, p.Oid, p.Code, p.Date, pm.Name AS PaymentMethod, 
                        (IFNULL(p.PaymentAmount3,0) * CASE WHEN pty.Code='SRETURN' THEN -1 ELSE 1 END) AS PaymentAmount, 
                        c.Code AS Currency, 
                        (IFNULL(p.PaymentAmountBase3,0) * CASE WHEN pty.Code='SRETURN' THEN -1 ELSE 1 END) AS PaymentBase
                    FROM pospointofsale p
                        LEFT OUTER JOIN mstpaymentmethod pm ON p.PaymentMethod3 = pm.Oid
                        LEFT OUTER JOIN accaccount a ON pm.Account = a.Oid
                        LEFT OUTER JOIN mstcurrency c ON a.Currency = c.Oid
                        LEFT OUTER JOIN sysstatus s ON p.Status = s.Oid
                        LEFT OUTER JOIN user u ON p.User = u.Oid
                        LEFT OUTER JOIN postable pt ON p.POSTable = pt.Oid 
                        LEFT OUTER JOIN company co ON p.Company = co.Oid
                        LEFT OUTER JOIN syspointofsaletype pty ON pty.Oid = p.PointOfSaleType
                        WHERE s.Code  IN ('Paid','Posted') AND p.PaymentMethod3 IS NOT NULL AND 5=5 AND 4=4
                    UNION ALL
                    SELECT co.Code AS Comp,pty.Code AS Type, p.Oid, p.Code, p.Date, pm.Name AS PaymentMethod, 
                        (IFNULL(p.PaymentAmount4,0) * CASE WHEN pty.Code='SRETURN' THEN -1 ELSE 1 END) AS PaymentAmount, 
                        c.Code AS Currency, 
                        (IFNULL(p.PaymentAmountBase4,0) * CASE WHEN pty.Code='SRETURN' THEN -1 ELSE 1 END) AS PaymentBase
                    FROM pospointofsale p
                        LEFT OUTER JOIN mstpaymentmethod pm ON p.PaymentMethod4 = pm.Oid
                        LEFT OUTER JOIN accaccount a ON pm.Account = a.Oid
                        LEFT OUTER JOIN mstcurrency c ON a.Currency = c.Oid
                        LEFT OUTER JOIN sysstatus s ON p.Status = s.Oid
                        LEFT OUTER JOIN user u ON p.User = u.Oid
                        LEFT OUTER JOIN postable pt ON p.POSTable = pt.Oid 
                        LEFT OUTER JOIN company co ON p.Company = co.Oid
                        LEFT OUTER JOIN syspointofsaletype pty ON pty.Oid = p.PointOfSaleType
                        WHERE s.Code  IN ('Paid','Posted') AND p.PaymentMethod4 IS NOT NULL AND 5=5 AND 4=4
                    UNION ALL
                    SELECT co.Code AS Comp,pty.Code AS Type, p.Oid, p.Code, p.Date, pm.Name AS PaymentMethod, 
                        (IFNULL(p.PaymentAmount5,0) * CASE WHEN pty.Code='SRETURN' THEN -1 ELSE 1 END) AS PaymentAmount, 
                        c.Code AS Currency, 
                        (IFNULL(p.PaymentAmountBase5,0) * CASE WHEN pty.Code='SRETURN' THEN -1 ELSE 1 END) AS PaymentBase
                    FROM pospointofsale p
                        LEFT OUTER JOIN mstpaymentmethod pm ON p.PaymentMethod5 = pm.Oid
                        LEFT OUTER JOIN accaccount a ON pm.Account = a.Oid
                        LEFT OUTER JOIN mstcurrency c ON a.Currency = c.Oid
                        LEFT OUTER JOIN sysstatus s ON p.Status = s.Oid
                        LEFT OUTER JOIN user u ON p.User = u.Oid
                        LEFT OUTER JOIN postable pt ON p.POSTable = pt.Oid 
                        LEFT OUTER JOIN company co ON p.Company = co.Oid
                        LEFT OUTER JOIN syspointofsaletype pty ON pty.Oid = p.PointOfSaleType
                        WHERE s.Code  IN ('Paid','Posted') AND p.PaymentMethod5 IS NOT NULL AND 5=5 AND 4=4
                    UNION ALL
                    SELECT co.Code AS Comp,'Cash Trans.' AS Type, ps.Oid, CONCAT(DATE_FORMAT(ps.Date, '%Y-%m-%d'),' ',u.UserName) AS Code, ps.Date, CONCAT(pm.Name, IFNULL(psat.Name,'')) AS PaymentMethod,
                        IFNULL(psa.Amount,0) * CASE WHEN psa.Type = 2 THEN -1 ELSE 1 END AS PaymentAmount, 
                        c.Code AS Currency, 
                        IFNULL(psa.AmountBase,0) * CASE WHEN psa.Type = 2 THEN -1 ELSE 1 END AS PaymentBase
                    FROM possessionamount psa
                        LEFT OUTER JOIN possession ps ON ps.Oid = psa.POSSession
                        LEFT OUTER JOIN mstpaymentmethod pm ON psa.PaymentMethod = pm.Oid
                        LEFT OUTER JOIN accaccount a ON pm.Account = a.Oid
                        LEFT OUTER JOIN mstcurrency c ON a.Currency = c.Oid
                        LEFT OUTER JOIN possessionamounttype psat ON psa.POSSessionAmountType = psat.Oid
                        LEFT OUTER JOIN company co ON psa.Company = co.Oid
                        LEFT OUTER JOIN user u ON u.Oid = ps.User
                    WHERE 8=8 AND psa.GCRecord IS NULL) pos  
                    ORDER BY pos.Type, pos.Code, pos.Currency, pos.PaymentMethod";
                break;
            case 16:
                return
                    "SELECT Comp,'POS' AS Type, PaymentMethod,
                    SUM(PaymentAmount) AS PaymentAmount,
                    Currency, SUM(PaymentBase) AS PaymentBase
                    FROM (
                    SELECT co.Code AS Comp,p.Oid, p.Code, p.Date, pm.Name AS PaymentMethod, 
                        IFNULL(p.PaymentAmount,0) AS PaymentAmount, c.Code AS Currency, IFNULL(p.PaymentAmountBase,0) AS PaymentBase
                    FROM pospointofsale p
                        LEFT OUTER JOIN mstpaymentmethod pm ON p.PaymentMethod = pm.Oid
                        LEFT OUTER JOIN accaccount a ON pm.Account = a.Oid
                        LEFT OUTER JOIN mstcurrency c ON a.Currency = c.Oid
                        LEFT OUTER JOIN sysstatus s ON p.Status = s.Oid
                        LEFT OUTER JOIN user u ON p.User = u.Oid
                        LEFT OUTER JOIN company co ON p.Company = co.Oid
                        LEFT OUTER JOIN postable pt ON p.POSTable = pt.Oid 
                        WHERE s.Code = 'Paid' AND p.PaymentMethod IS NOT NULL AND 5=5 AND 4=4
                    UNION ALL
                    SELECT co.Code AS Comp,p.Oid, p.Code, p.Date, pm.Name AS PaymentMethod, 
                        IFNULL(p.PaymentAmount2,0) AS PaymentAmount, c.Code AS Currency, IFNULL(p.PaymentAmountBase2,0) AS PaymentBase
                    FROM pospointofsale p
                        LEFT OUTER JOIN mstpaymentmethod pm ON p.PaymentMethod2 = pm.Oid
                        LEFT OUTER JOIN accaccount a ON pm.Account = a.Oid
                        LEFT OUTER JOIN mstcurrency c ON a.Currency = c.Oid
                        LEFT OUTER JOIN sysstatus s ON p.Status = s.Oid
                        LEFT OUTER JOIN user u ON p.User = u.Oid
                        LEFT OUTER JOIN postable pt ON p.POSTable = pt.Oid 
                        LEFT OUTER JOIN company co ON p.Company = co.Oid
                        WHERE s.Code = 'Paid' AND p.PaymentMethod2 IS NOT NULL AND 5=5 AND 4=4
                    UNION ALL
                    SELECT co.Code AS Comp,p.Oid, p.Code, p.Date, pm.Name AS PaymentMethod, 
                        IFNULL(p.PaymentAmount3,0) AS PaymentAmount, c.Code AS Currency, IFNULL(p.PaymentAmountBase3,0) AS PaymentBase
                    FROM pospointofsale p
                        LEFT OUTER JOIN mstpaymentmethod pm ON p.PaymentMethod3 = pm.Oid
                        LEFT OUTER JOIN accaccount a ON pm.Account = a.Oid
                        LEFT OUTER JOIN mstcurrency c ON a.Currency = c.Oid
                        LEFT OUTER JOIN sysstatus s ON p.Status = s.Oid
                        LEFT OUTER JOIN user u ON p.User = u.Oid
                        LEFT OUTER JOIN postable pt ON p.POSTable = pt.Oid 
                        LEFT OUTER JOIN company co ON p.Company = co.Oid
                        WHERE s.Code = 'Paid' AND p.PaymentMethod3 IS NOT NULL AND 5=5 AND 4=4
                    UNION ALL
                    SELECT co.Code AS Comp,p.Oid, p.Code, p.Date, pm.Name AS PaymentMethod, 
                        IFNULL(p.PaymentAmount4,0) AS PaymentAmount, c.Code AS Currency, IFNULL(p.PaymentAmountBase4,0) AS PaymentBase
                    FROM pospointofsale p
                        LEFT OUTER JOIN mstpaymentmethod pm ON p.PaymentMethod4 = pm.Oid
                        LEFT OUTER JOIN accaccount a ON pm.Account = a.Oid
                        LEFT OUTER JOIN mstcurrency c ON a.Currency = c.Oid
                        LEFT OUTER JOIN sysstatus s ON p.Status = s.Oid
                        LEFT OUTER JOIN user u ON p.User = u.Oid
                        LEFT OUTER JOIN postable pt ON p.POSTable = pt.Oid 
                        LEFT OUTER JOIN company co ON p.Company = co.Oid
                        WHERE s.Code = 'Paid' AND p.PaymentMethod4 IS NOT NULL AND 5=5 AND 4=4
                    UNION ALL
                    SELECT co.Code AS Comp,p.Oid, p.Code, p.Date, pm.Name AS PaymentMethod, 
                        IFNULL(p.PaymentAmount5,0) AS PaymentAmount, c.Code AS Currency, IFNULL(p.PaymentAmountBase5,0) AS PaymentBase
                    FROM pospointofsale p
                        LEFT OUTER JOIN mstpaymentmethod pm ON p.PaymentMethod5 = pm.Oid
                        LEFT OUTER JOIN accaccount a ON pm.Account = a.Oid
                        LEFT OUTER JOIN mstcurrency c ON a.Currency = c.Oid
                        LEFT OUTER JOIN sysstatus s ON p.Status = s.Oid
                        LEFT OUTER JOIN user u ON p.User = u.Oid
                        LEFT OUTER JOIN postable pt ON p.POSTable = pt.Oid 
                        LEFT OUTER JOIN company co ON p.Company = co.Oid
                        WHERE s.Code = 'Paid' AND p.PaymentMethod5 IS NOT NULL AND 5=5 AND 4=4
                        ) AS Result
                    UNION ALL
                    SELECT co.Code AS Comp,'Cash Trans.' AS Type, pm.Name AS PaymentMethod,
                        SUM(IFNULL(psa.Amount,0) * CASE WHEN psa.Type = 2 THEN -1 ELSE 1 END) AS PaymentAmount, c.Code AS Currency, 
                        SUM(IFNULL(psa.AmountBase,0) * CASE WHEN psa.Type = 2 THEN -1 ELSE 1 END) AS PaymentBase
                    FROM possessionamount psa
                        LEFT OUTER JOIN possession ps ON ps.Oid = psa.POSSession
                        LEFT OUTER JOIN mstpaymentmethod pm ON psa.PaymentMethod = pm.Oid
                        LEFT OUTER JOIN accaccount a ON pm.Account = a.Oid
                        LEFT OUTER JOIN mstcurrency c ON a.Currency = c.Oid
                        LEFT OUTER JOIN company co ON psa.Company = co.Oid
                    WHERE 8=8  AND psa.GCRecord IS NULL
                    GROUP BY pm.Name, c.Code";
                break;
            case 17:
                return "SELECT 
                co.Code AS Comp,
                c.Code AS CurrencyCode,
                DATE_FORMAT(pos.Date, '%e %b %Y') AS Date,
                DATE_FORMAT(pos.Date, '%Y%m%d') AS DateOrder,
                COUNT(pos.Oid) AS Qty,
                SUM(IFNULL(posd.DetailSubtotal,0)) AS DetailSubtotal,
                SUM(IFNULL(posd.DetailDiscount,0)) AS DetailDiscount,
                SUM(IFNULL(posd.DetailTotal,0)) AS SubtotalAmount,
                SUM(IFNULL(pos.SubtotalAmount,0) * CASE WHEN pos.PointOfSaleType='{$return}' THEN -1 ELSE 1 END) AS SubtotalAmount1,
                (SUM(IFNULL(pos.DiscountAmount,0) + IFNULL(pos.DiscountPercentageAmount,0)) * CASE WHEN pos.PointOfSaleType='{$return}' THEN -1 ELSE 1 END) AS DiscountAmount,
                SUM(IFNULL(pos.TotalAmount,0) * CASE WHEN pos.PointOfSaleType='{$return}' THEN -1 ELSE 1 END) AS TotalAmount,
                IFNULL(psa.TotalSessionAmount,0) AS TotalSessionAmount
                FROM pospointofsale pos 
                LEFT OUTER JOIN 
                (
                    SELECT pos.Oid,
                    SUM(IFNULL(posd.Quantity,0) * IFNULL(posd.Amount,0)) AS DetailSubtotal,
                    SUM(IFNULL(posd.DiscountAmount,0) + IFNULL(posd.DiscountPercentageAmount,0)) AS DetailDiscount,
                    SUM((IFNULL(posd.Quantity,0) * IFNULL(posd.Amount,0)) - IFNULL(posd.DiscountAmount,0) - IFNULL(posd.DiscountPercentageAmount,0)) AS DetailTotal
                    FROM pospointofsale pos
                    LEFT OUTER JOIN sysstatus s ON pos.Status = s.Oid
                    LEFT OUTER JOIN pospointofsaledetail posd ON posd.PointOfSale = pos.Oid
                    WHERE s.Code IN ('paid', 'complete','posted') AND 1=1 AND 3=3 GROUP BY pos.Oid
                ) AS posd ON posd.Oid = pos.Oid  
                LEFT OUTER JOIN (
                    SELECT  DATE_FORMAT(ps.Date, '%e %b %Y') AS Date, 
                    SUM(IFNULL(CASE WHEN psa.Type = 2 THEN IFNULL(psa.AmountBase,0) * -1 ELSE IFNULL(psa.AmountBase,0) END,0)) AS TotalSessionAmount
                    FROM possessionamount psa
                    LEFT OUTER JOIN possession ps ON ps.Oid = psa.POSSession
                    WHERE 8=8
                    GROUP BY DATE_FORMAT(ps.Date, '%e %b %Y')
                ) psa ON psa.Date = DATE_FORMAT(pos.Date, '%e %b %Y') 
                LEFT OUTER JOIN mstbusinesspartner bp ON pos.Customer = bp.Oid
                LEFT OUTER JOIN mstbusinesspartnergroup bpg ON bp.BusinessPartnerGroup = bpg.Oid
                LEFT OUTER JOIN mstcity ct ON bp.City = ct.Oid
                LEFT OUTER JOIN mstpaymentmethod pm ON pos.PaymentMethod = pm.Oid
                LEFT OUTER JOIN mstwarehouse w ON pos.Warehouse = w.Oid
                LEFT OUTER JOIN mstemployee e ON pos.Employee = e.Oid
                LEFT OUTER JOIN postable pt ON pos.POSTable = pt.Oid 
                LEFT OUTER JOIN user u ON pos.User = u.Oid
                LEFT OUTER JOIN mstcurrency c ON pos.Currency = c.Oid
                LEFT OUTER JOIN company co ON co.Oid = pos.Company
                LEFT OUTER JOIN sysstatus s ON pos.Status = s.Oid
                WHERE pos.GCRecord IS NULL AND s.Code IN ('paid', 'complete','posted') AND 1=1 AND 4=4
                GROUP BY c.Code, DATE_FORMAT(pos.Date, '%Y%m%d')";
                break;
            case 18:
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
                    CONCAT(i.Name, ' - ', i.Code) AS GroupName,
                    c.Code AS CurrencyCode,
                    co.Code AS Comp,
                    DATE_FORMAT(pos.Date, '%e %b %Y') AS Date,
                    DATE_FORMAT(pos.Date, '%Y%m%d') AS DateOrder,
                    SUM(CASE WHEN w.Code = '" . $wh1 . "' THEN IFNULL(posd.Quantity,0) ELSE 0 END) AS w1qty,
                    SUM(CASE WHEN w.Code = '" . $wh1 . "' THEN (IFNULL(posd.Amount,0) * IFNULL(posd.Quantity,0)) - IFNULL(posd.DiscountAmount,0) - IFNULL(posd.DiscountPercentageAmount,0) ELSE 0 END) AS w1amt,
                    SUM(CASE WHEN w.Code = '" . $wh2 . "' THEN IFNULL(posd.Quantity,0) ELSE 0 END) AS w2qty,
                    SUM(CASE WHEN w.Code = '" . $wh2 . "' THEN (IFNULL(posd.Amount,0) * IFNULL(posd.Quantity,0)) - IFNULL(posd.DiscountAmount,0) - IFNULL(posd.DiscountPercentageAmount,0) ELSE 0 END) AS w2amt,
                    SUM(CASE WHEN w.Code = '" . $wh3 . "' THEN IFNULL(posd.Quantity,0) ELSE 0 END) AS w3qty,
                    SUM(CASE WHEN w.Code = '" . $wh3 . "' THEN (IFNULL(posd.Amount,0) * IFNULL(posd.Quantity,0)) - IFNULL(posd.DiscountAmount,0) - IFNULL(posd.DiscountPercentageAmount,0) ELSE 0 END) AS w3amt,
                    SUM(CASE WHEN w.Code = '" . $wh4 . "' THEN IFNULL(posd.Quantity,0) ELSE 0 END) AS w4qty,
                    SUM(CASE WHEN w.Code = '" . $wh4 . "' THEN (IFNULL(posd.Amount,0) * IFNULL(posd.Quantity,0)) - IFNULL(posd.DiscountAmount,0) - IFNULL(posd.DiscountPercentageAmount,0) ELSE 0 END) AS w4amt,
                    SUM(CASE WHEN w.Code = '" . $wh5 . "' THEN IFNULL(posd.Quantity,0) ELSE 0 END) AS w5qty,
                    SUM(CASE WHEN w.Code = '" . $wh5 . "' THEN (IFNULL(posd.Amount,0) * IFNULL(posd.Quantity,0)) - IFNULL(posd.DiscountAmount,0) - IFNULL(posd.DiscountPercentageAmount,0) ELSE 0 END) AS w5amt
                    FROM pospointofsale pos 
                    LEFT OUTER JOIN pospointofsaledetail posd ON pos.Oid = posd.PointOfSale
                    LEFT OUTER JOIN mstbusinesspartner bp ON pos.Customer = bp.Oid
                    LEFT OUTER JOIN mstbusinesspartnergroup bpg ON bp.BusinessPartnerGroup = bpg.Oid
                    LEFT OUTER JOIN mstcity ct ON bp.City = ct.Oid
                    LEFT OUTER JOIN mstitem i ON posd.Item = i.Oid
                    LEFT OUTER JOIN mstitemgroup ig ON i.ItemGroup = ig.Oid
                    LEFT OUTER JOIN mstwarehouse w ON pos.Warehouse = w.Oid
                    LEFT OUTER JOIN mstpaymentmethod pm ON pos.PaymentMethod = pm.Oid
                    LEFT OUTER JOIN postable pt ON pos.POSTable = pt.Oid 
                    LEFT OUTER JOIN mstemployee e ON pos.Employee = e.Oid
                    LEFT OUTER JOIN mstcurrency c ON pos.Currency = c.Oid
                    LEFT OUTER JOIN sysstatus s ON pos.Status = s.Oid
                    LEFT OUTER JOIN company co ON co.Oid = pos.Company
                    LEFT OUTER JOIN user u ON pos.User = u.Oid
                    LEFT OUTER JOIN (
                        SELECT ps.Company, SUM(psa.AmountBase) AS TotalSessionAmount
                        FROM possessionamount psa
                        LEFT OUTER JOIN possession ps ON ps.Oid = psa.POSSession
                        WHERE 8=8
                    ) psa ON psa.Company = pos.Company
                    LEFT OUTER JOIN (
                        SELECT pd.Company, SUM(IFNULL(pd.DiscountAmountBase,0) + IFNULL(pd.DiscountPercentageAmount,0)) AS TotalDiscountAmount
                        FROM pospointofsale pd
                        WHERE 1=1 AND 3=3
                    ) pd ON pd.Company = pos.Company
                    WHERE pos.GCRecord IS NULL AND s.Code IN ('paid', 'complete','posted') AND 1=1 AND 3=3 AND 4=4 AND 6=6
                    GROUP BY i.Name, i.Code, c.Code, DATE_FORMAT(pos.Date, '%Y%m%d')";
                break;
            case 19:
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
                    CONCAT(i.Name, ' - ', i.Code) AS GroupName,
                    c.Code AS CurrencyCode,
                    co.Code AS Comp,
                    DATE_FORMAT(pos.Date, '%e %b %Y') AS Date,
                    DATE_FORMAT(pos.Date, '%Y%m%d') AS DateOrder,
                    SUM(CASE WHEN w.Code = '" . $wh1 . "' THEN IFNULL(posd.Quantity,0) ELSE 0 END) AS w1qty,
                    SUM(CASE WHEN w.Code = '" . $wh1 . "' THEN (IFNULL(posd.Amount,0) * IFNULL(posd.Quantity,0)) - IFNULL(posd.DiscountAmount,0) - IFNULL(posd.DiscountPercentageAmount,0) ELSE 0 END) AS w1amt,
                    SUM(CASE WHEN w.Code = '" . $wh2 . "' THEN IFNULL(posd.Quantity,0) ELSE 0 END) AS w2qty,
                    SUM(CASE WHEN w.Code = '" . $wh2 . "' THEN (IFNULL(posd.Amount,0) * IFNULL(posd.Quantity,0)) - IFNULL(posd.DiscountAmount,0) - IFNULL(posd.DiscountPercentageAmount,0) ELSE 0 END) AS w2amt,
                    SUM(CASE WHEN w.Code = '" . $wh3 . "' THEN IFNULL(posd.Quantity,0) ELSE 0 END) AS w3qty,
                    SUM(CASE WHEN w.Code = '" . $wh3 . "' THEN (IFNULL(posd.Amount,0) * IFNULL(posd.Quantity,0)) - IFNULL(posd.DiscountAmount,0) - IFNULL(posd.DiscountPercentageAmount,0) ELSE 0 END) AS w3amt,
                    SUM(CASE WHEN w.Code = '" . $wh4 . "' THEN IFNULL(posd.Quantity,0) ELSE 0 END) AS w4qty,
                    SUM(CASE WHEN w.Code = '" . $wh4 . "' THEN (IFNULL(posd.Amount,0) * IFNULL(posd.Quantity,0)) - IFNULL(posd.DiscountAmount,0) - IFNULL(posd.DiscountPercentageAmount,0) ELSE 0 END) AS w4amt,
                    SUM(CASE WHEN w.Code = '" . $wh5 . "' THEN IFNULL(posd.Quantity,0) ELSE 0 END) AS w5qty,
                    SUM(CASE WHEN w.Code = '" . $wh5 . "' THEN (IFNULL(posd.Amount,0) * IFNULL(posd.Quantity,0)) - IFNULL(posd.DiscountAmount,0) - IFNULL(posd.DiscountPercentageAmount,0) ELSE 0 END) AS w5amt
                    FROM pospointofsale pos 
                    LEFT OUTER JOIN pospointofsaledetail posd ON pos.Oid = posd.PointOfSale
                    LEFT OUTER JOIN mstbusinesspartner bp ON pos.Customer = bp.Oid
                    LEFT OUTER JOIN mstbusinesspartnergroup bpg ON bp.BusinessPartnerGroup = bpg.Oid
                    LEFT OUTER JOIN mstcity ct ON bp.City = ct.Oid
                    LEFT OUTER JOIN mstitem i ON posd.Item = i.Oid
                    LEFT OUTER JOIN mstitemgroup ig ON i.ItemGroup = ig.Oid
                    LEFT OUTER JOIN mstwarehouse w ON pos.Warehouse = w.Oid
                    LEFT OUTER JOIN mstpaymentmethod pm ON pos.PaymentMethod = pm.Oid
                    LEFT OUTER JOIN postable pt ON pos.POSTable = pt.Oid 
                    LEFT OUTER JOIN mstemployee e ON pos.Employee = e.Oid
                    LEFT OUTER JOIN mstcurrency c ON pos.Currency = c.Oid
                    LEFT OUTER JOIN sysstatus s ON pos.Status = s.Oid
                    LEFT OUTER JOIN company co ON co.Oid = pos.Company
                    LEFT OUTER JOIN user u ON pos.User = u.Oid
                    LEFT OUTER JOIN (
                        SELECT ps.Company, SUM(psa.AmountBase) AS TotalSessionAmount
                        FROM possessionamount psa
                        LEFT OUTER JOIN possession ps ON ps.Oid = psa.POSSession
                        WHERE 8=8
                    ) psa ON psa.Company = pos.Company
                    LEFT OUTER JOIN (
                        SELECT pd.Company, SUM(IFNULL(pd.DiscountAmountBase,0) + IFNULL(pd.DiscountPercentageAmount,0)) AS TotalDiscountAmount
                        FROM pospointofsale pd
                        WHERE 1=1 AND 3=3
                    ) pd ON pd.Company = pos.Company
                    WHERE pos.GCRecord IS NULL AND s.Code IN ('paid', 'complete','posted') AND 1=1 AND 3=3 AND 4=4 AND 6=6
                    GROUP BY DATE_FORMAT(pos.Date, '%Y%m%d'), i.Name, i.Code, c.Code";
                break;
            case 20:
                return "SELECT
                    CONCAT(i.Name, ' - ', i.Code) AS GroupName,
                    pos.Code,
                    co.Code AS Comp,
                    DATE_FORMAT(pos.Date, '%Y-%m-%d') AS DateOrder,
                    DATE_FORMAT(pos.Date, '%e %b %Y') AS Date,
                    bp.Name AS BusinessPartner,
                    pm.Name AS PaymentMethod,
                    w.Name AS Warehouse,
                    pt.Name AS TableName,
                    e.Name AS EmployeeName,
                    pos.Quantity,
                    u.Name AS Cashier,
                    c.Code AS CurrencyCode,
                    ((IFNULL(pos.DiscountAmount,0) + IFNULL(pos.DiscountPercentageAmount,0)) * CASE WHEN pos.PointOfSaleType='{$return}' THEN -1 ELSE 1 END) AS DiscountAmount, 
                    (IFNULL(pos.SubtotalAmount,0) * CASE WHEN pos.PointOfSaleType='{$return}' THEN -1 ELSE 1 END) AS SubtotalAmount,
                    (IFNULL(pos.TotalAmount,0) * CASE WHEN pos.PointOfSaleType='{$return}' THEN -1 ELSE 1 END) AS TotalAmount
                    FROM pospointofsale pos
                    LEFT OUTER JOIN pospointofsaledetail posd ON pos.Oid = posd.PointOfSale
                    LEFT OUTER JOIN user u ON pos.User = u.Oid
                    LEFT OUTER JOIN postable pt ON pos.POSTable = pt.Oid 
                    LEFT OUTER JOIN mstwarehouse w ON pos.Warehouse = w.Oid
                    LEFT OUTER JOIN mstpaymentmethod pm ON pos.PaymentMethod = pm.Oid
                    LEFT OUTER JOIN mstemployee e ON pos.Employee = e.Oid
                    LEFT OUTER JOIN mstcurrency c ON pos.Currency = c.Oid
                    LEFT OUTER JOIN mstbusinesspartner bp ON pos.Customer = bp.Oid
                    LEFT OUTER JOIN mstbusinesspartnergroup bpg ON bp.BusinessPartnerGroup = bpg.Oid
                    LEFT OUTER JOIN mstitem i ON posd.Item = i.Oid
                    LEFT OUTER JOIN mstcity ct ON bp.City = ct.Oid
                    LEFT OUTER JOIN company co ON co.Oid = pos.Company
                    LEFT OUTER JOIN sysstatus s ON pos.Status = s.Oid
                    WHERE pos.GCRecord IS NULL AND s.Code IN ('paid', 'complete','posted') AND 1=1 AND 3=3 AND 4=4
                    ORDER BY i.Name, pos.Date, pos.Code";
                break;

            case 21:
                return "SELECT * FROM (SELECT
                    co.Code AS Comp,
                CONCAT(p.Name, ' - ', p.Code) AS GroupName,
                c.Code AS CurrencyCode,
                DATE_FORMAT(pos.Date, '%e %b %Y') AS Date,
                DATE_FORMAT(pos.Date, '%Y%m%d') AS DateOrder,
                COUNT(pos.Oid) AS Qty,
                SUM(IFNULL(posd.DetailSubtotal,0)) AS DetailSubtotal,
                SUM(IFNULL(posd.DetailDiscount,0)) AS DetailDiscount,
                SUM(IFNULL(posd.DetailTotal,0)) AS SubtotalAmount,
                SUM(IFNULL(pos.SubtotalAmount,0) * CASE WHEN pos.PointOfSaleType='{$return}' THEN -1 ELSE 1 END) AS SubtotalAmount1,
                (SUM(IFNULL(pos.DiscountAmount,0) + IFNULL(pos.DiscountPercentageAmount,0)) * CASE WHEN pos.PointOfSaleType='{$return}' THEN -1 ELSE 1 END) AS DiscountAmount,
                SUM(IFNULL(pos.TotalAmount,0) * CASE WHEN pos.PointOfSaleType='{$return}' THEN -1 ELSE 1 END) AS TotalAmount
                FROM pospointofsale pos 
                LEFT OUTER JOIN 
                (SELECT pos.Oid,
                SUM(IFNULL(posd.Quantity,0) * IFNULL(posd.Amount,0)) AS DetailSubtotal,
                SUM(IFNULL(posd.DiscountAmount,0) + IFNULL(posd.DiscountPercentageAmount,0)) AS DetailDiscount,
                SUM((IFNULL(posd.Quantity,0) * IFNULL(posd.Amount,0)) - IFNULL(posd.DiscountAmount,0) - IFNULL(posd.DiscountPercentageAmount,0)) AS DetailTotal
                FROM pospointofsale pos
                LEFT OUTER JOIN sysstatus s ON pos.Status = s.Oid
                LEFT OUTER JOIN pospointofsaledetail posd ON posd.PointOfSale = pos.Oid
                WHERE s.Code IN ('paid', 'complete','posted') AND 1=1 AND 3=3 GROUP BY pos.Oid) AS posd ON posd.Oid = pos.Oid
                LEFT OUTER JOIN mstbusinesspartner bp ON pos.Customer = bp.Oid
                LEFT OUTER JOIN mstbusinesspartnergroup bpg ON bp.BusinessPartnerGroup = bpg.Oid
                LEFT OUTER JOIN mstproject p ON pos.Project = p.Oid
                LEFT OUTER JOIN mstcity ct ON bp.City = ct.Oid
                LEFT OUTER JOIN mstpaymentmethod pm ON pos.PaymentMethod = pm.Oid
                LEFT OUTER JOIN mstwarehouse w ON pos.Warehouse = w.Oid
                LEFT OUTER JOIN mstemployee e ON pos.Employee = e.Oid
                LEFT OUTER JOIN postable pt ON pos.POSTable = pt.Oid 
                LEFT OUTER JOIN mstcurrency c ON pos.Currency = c.Oid
                LEFT OUTER JOIN sysstatus s ON pos.Status = s.Oid
                LEFT OUTER JOIN company co ON co.Oid = pos.Company
                LEFT OUTER JOIN user u ON pos.User = u.Oid
                WHERE pos.GCRecord IS NULL AND s.Code IN ('paid', 'complete','posted') AND 1=1 AND 4=4
                GROUP BY pm.Name, pm.Code, c.Code, DATE_FORMAT(pos.Date, '%Y%m%d')
                UNION ALL
                SELECT co.Code AS Comp,'',cur.Code,psa.Date, psa.Date,0,0,0,0,psa.AmountBase,0, psa.AmountBase
                FROM possessionamount psa
                LEFT OUTER JOIN mstpaymentmethod pm ON pm.Oid = psa.PaymentMethod
                LEFT OUTER JOIN possession ps ON ps.Oid = psa.POSSession 
                LEFT OUTER JOIN user u ON u.Oid = ps.User
                LEFT OUTER JOIN mstwarehouse w ON w.Oid = ps.Warehouse
                LEFT OUTER JOIN company co ON co.Oid = psa.Company
                LEFT OUTER JOIN mstcurrency cur ON cur.Oid = psa.Currency
                WHERE psa.GCRecord IS NULL AND 8=8
                ) AS Result";
            case 22:
                return "SELECT * FROM (SELECT
                    co.Code AS Comp,
                CONCAT(e.Name, ' - ', e.Code) AS GroupName,
                c.Code AS CurrencyCode,
                DATE_FORMAT(pos.Date, '%e %b %Y') AS Date,
                DATE_FORMAT(pos.Date, '%Y%m%d') AS DateOrder,
                COUNT(pos.Oid) AS Qty,
                SUM(IFNULL(posd.DetailSubtotal,0)) AS DetailSubtotal,
                SUM(IFNULL(posd.DetailDiscount,0)) AS DetailDiscount,
                SUM(IFNULL(posd.DetailTotal,0)) AS SubtotalAmount,
                SUM(IFNULL(pos.SubtotalAmount,0) * CASE WHEN pos.PointOfSaleType='{$return}' THEN -1 ELSE 1 END) AS SubtotalAmount1,
                (SUM(IFNULL(pos.DiscountAmount,0) + IFNULL(pos.DiscountPercentageAmount,0)) * CASE WHEN pos.PointOfSaleType='{$return}' THEN -1 ELSE 1 END) AS DiscountAmount,
                SUM(IFNULL(pos.TotalAmount,0) * CASE WHEN pos.PointOfSaleType='{$return}' THEN -1 ELSE 1 END) AS TotalAmount
                FROM pospointofsale pos 
                LEFT OUTER JOIN 
                (SELECT pos.Oid,
                SUM(IFNULL(posd.Quantity,0) * IFNULL(posd.Amount,0)) AS DetailSubtotal,
                SUM(IFNULL(posd.DiscountAmount,0) + IFNULL(posd.DiscountPercentageAmount,0)) AS DetailDiscount,
                SUM((IFNULL(posd.Quantity,0) * IFNULL(posd.Amount,0)) - IFNULL(posd.DiscountAmount,0) - IFNULL(posd.DiscountPercentageAmount,0)) AS DetailTotal
                FROM pospointofsale pos
                LEFT OUTER JOIN sysstatus s ON pos.Status = s.Oid
                LEFT OUTER JOIN pospointofsaledetail posd ON posd.PointOfSale = pos.Oid
                WHERE s.Code IN ('paid', 'complete','posted') AND 1=1 AND 3=3 GROUP BY pos.Oid) AS posd ON posd.Oid = pos.Oid
                LEFT OUTER JOIN mstbusinesspartner bp ON pos.Customer = bp.Oid
                LEFT OUTER JOIN mstbusinesspartnergroup bpg ON bp.BusinessPartnerGroup = bpg.Oid
                LEFT OUTER JOIN mstcity ct ON bp.City = ct.Oid
                LEFT OUTER JOIN mstpaymentmethod pm ON pos.PaymentMethod = pm.Oid
                LEFT OUTER JOIN mstwarehouse w ON pos.Warehouse = w.Oid
                LEFT OUTER JOIN mstemployee e ON pos.Employee2 = e.Oid
                LEFT OUTER JOIN postable pt ON pos.POSTable = pt.Oid 
                LEFT OUTER JOIN mstcurrency c ON pos.Currency = c.Oid
                LEFT OUTER JOIN sysstatus s ON pos.Status = s.Oid
                LEFT OUTER JOIN company co ON co.Oid = pos.Company
                LEFT OUTER JOIN user u ON pos.User = u.Oid
                WHERE pos.GCRecord IS NULL AND s.Code IN ('paid', 'complete','posted') AND 1=1 AND 4=4
                GROUP BY pm.Name, pm.Code, c.Code, DATE_FORMAT(pos.Date, '%Y%m%d')
                UNION ALL
                SELECT co.Code AS Comp,'',cur.Code,psa.Date, psa.Date,0,0,0,0,psa.AmountBase,0, psa.AmountBase
                FROM possessionamount psa
                LEFT OUTER JOIN mstpaymentmethod pm ON pm.Oid = psa.PaymentMethod
                LEFT OUTER JOIN possession ps ON ps.Oid = psa.POSSession 
                LEFT OUTER JOIN user u ON u.Oid = ps.User
                LEFT OUTER JOIN mstwarehouse w ON w.Oid = ps.Warehouse
                LEFT OUTER JOIN company co ON co.Oid = psa.Company
                LEFT OUTER JOIN mstcurrency cur ON cur.Oid = psa.Currency
                WHERE psa.GCRecord IS NULL AND 8=8
                ) AS Result";
            case 23:
                return "SELECT 
                CONCAT(w.Name, ' - ', w.Code) AS GroupName,
                co.Code AS Comp,
                'Item' AS Judul,
                i.Name AS Item,
                sum(case when DATE_FORMAT(pos.Date,'%d')=1 then posd.Quantity else 0 END) AS d1,
                sum(case when DATE_FORMAT(pos.Date,'%d')=2 then posd.Quantity else 0 END) AS d2,
                sum(case when DATE_FORMAT(pos.Date,'%d')=3 then posd.Quantity else 0 END) AS d3,
                sum(case when DATE_FORMAT(pos.Date,'%d')=4 then posd.Quantity else 0 END) AS d4,
                sum(case when DATE_FORMAT(pos.Date,'%d')=5 then posd.Quantity else 0 END) AS d5,
                sum(case when DATE_FORMAT(pos.Date,'%d')=6 then posd.Quantity else 0 END) AS d6,
                sum(case when DATE_FORMAT(pos.Date,'%d')=7 then posd.Quantity else 0 END) AS d7,
                sum(case when DATE_FORMAT(pos.Date,'%d')=8 then posd.Quantity else 0 END) AS d8,
                sum(case when DATE_FORMAT(pos.Date,'%d')=9 then posd.Quantity else 0 END) AS d9,
                sum(case when DATE_FORMAT(pos.Date,'%d')=10 then posd.Quantity else 0 END) AS d10,
                sum(case when DATE_FORMAT(pos.Date,'%d')=11 then posd.Quantity else 0 END) AS d11,
                sum(case when DATE_FORMAT(pos.Date,'%d')=12 then posd.Quantity else 0 END) AS d12,
                sum(case when DATE_FORMAT(pos.Date,'%d')=13 then posd.Quantity else 0 END) AS d13,
                sum(case when DATE_FORMAT(pos.Date,'%d')=14 then posd.Quantity else 0 END) AS d14,
                sum(case when DATE_FORMAT(pos.Date,'%d')=15 then posd.Quantity else 0 END) AS d15,
                sum(case when DATE_FORMAT(pos.Date,'%d')=16 then posd.Quantity else 0 END) AS d16,
                sum(case when DATE_FORMAT(pos.Date,'%d')=17 then posd.Quantity else 0 END) AS d17,
                sum(case when DATE_FORMAT(pos.Date,'%d')=18 then posd.Quantity else 0 END) AS d18,
                sum(case when DATE_FORMAT(pos.Date,'%d')=19 then posd.Quantity else 0 END) AS d19,
                sum(case when DATE_FORMAT(pos.Date,'%d')=20 then posd.Quantity else 0 END) AS d20,
                sum(case when DATE_FORMAT(pos.Date,'%d')=21 then posd.Quantity else 0 END) AS d21,
                sum(case when DATE_FORMAT(pos.Date,'%d')=22 then posd.Quantity else 0 END) AS d22,
                sum(case when DATE_FORMAT(pos.Date,'%d')=23 then posd.Quantity else 0 END) AS d23,
                sum(case when DATE_FORMAT(pos.Date,'%d')=24 then posd.Quantity else 0 END) AS d24,
                sum(case when DATE_FORMAT(pos.Date,'%d')=25 then posd.Quantity else 0 END) AS d25,
                sum(case when DATE_FORMAT(pos.Date,'%d')=26 then posd.Quantity else 0 END) AS d26,
                sum(case when DATE_FORMAT(pos.Date,'%d')=27 then posd.Quantity else 0 END) AS d27,
                sum(case when DATE_FORMAT(pos.Date,'%d')=28 then posd.Quantity else 0 END) AS d28,
                sum(case when DATE_FORMAT(pos.Date,'%d')=29 then posd.Quantity else 0 END) AS d29,
                sum(case when DATE_FORMAT(pos.Date,'%d')=30 then posd.Quantity else 0 END) AS d30,
                sum(case when DATE_FORMAT(pos.Date,'%d')=31 then posd.Quantity else 0 END) AS d31
                FROM pospointofsale  pos
                LEFT OUTER JOIN pospointofsaledetail posd ON pos.Oid = posd.PointOfSale
                LEFT OUTER JOIN sysstatus s ON pos.Status = s.Oid
                LEFT OUTER JOIN mstitem i ON posd.Item = i.Oid
                LEFT OUTER JOIN mstitemgroup ig ON ig.Oid = i.ItemGroup
                LEFT OUTER JOIN company co ON co.Oid = pos.Company
                LEFT OUTER JOIN mstwarehouse w On w.Oid = pos.Warehouse
                WHERE pos.GCRecord IS NULL AND s.Code IN ('paid', 'complete','posted') AND i.Name IS NOT NULL AND 2=2 AND 4=4 
                GROUP BY w.Name, w.Oid, i.Name, i.Oid";

            case 24:
                return "SELECT 
                CONCAT(w.Name, ' - ', w.Code) AS GroupName,
                co.Code AS Comp,
                ig.Name AS Item,
                'Item' AS Judul,
                sum(case when DATE_FORMAT(pos.Date,'%d')=1 then posd.Quantity else 0 END) AS d1,
                sum(case when DATE_FORMAT(pos.Date,'%d')=2 then posd.Quantity else 0 END) AS d2,
                sum(case when DATE_FORMAT(pos.Date,'%d')=3 then posd.Quantity else 0 END) AS d3,
                sum(case when DATE_FORMAT(pos.Date,'%d')=4 then posd.Quantity else 0 END) AS d4,
                sum(case when DATE_FORMAT(pos.Date,'%d')=5 then posd.Quantity else 0 END) AS d5,
                sum(case when DATE_FORMAT(pos.Date,'%d')=6 then posd.Quantity else 0 END) AS d6,
                sum(case when DATE_FORMAT(pos.Date,'%d')=7 then posd.Quantity else 0 END) AS d7,
                sum(case when DATE_FORMAT(pos.Date,'%d')=8 then posd.Quantity else 0 END) AS d8,
                sum(case when DATE_FORMAT(pos.Date,'%d')=9 then posd.Quantity else 0 END) AS d9,
                sum(case when DATE_FORMAT(pos.Date,'%d')=10 then posd.Quantity else 0 END) AS d10,
                sum(case when DATE_FORMAT(pos.Date,'%d')=11 then posd.Quantity else 0 END) AS d11,
                sum(case when DATE_FORMAT(pos.Date,'%d')=12 then posd.Quantity else 0 END) AS d12,
                sum(case when DATE_FORMAT(pos.Date,'%d')=13 then posd.Quantity else 0 END) AS d13,
                sum(case when DATE_FORMAT(pos.Date,'%d')=14 then posd.Quantity else 0 END) AS d14,
                sum(case when DATE_FORMAT(pos.Date,'%d')=15 then posd.Quantity else 0 END) AS d15,
                sum(case when DATE_FORMAT(pos.Date,'%d')=16 then posd.Quantity else 0 END) AS d16,
                sum(case when DATE_FORMAT(pos.Date,'%d')=17 then posd.Quantity else 0 END) AS d17,
                sum(case when DATE_FORMAT(pos.Date,'%d')=18 then posd.Quantity else 0 END) AS d18,
                sum(case when DATE_FORMAT(pos.Date,'%d')=19 then posd.Quantity else 0 END) AS d19,
                sum(case when DATE_FORMAT(pos.Date,'%d')=20 then posd.Quantity else 0 END) AS d20,
                sum(case when DATE_FORMAT(pos.Date,'%d')=21 then posd.Quantity else 0 END) AS d21,
                sum(case when DATE_FORMAT(pos.Date,'%d')=22 then posd.Quantity else 0 END) AS d22,
                sum(case when DATE_FORMAT(pos.Date,'%d')=23 then posd.Quantity else 0 END) AS d23,
                sum(case when DATE_FORMAT(pos.Date,'%d')=24 then posd.Quantity else 0 END) AS d24,
                sum(case when DATE_FORMAT(pos.Date,'%d')=25 then posd.Quantity else 0 END) AS d25,
                sum(case when DATE_FORMAT(pos.Date,'%d')=26 then posd.Quantity else 0 END) AS d26,
                sum(case when DATE_FORMAT(pos.Date,'%d')=27 then posd.Quantity else 0 END) AS d27,
                sum(case when DATE_FORMAT(pos.Date,'%d')=28 then posd.Quantity else 0 END) AS d28,
                sum(case when DATE_FORMAT(pos.Date,'%d')=29 then posd.Quantity else 0 END) AS d29,
                sum(case when DATE_FORMAT(pos.Date,'%d')=30 then posd.Quantity else 0 END) AS d30,
                sum(case when DATE_FORMAT(pos.Date,'%d')=31 then posd.Quantity else 0 END) AS d31
                FROM pospointofsale  pos
                LEFT OUTER JOIN pospointofsaledetail posd ON pos.Oid = posd.PointOfSale
                LEFT OUTER JOIN sysstatus s ON pos.Status = s.Oid
                LEFT OUTER JOIN mstitem i ON posd.Item = i.Oid
                LEFT OUTER JOIN mstitemgroup ig ON ig.Oid = i.ItemGroup
                LEFT OUTER JOIN company co ON co.Oid = pos.Company
                LEFT OUTER JOIN mstwarehouse w On w.Oid = pos.Warehouse
                WHERE pos.GCRecord IS NULL AND s.Code IN ('paid', 'complete','posted') AND ig.Name IS NOT NULL AND 2=2 AND 4=4 
                GROUP BY w.Name, w.Oid, ig.Name, ig.Oid";
            break;
            case 25:
                return "SELECT
                co.Code AS Comp,
                CONCAT(ig.Name, ' - ', ig.Code) AS GroupName,
                pos.Code,
                DATE_FORMAT(pos.Date, '%Y-%m-%d') AS DateOrder,
                pos.Date AS Date,
                CONCAT(i.Name, ' - ',i.Code) AS Item,
                bp.Name AS BusinessPartner,
                pm.Name AS PaymentMethod,
                w.Name AS Warehouse,
                pt.Name AS TableName,
                e.Name AS EmployeeName,
                posd.Quantity,
                u.Name AS Cashier,
                c.Code AS CurrencyCode, 
                posd.Price AS PriceBefore,
                posd.Price - (posd.Price * posd.Discount) AS PriceAfter,
                posd.Quantity * (posd.Price - (posd.Price * posd.Discount)) AS Total
                
                FROM pospointofsale pos
                LEFT OUTER JOIN 
                (SELECT pos.Oid,
                (IFNULL(posd.Amount,0) * CASE WHEN pos.PointOfSaleType='{$return}' THEN -1 ELSE 1 END) AS Price,
                IFNULL(pos.DiscountPercentage,0) AS Discount
                FROM pospointofsale pos
                LEFT OUTER JOIN sysstatus s ON pos.Status = s.Oid
                LEFT OUTER JOIN pospointofsaledetail posd ON posd.PointOfSale = pos.Oid
                WHERE s.Code IN ('paid', 'complete','posted') AND 1=1 AND 3=3 GROUP BY pos.Oid) AS posd ON posd.Oid = pos.Oid
                LEFT OUTER JOIN pospointofsaledetail posd ON pos.Oid = posd.PointOfSale
                LEFT OUTER JOIN user u ON pos.User = u.Oid
                LEFT OUTER JOIN postable pt ON pos.POSTable = pt.Oid 
                LEFT OUTER JOIN mstwarehouse w ON pos.Warehouse = w.Oid
                LEFT OUTER JOIN mstpaymentmethod pm ON pos.PaymentMethod = pm.Oid
                LEFT OUTER JOIN mstemployee e ON pos.Employee = e.Oid
                LEFT OUTER JOIN mstcurrency c ON pos.Currency = c.Oid
                LEFT OUTER JOIN mstbusinesspartner bp ON pos.Customer = bp.Oid
                LEFT OUTER JOIN mstbusinesspartnergroup bpg ON bp.BusinessPartnerGroup = bpg.Oid
                LEFT OUTER JOIN mstitem i ON posd.Item = i.Oid
                LEFT OUTER JOIN mstitemgroup ig ON i.ItemGroup = ig.Oid
                LEFT OUTER JOIN mstcity ct ON bp.City = ct.Oid
                LEFT OUTER JOIN company co ON co.Oid = pos.Company
                LEFT OUTER JOIN sysstatus s ON pos.Status = s.Oid
                WHERE pos.GCRecord IS NULL AND s.Code IN ('paid', 'complete','posted') AND 1=1 AND 3=3 AND 4=4
                ORDER BY ig.Name, i.Name, i.Code, pos.Date, pos.Code
                ";
            break;

        }
        return "";
    }
}
