<?php

namespace App\AdminApi\Report\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

use App\Laravel\Http\Controllers\Controller;
use App\Core\Master\Entities\BusinessPartner;
use App\Core\Master\Entities\BusinessPartnerGroup;
use App\Core\Master\Entities\BusinessPartnerAccountGroup;
use App\Core\Master\Entities\City;
use App\AdminApi\Report\Services\ReportService;
use App\Core\Master\Entities\Company;

use Barryvdh\DomPDF\Facade as PDF;
use Barryvdh\Snappy\Facades\SnappyPdf;
use Carbon\Carbon;

class ReportSupplierController extends Controller
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
        $this->reportName = 'supplier';
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

        $criteria = $criteria . reportQueryCompany('mstbusinesspartner');
        if ($request->input('p_Company')) {
            $val = Company::findOrFail($request->input('p_Company'));
            $criteria = $criteria . " AND co.Oid = '" . $val->Oid . "'";
            $filter = $filter . " AND Company = '" . strtoupper($val->Name) . "'";
        }
        if ($request->input('City')) {
            $val = City::findOrFail($request->input('City'));
            $criteria = $criteria." AND bp.City = '".$val->Oid."'";
            $filter = $filter."City = '".strtoupper($val->Name)."'; ";
        } 
        if ($request->input('p_BusinessPartnerGroup')) {
            $val = BusinessPartnerGroup::findOrFail($request->input('p_BusinessPartnerGroup'));
            $criteria = $criteria." AND bp.BusinessPartnerGroup = '".$val->Oid."'";
            $filter = $filter."B.P.Group = '".strtoupper($val->Name)."'; ";
        }
        if ($request->input('p_BusinessPartnerAccountGroup')) {
            $val = BusinessPartnerAccountGroup::findOrFail($request->input('p_BusinessPartnerAccountGroup'));
            $criteria = $criteria." AND bp.BusinessPartnerAccountGroup = '".$val->Oid."'";
            $filter = $filter."Account= '".strtoupper($val->Name)."'; ";
        }
        if ($filter) $filter = substr($filter,0);
        if ($criteria) $query = str_replace(" AND 1=1",$criteria,$query);

        $data = DB::select($query);

        switch ($reportname) {
            case 'supplier1':
                $reporttitle = "Report Supplier Group By City";
                $pdf = SnappyPdf::loadView('AdminApi\Report::pdf.'.$reportname, compact('data', 'user', 'reportname','filter', 'reporttitle'));
            break;
            case 'supplier2':
                $reporttitle = "Report Supplier Group By City (Incl. Address)";
                $pdf = SnappyPdf::loadView('AdminApi\Report::pdf.'.$reportname, compact('data', 'user', 'reportname','filter', 'reporttitle'));
            break;
            case 'supplier3':
                $reporttitle = "Report Supplier Group By Business Partner";
                $pdf = SnappyPdf::loadView('AdminApi\Report::pdf.'.$reportname, compact('data', 'user', 'reportname','filter', 'reporttitle'));
            break;
            case 'supplier4':
                $reporttitle = "Report Supplier Group By Business Partner (Incl. Address)";
                $pdf = SnappyPdf::loadView('AdminApi\Report::pdf.'.$reportname, compact('data', 'user', 'reportname','filter', 'reporttitle'));
            break;
            case 'supplier5':
                $reporttitle = "Report Supplier Group By Account";
                $pdf = SnappyPdf::loadView('AdminApi\Report::pdf.'.$reportname, compact('data', 'user', 'reportname','filter', 'reporttitle'));
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
            default:
                return "SELECT
                        bp.Name, bp.Email,bp.PhoneNumber,bp.Website,
                        bpg.Name AS BusinessPartnerGroup, co.Code AS Comp,
                        CONCAT (c.Name,' - ',c.Code) AS City,
                        CONCAT (bpag.Name,' - ',bpag.Code) AS BusinessPartnerAccountGroup,
                        bpa.Address AS Address,
                        pt.Name AS PurchaseTerm,
                        t.Name AS PurchaseTax,
                        cur.Code AS PurchaseCurrency
                        FROM mstbusinesspartner bp
                        LEFT OUTER JOIN mstcity c ON bp.City = c.Oid
                        LEFT OUTER JOIN mstbusinesspartnergroup bpg ON bp.BusinessPartnerGroup = bpg.Oid
                        LEFT OUTER JOIN mstbusinesspartneraccountgroup bpag ON bp.BusinessPartnerGroup = bpag.Oid
                        LEFT OUTER JOIN mstbusinesspartneraddress bpa ON bp.BusinessPartnerGroup = bpa.Oid
                        LEFT OUTER JOIN mstcurrency cur ON bp.PurchaseCurrency = cur.Oid
                        LEFT OUTER JOIN msttax t ON bp.PurchaseTax = t.Oid
                        LEFT OUTER JOIN company co ON bp.Company = co.Oid
                        LEFT OUTER JOIN mstpaymentterm pt ON bp.PurchaseTerm = pt.Oid
                        WHERE bp.GCRecord IS NULL AND 1=1 AND bp.IsPurchase = 1
                        ORDER BY bp.Code
                        LIMIT 50";
        }
        return "";
    }


}
