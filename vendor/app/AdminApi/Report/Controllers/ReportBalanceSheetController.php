<?php

namespace App\AdminApi\Report\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

use App\Laravel\Http\Controllers\Controller;
use App\Core\Accounting\Entities\Account;
use App\Core\Accounting\Entities\AccountGroup;
use App\Core\Accounting\Entities\AccountSection;
use App\Core\Internal\Entities\AccountType;
use App\Core\Internal\Entities\JournalType;
use App\AdminApi\Report\Services\ReportService;
use App\Core\Master\Entities\Company;

use Barryvdh\DomPDF\Facade as PDF;
use Barryvdh\Snappy\Facades\SnappyPdf;
use Carbon\Carbon;

class ReportBalanceSheetController extends Controller
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
        $this->reportName = 'balancesheet';
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

        
        $date = Carbon::parse($request->input('Date'));
        $criteria = $criteria." AND DATE_FORMAT(j.Date,'%Y-%m') <= '".$date->format('Y-m')."'";
        $filter = $filter." AND Date From = '".strtoupper($date->format('Y-m-d'))."'";

        $criteria = $criteria . reportQueryCompany('accjournal');
        if ($request->input('p_Company')) {
            $val = Company::findOrFail($request->input('p_Company'));
            $criteria = $criteria . " AND co.Oid = '" . $val->Oid . "'";
            $filter = $filter . " AND Company = '" . strtoupper($val->Name) . "'";
        }
        if ($filter) $filter = substr($filter,0);
        if ($criteria) $query = str_replace(" AND 1=1",$criteria,$query);

        $data = DB::select($query);

        switch ($reportname) {
            case 'balancesheet1':
                $reporttitle = "Report Balance Sheet";
            // return view('AdminApi\Report::pdf.'.$reportname, compact('data', 'user', 'reportname','filter', 'reporttitle'));
                $pdf = SnappyPdf::loadView('AdminApi\Report::pdf.'.$reportname, compact('data', 'user', 'reportname','filter', 'reporttitle'));
                // $marginleft = 45;
                // $marginright = 45;
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
            // ->setOption('margin-right', $marginright)
            // ->setOption('margin-left', $marginleft)
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
                co.Code AS Comp,
                t.BalanceSheetGroup,
                ac.Code AS Sort,
                acs.Code AS SectionCode,
                acs.Name AS SectionName,
                acd.Code AS GroupCode,
                acd.Name AS GroupName,
                ac.Code AS AccountCode,
                ac.Name AS AccountName,
                IF(t.BalanceSheetGroup = 'ASSETS', 1, -1) * (SUM(IFNULL(j.DebetBase,0) - IFNULL(j.CreditBase,0))) AS Amount0,
                SUM(IFNULL(j.DebetBase,0) - IFNULL(j.CreditBase,0)) AS Amount1
            FROM accjournal j
            LEFT OUTER JOIN accaccount ac ON ac.Oid = j.Account
            LEFT OUTER JOIN accaccountgroup acd ON ac.AccountGroup = acd.Oid
            LEFT OUTER JOIN accaccountsection acs ON acd.AccountSection = acs.Oid
            LEFT OUTER JOIN sysaccounttype t ON t.Oid = ac.AccountType
            LEFT OUTER JOIN sysjournaltype jt ON jt.Oid = j.JournalType
            LEFT OUTER JOIN company co ON co.Oid = j.Company
            WHERE BalanceSheetGroup IS NOT NULL AND LENGTH(TRIM(BalanceSheetGroup)) > 0 AND jt.Code != 'OPEN'
            AND j.GCRecord IS NULL
            AND 1=1
            GROUP BY t.BalanceSheetGroup, ac.Code, ac.Name, acs.Code, acs.Name, acd.Code, acd.Name, co.Code
            HAVING SUM(IFNULL(j.DebetBase,0) - IFNULL(j.CreditBase,0)) != 0
            ";
      }
      return "";
  }


}
