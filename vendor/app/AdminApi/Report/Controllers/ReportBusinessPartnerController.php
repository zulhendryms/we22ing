<?php

namespace App\AdminApi\Report\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

use App\Laravel\Http\Controllers\Controller;
use App\Core\Master\Entities\BusinessPartnerGroup;
use App\Core\Master\Entities\BusinessPartnerAccountGroup;
use App\AdminApi\Report\Services\ReportService;
use App\Core\Master\Entities\Company;

use Barryvdh\DomPDF\Facade as PDF;
use Barryvdh\Snappy\Facades\SnappyPdf;
use Carbon\Carbon;

class ReportBusinessPartnerController extends Controller
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
        $this->reportName = 'BusinessPartner';
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
      if ($request->input('p_BusinessPartnerGroup')) {
          $val = BusinessPartnerGroup::findOrFail($request->input('p_BusinessPartnerGroup'));
          $criteria = $criteria." AND bp.BusinessPartnerGroup = '".$val->Oid."'";
          $filter = $filter."BusinessPartnerGroup = '".strtoupper($val->Name)."'; ";
      }
      if ($request->input('p_BusinessPartnerAccountGroup')) {
          $val = BusinessPartnerAccountGroup::findOrFail($request->input('p_BusinessPartnerAccountGroup'));
          $criteria = $criteria." AND bp.BusinessPartnerAccountGroup = '".$val->Oid."'";
          $filter = $filter."BusinessPartnerAccountGroup = '".strtoupper($val->Name)."'; ";
      }
      $criteria = $criteria . reportQueryCompany('mstbusinesspartner');
      if ($request->input('p_Company')) {
          $val = Company::findOrFail($request->input('p_Company'));
          $criteria = $criteria . " AND co.Oid = '" . $val->Oid . "'";
          $filter = $filter . " AND Company = '" . strtoupper($val->Name) . "'";
      }
      if ($filter) $filter = substr($filter,0);
      if ($criteria) $query = str_replace(" AND 1=1",$criteria,$query);

      $data = DB::select($query);
      
      switch ($reportname) {
        case 'businesspartner1':
            $reporttitle = "Report Potrait Partner Format Potrait";
            $pdf = SnappyPdf::loadView('AdminApi\Report::pdf.'.$reportname, compact('data', 'user', 'reportname','filter', 'reporttitle'));
        break;
        case 'businesspartner2':
            $reporttitle = "Report Business Partner Format Landscape";
            $pdf = SnappyPdf::loadView('AdminApi\Report::pdf.'.$reportname, compact('data', 'user', 'reportname','filter', 'reporttitle'))->setPaper('A4', 'landscape');
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
          case 'businesspartner1':
              return "SELECT bp.Oid, bp.Code, bp.Name, bp.PhoneNumber, bp.Email, bp.FullAddress,
              co.Name AS Company,
              CONCAT(c.Name, ' - ' , c.Code) AS City,co.Code AS Comp,
              CONCAT(bpg.Name, ' - ' , bpg.Code) AS BusinessPartnerGroup
            
              FROM mstbusinesspartner bp
              LEFT OUTER JOIN company co ON bp.Company = co.Oid
              LEFT OUTER JOIN mstcity c ON bp.City = c.Oid
              LEFT OUTER JOIN mstbusinesspartnergroup bpg ON bp.BusinessPartnerGroup = bpg.Oid
              WHERE bp.GCRecord IS NULL AND 1=1
              ORDER BY bp.Name
              LIMIT 50";
          case 'businesspartner2' :                
              return "SELECT bp.Oid, bp.Code, bp.Name, co.Code AS Comp,
              CONCAT(bpg.Name, ' - ' , bpg.Code) AS BusinessPartnerGroup,
              CONCAT(bpag.Name, ' - ' , bpag.Code) AS BusinessPartnerAccountGroup,
              purchaseCurrency.Code AS PurchaseCurrency,
              salesCurrency.Code AS SalesCurrency
              
              FROM mstbusinesspartner bp
              LEFT OUTER JOIN mstbusinesspartnergroup bpg ON bp.BusinessPartnerGroup = bpg.Oid
              LEFT OUTER JOIN mstbusinesspartneraccountgroup bpag ON bp.BusinessPartnerAccountGroup = bpag.Oid
              LEFT OUTER JOIN mstcurrency purchaseCurrency ON bp.PurchaseCurrency = purchaseCurrency.Oid
              LEFT OUTER JOIN mstcurrency salesCurrency ON bp.SalesCurrency = salesCurrency.Oid
              LEFT OUTER JOIN company co ON bp.Company = co.Oid
              WHERE bp.GCRecord IS NULL AND 1=1
              ORDER BY bpg.Name, bp.Name
              LIMIT 50";
          default:
              return "";
      }
      return "";
  }


}