<?php

namespace App\AdminApi\Report\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

use App\Laravel\Http\Controllers\Controller;
use App\Core\Master\Entities\ItemGroup;
use App\Core\Master\Entities\ItemGroupUser;
use App\Core\Master\Entities\Warehouse;
use App\Core\Master\Entities\Item;
use App\AdminApi\Report\Services\ReportService;
use App\Core\Base\Services\HttpService;
use App\Core\Master\Entities\Company;

use Barryvdh\DomPDF\Facade as PDF;
use Barryvdh\Snappy\Facades\SnappyPdf;
use Carbon\Carbon;

class ReportStockController extends Controller
{
    protected $reportService;
    protected $reportName;
    private $httpService;

    public function __construct(
        ReportService $reportService,
        HttpService $httpService
    ) {
        $this->reportName = 'stock';
        $this->reportService = $reportService;
        $this->httpService = $httpService;
        $this->httpService->baseUrl('http://api1.ezbooking.co:888')->json();
    }

    public function config(Request $request)
    {
        $return = $this->httpService->get('/portal/api/development/table/phpreport?code=ReportStock');
        // return $return;
        return response()->json(
            $return,
            Response::HTTP_OK
        );
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
        $criteria = "";
        $criteria2 = "";
        $criteria3 = "";
        $criteria4 = "";
        $filter = "";

        $datefrom = Carbon::parse($request->input('DateStart'));
        $dateto = Carbon::parse($request->input('DateUntil'));

        $criteria = $criteria . " AND DATE_FORMAT(st.Date, '%Y-%m-%d') >= '" . $datefrom->format('Y-m-d') . "'";
        $filter = $filter . "Date From = '" . strtoupper($datefrom->format('Y-m-d')) . "'; ";
        $criteria = $criteria . " AND DATE_FORMAT(st.Date, '%Y-%m-%d') <= '" . $dateto->format('Y-m-d') . "'";
        $filter = $filter . "Date To = '" . strtoupper($dateto->format('Y-m-d')) . "'; ";

        $criteria2 = $criteria2 . " AND st.Date < '" . $datefrom->format('Y-m-d') . "'";
        $criteria3 = $criteria3 . " AND beg.Date < '" . $datefrom->format('Y-m-d') . "'";

        $criteria4 = $criteria4 . reportQueryCompany('trdtransactionstock');
        if ($request->input('p_Company')) {
            $val = Company::findOrFail($request->input('p_Company'));
            $criteria4 = $criteria4 . " AND co.Oid = '" . $val->Oid . "'";
            $filter = $filter . " AND Company = '" . strtoupper($val->Name) . "'; ";
        }

        if ($request->input('p_Item_ItemGroup')) {
            $val = ItemGroup::find($request->input('p_Item_ItemGroup'));
            $criteria3 = $criteria3 . " AND i.ItemGroup = '" . $val->Oid . "'";
            $criteria4 = $criteria4 . " AND i.ItemGroup = '" . $val->Oid . "'";
            $filter = $filter . "AND Item Group = '" . strtoupper($val->Name) . "'; ";
        }
        if ($request->input('p_Warehouse')) {
            $val = Warehouse::findOrFail($request->input('p_Warehouse'));
            $criteria = $criteria . " AND st.Warehouse = '" . $val->Oid . "'";
            $criteria2 = $criteria2 . " AND st.Warehouse = '" . $val->Oid . "'";
            $filter = $filter . "AND Warehouse = '" . strtoupper($val->Name) . "'; ";
        }
        if ($request->input('p_Item')) {
            $val = Item::findOrFail($request->input('p_Item'));
            $criteria = $criteria . " AND st.Item = '" . $val->Oid . "'";
            $criteria2 = $criteria2 . " AND st.Item = '" . $val->Oid . "'";
            $filter = $filter . "AND Item = '" . strtoupper($val->Name) . "'; ";
        }
        if ($filter) $filter = substr($filter, 0);
        if ($criteria) $query = str_replace(" AND 1=1", $criteria, $query);
        if ($criteria2) $query = str_replace(" AND 2=2", $criteria2, $query);
        if ($criteria3) $query = str_replace(" AND 3=3", $criteria3, $query);
        if ($criteria4) $query = str_replace(" AND 4=4", $criteria4, $query);

        $data = DB::select($query);

        switch ($reportname) {
            case 'stock1':
                $reporttitle = "Report Stock Summary Warehouse";
                $pdf = SnappyPdf::loadView('AdminApi\Report::pdf.' . $reportname, compact('data', 'user', 'reportname', 'filter', 'reporttitle'));
                break;
            case 'stock2':
                $reporttitle = "Report Stock Detail Order By Item";
                $pdf = SnappyPdf::loadView('AdminApi\Report::pdf.' . $reportname, compact('data', 'user', 'reportname', 'filter', 'reporttitle'));
                break;
            case 'stock3':
                $reporttitle = "Report Stock Summary Item";
                $pdf = SnappyPdf::loadView('AdminApi\Report::pdf.' . $reportname, compact('data', 'user', 'reportname', 'filter', 'reporttitle'));
                break;
            case 'stock4':
                $reporttitle = "Report Stock Summary Item Group";
                $pdf = SnappyPdf::loadView('AdminApi\Report::pdf.stock3', compact('data', 'user', 'reportname', 'filter', 'reporttitle'));
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
            ->setOption('margin-right', 10)
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
            case 'stock0':
                return "SELECT
                CONCAT(w.Name,' - ',w.Code) AS Warehouse,
                i.Code,
                i.Name,
                SUM(IFNULL(st.Quantity,0)) AS Quantity,
                SUM(IFNULL(st.Cost,0)) AS Amount,
                CONCAT(ig.Name,' - ',ig.Code) AS GroupName
              FROM (
                SELECT st.Item, st.Warehouse, 'Start' AS Type, 
                SUM(IFNULL(st.StockQuantity,0)) AS Quantity, SUM(IFNULL(st.StockQuantity,0) * IFNULL(st.StockCost,0)) AS Cost
                FROM trdtransactionstock st
                LEFT OUTER JOIN sysjournaltype jt ON st.JournalType = jt.Oid
                LEFT OUTER JOIN sysstatus s ON s.Oid = st.Status
                WHERE jt.Code !='Auto' AND s.Code = 'posted' AND 2=2
                GROUP BY st.Warehouse, st.Item
                UNION ALL
                SELECT st.Item, st.Warehouse, 'Stock' AS Type,
                SUM(IFNULL(st.StockQuantity,0)) AS Quantity, SUM(IFNULL(st.StockQuantity,0) * IFNULL(st.StockCost,0)) AS Cost
                FROM trdtransactionstock st
                LEFT OUTER JOIN sysjournaltype jt ON st.JournalType = jt.Oid
                LEFT OUTER JOIN sysstatus s ON s.Oid = st.Status
                WHERE st.StockQuantity > 0 AND s.Code = 'posted' AND jt.Code !='Auto' AND 1=1
                GROUP BY st.Warehouse, st.Item
                ) st
              LEFT OUTER JOIN mstitem i ON i.Oid = st.Item
              LEFT OUTER JOIN mstwarehouse w ON st.Warehouse = w.Oid
              LEFT OUTER JOIN mstitemgroup ig ON i.ItemGroup = ig.Oid
              LEFT OUTER JOIN sysjournaltype jt ON jt.Code != 'Auto'
                GROUP BY w.Code,w.Name, i.Code, i.Name, ig.Name, ig.Code";
                break;
            case 'stock1':
                return "SELECT co.Code AS Comp, i.Item, i.Warehouse, 
                i.WarehouseName,
                  i.ItemName,
                  SUM(IFNULL(beg.StockQuantity,0)) AS BegQty,
                  SUM(IFNULL(st.StockQuantity,0)) AS Qty,
                  SUM(IFNULL(st.StockIn,0)) AS QtyIn,
                  SUM(IFNULL(st.StockOut,0)) AS QtyOut,
                  SUM(IFNULL(st.StockOther,0)) AS QtyOther,
                  CONCAT(ig.Name,' - ',ig.Code) AS GroupName
                FROM 
                (
                    SELECT i.Company AS Company, i.Oid AS Item, w.Oid AS Warehouse, CONCAT(i.Name,' - ',i.Code) AS ItemName, CONCAT(w.Name,' - ',w.Code) AS WarehouseName, i.ItemGroup
                    FROM mstitem i, mstwarehouse w
                ) AS i
                LEFT OUTER JOIN company co ON co.Oid = i.Company
                LEFT OUTER JOIN mstitemgroup ig ON ig.Oid = i.ItemGroup
                LEFT OUTER JOIN (
                    SELECT st.Item, st.Warehouse, SUM(IFNULL(st.StockQuantity,0)) AS StockQuantity
                    FROM trdtransactionstock st
                    LEFT OUTER JOIN sysjournaltype jt ON jt.Oid = st.JournalType
                    LEFT OUTER JOIN sysstatus s ON s.Oid = st.Status
                    WHERE jt.Code != 'Auto' AND s.Code = 'posted' AND 2=2
                    GROUP BY st.Warehouse, st.Item
                ) beg ON beg.Item = i.Item AND beg.Warehouse = i.Warehouse
                LEFT OUTER JOIN (
                    SELECT st.Item, st.Warehouse, SUM(IFNULL(st.StockQuantity,0)) AS StockQuantity, SUM(IFNULL(st.Quantity,0)) AS Quantity,
                      SUM(CASE WHEN jt.Code IN ('PINV') THEN IFNULL(st.StockQuantity,0) ELSE 0 END) AS StockIn,
                      SUM(CASE WHEN jt.Code IN ('SINV', 'POS') THEN IFNULL(st.StockQuantity,0) ELSE 0 END) AS StockOut,
                      SUM(CASE WHEN jt.Code IN ('STIN','STOUT','Stock') THEN IFNULL(st.StockQuantity,0) ELSE 0 END) AS StockOther
                    FROM trdtransactionstock st
                    LEFT OUTER JOIN sysjournaltype jt ON jt.Oid = st.JournalType
                    LEFT OUTER JOIN sysstatus s ON s.Oid = st.Status
                    WHERE jt.Code != 'Auto' AND s.Code = 'posted' AND 1=1
                    GROUP BY st.Warehouse, st.Item
                ) st ON st.Item = i.Item AND st.Warehouse = i.Warehouse
                WHERE i.Item IS NOT NULL AND 4=4
                GROUP BY i.WarehouseName, ig.Name, i.ItemName
                HAVING SUM(IFNULL(beg.StockQuantity,0)) != 0 OR SUM(IFNULL(st.Quantity,0)) != 0";
                break;
            case 'stock2':
                return "SELECT Comp, i.Code AS ItemCode, i.Name, st.Date, st.Code, i.WarehouseName AS Warehouse, st.Type AS Type, st.Quantity, st.Amount
                FROM (
                    SELECT co.Code AS Comp, i.Oid AS Item, w.Oid AS Warehouse, CONCAT(i.Name,' - ',i.Code) AS ItemName, CONCAT(w.Name,' - ',w.Code) AS WarehouseName, i.ItemGroup,
                    i.Code AS Code, i.Name AS Name
                    FROM mstitem i, mstwarehouse w, company co
                ) AS i
                LEFT OUTER JOIN mstitemgroup ig ON ig.Oid = i.ItemGroup
                LEFT OUTER JOIN (
                    SELECT st.Item, st.Warehouse, st.Date, st.Code, jt.Name AS Type, st.StockQuantity AS Quantity, st.StockCost AS Amount
                    FROM trdtransactionstock st LEFT OUTER JOIN sysjournaltype jt ON st.JournalType = jt.Oid LEFT OUTER JOIN sysstatus s ON s.Oid = st.Status
                    WHERE st.GCRecord IS NULL AND s.Code = 'posted' AND jt.Code != 'Auto' AND 1=1 
                    UNION ALL
                    SELECT st.Item, st.Warehouse, st.Date, st.Code, jt.Name AS Type, SUM(st.StockQuantity) AS Quantity, SUM(st.StockCost) / SUM(st.StockQuantity) AS Amount
                    FROM trdtransactionstock st LEFT OUTER JOIN sysjournaltype jt ON st.JournalType = jt.Oid LEFT OUTER JOIN sysstatus s ON s.Oid = st.Status
                    WHERE st.GCRecord IS NULL AND s.Code = 'posted' AND jt.Code = 'Auto' AND 2=2
                    GROUP BY st.Item, st.Warehouse
                ) st ON st.Item = i.Item AND st.Warehouse = i.Warehouse
                WHERE st.Quantity IS NOT NULL AND 4=4
                ORDER BY st.Date, st.Code";
                break;
            case 'stock3':
                return "SELECT Comp, i.Item, i.Warehouse, 
                i.WarehouseName,
                  i.ItemName,
                  SUM(IFNULL(beg.StockQuantity,0)) AS BegQty,
                  SUM(IFNULL(st.StockQuantity,0)) AS Qty,
                  SUM(IFNULL(st.StockIn,0)) AS QtyIn,
                  SUM(IFNULL(st.StockOut,0)) AS QtyOut,
                  SUM(IFNULL(st.StockOther,0)) AS QtyOther,
                  CONCAT(ig.Name,' - ',ig.Code) AS GroupName
                FROM 
                (
                    SELECT co.Code AS Comp, i.Oid AS Item, w.Oid AS Warehouse, CONCAT(i.Name,' - ',i.Code) AS ItemName, CONCAT(w.Name,' - ',w.Code) AS WarehouseName, i.ItemGroup
                    FROM mstitem i, mstwarehouse w, company co
                ) AS i
                LEFT OUTER JOIN mstitemgroup ig ON ig.Oid = i.ItemGroup
                LEFT OUTER JOIN (
                    SELECT st.Item, st.Warehouse, SUM(IFNULL(st.StockQuantity,0)) AS StockQuantity
                    FROM trdtransactionstock st
                    LEFT OUTER JOIN sysjournaltype jt ON jt.Oid = st.JournalType
                    LEFT OUTER JOIN sysstatus s ON s.Oid = st.Status
                    WHERE jt.Code = 'Auto' AND s.Code = 'posted' AND 2=2
                    GROUP BY st.Warehouse, st.Item
                ) beg ON beg.Item = i.Item AND beg.Warehouse = i.Warehouse
                LEFT OUTER JOIN (
                    SELECT st.Item, st.Warehouse, SUM(IFNULL(st.StockQuantity,0)) AS StockQuantity, SUM(IFNULL(st.Quantity,0)) AS Quantity,
                      SUM(CASE WHEN jt.Code IN ('PINV') THEN IFNULL(st.StockQuantity,0) ELSE 0 END) AS StockIn,
                      SUM(CASE WHEN jt.Code IN ('SINV', 'POS') THEN IFNULL(st.StockQuantity,0) ELSE 0 END) AS StockOut,
                      SUM(CASE WHEN jt.Code IN ('STIN','STOUT','Stock') THEN IFNULL(st.StockQuantity,0) ELSE 0 END) AS StockOther
                    FROM trdtransactionstock st
                    LEFT OUTER JOIN sysjournaltype jt ON jt.Oid = st.JournalType
                    LEFT OUTER JOIN sysstatus s ON s.Oid = st.Status
                    WHERE jt.Code != 'Auto' AND s.Code = 'posted' AND 1=1
                    GROUP BY st.Warehouse, st.Item
                ) st ON st.Item = i.Item AND st.Warehouse = i.Warehouse
                WHERE i.Item IS NOT NULL AND 4=4
                GROUP BY  i.ItemName, i.WarehouseName, ig.Name
                HAVING SUM(IFNULL(beg.StockQuantity,0)) != 0 OR SUM(IFNULL(st.Quantity,0)) != 0";
                break;
            case "stock4":
                return "SELECT Comp, i.Item, i.Warehouse, 
                i.WarehouseName,
                  CONCAT(ig.Name,' - ',ig.Code) AS ItemName,
                  SUM(IFNULL(beg.StockQuantity,0)) AS BegQty,
                  SUM(IFNULL(st.StockQuantity,0)) AS Qty,
                  SUM(IFNULL(st.StockIn,0)) AS QtyIn,
                  SUM(IFNULL(st.StockOut,0)) AS QtyOut,
                  SUM(IFNULL(st.StockOther,0)) AS QtyOther
                FROM 
                (
                    SELECT co.Code AS Comp, i.Oid AS Item, w.Oid AS Warehouse, CONCAT(i.Name,' - ',i.Code) AS ItemName, CONCAT(w.Name,' - ',w.Code) AS WarehouseName, i.ItemGroup
                    FROM mstitem i, mstwarehouse w, company co
                ) AS i
                LEFT OUTER JOIN mstitemgroup ig ON ig.Oid = i.ItemGroup
                LEFT OUTER JOIN (
                    SELECT st.Item, st.Warehouse, SUM(IFNULL(st.StockQuantity,0)) AS StockQuantity
                    FROM trdtransactionstock st
                    LEFT OUTER JOIN sysjournaltype jt ON jt.Oid = st.JournalType
                    LEFT OUTER JOIN sysstatus s ON s.Oid = st.Status
                    WHERE jt.Code = 'Auto' AND s.Code = 'posted' AND 2=2
                    GROUP BY st.Warehouse, st.Item
                ) beg ON beg.Item = i.Item AND beg.Warehouse = i.Warehouse
                LEFT OUTER JOIN (
                    SELECT st.Item, st.Warehouse, SUM(IFNULL(st.StockQuantity,0)) AS StockQuantity, SUM(IFNULL(st.Quantity,0)) AS Quantity,
                      SUM(CASE WHEN jt.Code IN ('PINV') THEN IFNULL(st.StockQuantity,0) ELSE 0 END) AS StockIn,
                      SUM(CASE WHEN jt.Code IN ('SINV', 'POS') THEN IFNULL(st.StockQuantity,0) ELSE 0 END) AS StockOut,
                      SUM(CASE WHEN jt.Code IN ('STIN','STOUT','Stock') THEN IFNULL(st.StockQuantity,0) ELSE 0 END) AS StockOther
                    FROM trdtransactionstock st
                    LEFT OUTER JOIN sysjournaltype jt ON jt.Oid = st.JournalType
                    LEFT OUTER JOIN sysstatus s ON s.Oid = st.Status
                    WHERE jt.Code != 'Auto' AND s.Code = 'posted' AND 1=1
                    GROUP BY st.Warehouse, st.Item
                ) st ON st.Item = i.Item AND st.Warehouse = i.Warehouse
                WHERE i.Item IS NOT NULL AND 4=4
                GROUP BY  i.WarehouseName, ig.Name
                HAVING SUM(IFNULL(beg.StockQuantity,0)) != 0 OR SUM(IFNULL(st.Quantity,0)) != 0";
            default:
                return "";
        }
        return "";
    }
}
