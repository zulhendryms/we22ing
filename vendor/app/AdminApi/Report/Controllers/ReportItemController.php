<?php

namespace App\AdminApi\Report\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

use App\Laravel\Http\Controllers\Controller;
use App\Core\Master\Entities\ItemGroup;
use App\Core\Master\Entities\ItemAccountGroup;
use App\AdminApi\Report\Services\ReportService;
use App\Core\Master\Entities\Company;

use Barryvdh\DomPDF\Facade as PDF;
use Barryvdh\Snappy\Facades\SnappyPdf;
use Carbon\Carbon;

class ReportItemController extends Controller
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
        $this->reportName = 'item';
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
      $criteria = ""; $filter="";
      $criteria2 = ""; $filter="";

      
      $datefrom = Carbon::parse($request->input('DateStart'));
      $dateto = Carbon::parse($request->input('DateUntil'));
      $criteria2 = $criteria2 . " AND DATE_FORMAT(i.UpdatedAt, '%Y-%m-%d') >= '" . $datefrom->format('Y-m-d') . "'";
      $filter = $filter . "Date From = '" . strtoupper($datefrom->format('d-m-Y')) . "'; ";

      $criteria = $criteria . reportQueryCompany('mstitem');
      if ($request->input('p_Company')) {
          $val = Company::findOrFail($request->input('p_Company'));
          $criteria = $criteria . " AND co.Oid = '" . $val->Oid . "'";
          $filter = $filter . " AND Company = '" . strtoupper($val->Name) . "'";
      }
      if ($request->input('p_ItemGroup')) {
          $val = ItemGroup::findOrFail($request->input('p_ItemGroup'));
          $criteria = $criteria." AND i.ItemGroup = '".$val->Oid."'";
          $filter = $filter."ItemGroup = '".strtoupper($val->Name)."'; ";
      }
      if ($request->input('p_ItemAccountGroup')) {
          $val = ItemAccountGroup::findOrFail($request->input('p_ItemAccountGroup'));
          $criteria = $criteria." AND i.ItemAccountGroup = '".$val->Oid."'";
          $filter = $filter."ItemAccountGroup = '".strtoupper($val->Name)."'; ";
      }
      if ($filter) $filter = substr($filter,0);
      if ($criteria) $query = str_replace(" AND 1=1",$criteria,$query);
      if ($criteria2) $query = str_replace(" AND 2=2", $criteria2, $query);

     
      // logger($query);
      $data = DB::select($query);
      switch ($reportname) {
        case 'item1':
            $reporttitle = "Report Item Potrait";
            $pdf = SnappyPdf::loadView('AdminApi\Report::pdf.'.$reportname, compact('data', 'user', 'reportname','filter', 'reporttitle'));
        break;
        case 'item2':
            $reporttitle = "Report Item Landscape";
            $pdf = SnappyPdf::loadView('AdminApi\Report::pdf.'.$reportname, compact('data', 'user', 'reportname','filter', 'reporttitle'))->setPaper('A4', 'landscape');
        break;
        case 'item3':
            $reporttitle = "Report Item Potrait Group by Item Group";
            $pdf = SnappyPdf::loadView('AdminApi\Report::pdf.'.$reportname, compact('data', 'user', 'reportname','filter', 'reporttitle'));
        break;
        case 'item4':
            $reporttitle = "Report Item Potrait Group By Item Group";
            $pdf = SnappyPdf::loadView('AdminApi\Report::pdf.'.$reportname, compact('data', 'user', 'reportname','filter', 'reporttitle'));
        break;
        case 'item5':
            $reporttitle = "Report Item Potrait Group by Item";
            $pdf = SnappyPdf::loadView('AdminApi\Report::pdf.'.$reportname, compact('data', 'user', 'reportname','filter', 'reporttitle'));
        break;
        case 'item6':
          $reporttitle = "Report Updated Item";
          $pdf = SnappyPdf::loadView('AdminApi\POS::pdf.salespos_26', compact('data', 'user', 'reportname','filter', 'reporttitle'));
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
              route('AdminApi\Report::view',
              ['reportName' => $reportPath]), Response::HTTP_OK
          );
    }

    private function query($reportname) {
      switch ($reportname) {
        case 'item1':
          return "SELECT co.Code AS Comp, i.Oid, i.Code, i.Name,
            CONCAT(ig.Name, '-' , ig.Code) AS ItemGroup,
            CONCAT(iag.Name, '-' , iag.Code) AS ItemAccountGroup,
            CONCAT(c.Name, '-' , c.Code) AS City
            FROM mstitem i
            LEFT OUTER JOIN mstitemgroup ig ON i.ItemGroup = ig.Oid
            LEFT OUTER JOIN mstitemaccountgroup iag  ON i.ItemAccountGroup = iag.Oid
            LEFT OUTER JOIN mstcity c ON i.City = c.Oid
            LEFT OUTER JOIN company co ON i.Company = co.Oid
            WHERE i.GCRecord IS NULL AND 1=1
            ORDER BY i.Name
            LIMIT 50";
        break;
        case 'item2' :                
          return "SELECT co.Code AS Comp, i.Oid, i.Code, i.Name,
            CONCAT(ig.Name, '-' , ig.Code) AS ItemGroup,
            CONCAT(iag.Name, '-' , iag.Code) AS ItemAccountGroup,
            CONCAT(c.Name, '-' , c.Code) AS City
            FROM mstitem i
            LEFT OUTER JOIN mstitemgroup ig ON i.ItemGroup = ig.Oid
            LEFT OUTER JOIN mstitemaccountgroup iag  ON i.ItemAccountGroup = iag.Oid
            LEFT OUTER JOIN mstcity c ON i.City = c.Oid
            LEFT OUTER JOIN company co ON i.Company = co.Oid
            WHERE i.GCRecord IS NULL AND 1=1
            ORDER BY i.Name
            LIMIT 50";
        break;
        case 'item3' :                
          return "SELECT co.Code AS Comp, i.Code, i.Name,i.Barcode,
            CONCAT(ig.Name, '-' , ig.Code) AS ItemGroup,
            i.SalesAmount
            FROM mstitem i
            LEFT OUTER JOIN mstitemgroup ig ON i.ItemGroup = ig.Oid
            LEFT OUTER JOIN company co ON i.Company = co.Oid
            WHERE i.GCRecord IS NULL AND 1=1
            ORDER BY ig.Name, ig.Code
            LIMIT 50";
        break;
        case 'item4':
            return "SELECT co.Code AS Comp, i.Code, i.Name, SUM(IFNULL(st.StockQuantity,0)) AS Quantity,
            CONCAT(ig.Name,' - ',ig.Code) AS GroupName
            FROM trdtransactionstock st
            LEFT OUTER JOIN mstitem i ON i.Oid = st.Item
            LEFT OUTER JOIN mstitemgroup ig ON i.ItemGroup = ig.Oid
            LEFT OUTER JOIN sysjournaltype jt ON st.JournalType = jt.Oid
            LEFT OUTER JOIN company co ON i.Company = co.Oid
            WHERE st.GCRecord IS NULL AND jt.Code !='Auto' AND 1=1
            GROUP BY i.Code, i.Name, ig.Name, ig.Code";
        break;
        case 'item5' :                
          return "SELECT co.Code AS Comp, i.Code, i.Name, st.Date,st.Code,CONCAT(w.Name,' - ',w.Code) AS Warehouse, jt.Name AS Type, st.StockQuantity AS Quantity
            FROM trdtransactionstock st
            LEFT OUTER JOIN mstitem i ON i.Oid = st.Item
            LEFT OUTER JOIN mstwarehouse w ON st.Warehouse = w.Oid
            LEFT OUTER JOIN sysjournaltype jt ON st.JournalType = jt.Oid
            LEFT OUTER JOIN company co ON i.Company = co.Oid
            WHERE st.GCRecord IS NULL AND 1=1
            ORDER BY i.Name, i.Code, st.Date";
        break;
        case 'item6':
            return "SELECT
            co.Code AS Comp, 
            i.Name,
            i.Code,
            i.Barcode AS Barcode,
            c.Code AS CurrencyCode,
            i.SalesAmount AS SalesPrice,
            i.UpdatedAt, 
            u.Name AS UpdatedBy
            FROM mstitem i
            LEFT OUTER JOIN user u ON i.UpdatedBy = u.Oid
            LEFT OUTER JOIN mstcurrency c ON i.SalesCurrency = c.Oid
            LEFT OUTER JOIN company co ON i.Company = co.Oid
            WHERE i.GCRecord IS NULL AND 1=1 AND 2=2
            ORDER BY i.Name";
        break;
          default:
              return "";
      }
      return "";
  }


}
