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

class ReportProfitLossController extends Controller
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
        $this->reportName = 'profitloss';
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
        $criteria = ""; $criteria1 = ""; $criteria2 = ""; $criteria3 = ""; $filter="";
        
        $datefrom = Carbon::parse($request->input('DateStart'));
        $datefromless2 = date('Y-m', strtotime("-2 months", strtotime($datefrom)));
        $datefromless1 = date('Y-m', strtotime("-1 months", strtotime($datefrom)));
        $datefromless0 = date('Y-m', strtotime("-0 months", strtotime($datefrom)));
        $criteria = $criteria." AND DATE_FORMAT(j.Date,'%Y-%m') = '".$datefrom->format('Y-m')."'";
        $filter = $filter."Date From = '".strtoupper($datefrom->format('Y-m'))."'; "; 
        $criteria1 = $criteria1." AND DATE_FORMAT(j.Date,'%Y-%m') = '".$datefromless2."'";
        $criteria2 = $criteria2." AND DATE_FORMAT(j.Date,'%Y-%m') = '".$datefromless1."'";
        $criteria3 = $criteria3." AND DATE_FORMAT(j.Date,'%Y-%m') = '".$datefromless0."'";
        $criteria = $criteria . reportQueryCompany('accjournal');
        if ($request->input('p_Company')) {
            $val = Company::findOrFail($request->input('p_Company'));
            $criteria = $criteria . " AND co.CompanySource = '" . $val->CompanySource . "'";
            $filter = $filter . " AND Company = '" . strtoupper($val->Name) . "'";
        }
        if ($filter) $filter = substr($filter,0);
        if ($criteria) $query = str_replace(" AND 0=0",$criteria,$query);
        if ($criteria1) $query = str_replace(" AND 1=1",$criteria1,$query);
        if ($criteria2) $query = str_replace(" AND 2=2",$criteria2,$query);
        if ($criteria3) $query = str_replace(" AND 3=3",$criteria3,$query);

        logger($query);
        $data = DB::select($query);

        switch ($reportname) {
            case 'profitloss1':
                $reporttitle = "Report ProfitLoss Standard Version";
                $pdf = SnappyPdf::loadView('AdminApi\Report::pdf.'.$reportname, compact('data', 'user', 'reportname','filter', 'reporttitle'));
                $marginleft = 10;
                $marginright = 15;
            break;
            case 'profitloss2':
                $reporttitle = "Report ProfitLoss";
                $pdf = SnappyPdf::loadView('AdminApi\Report::pdf.'.$reportname, compact('data', 'user', 'reportname','filter', 'reporttitle'));
                $marginleft = 50;
                $marginright = 50;
            break;
            case 'profitloss3':
                $reporttitle = "Report ProfitLoss Analyze Per Group";
                $pdf = SnappyPdf::loadView('AdminApi\Report::pdf.'.$reportname, compact('data', 'user', 'reportname','filter', 'reporttitle'));
                $marginleft = 10;
                $marginright = 15;
            break;
            case 'profitloss4':
                $periode1 = $datefromless2;
                $periode2 = $datefromless1;
                $periode3 = $datefromless0;
                $reporttitle = "Report ProfitLoss Multi Period (3 Months) NEW";
                $pdf = SnappyPdf::loadView('AdminApi\Report::pdf.'.$reportname, compact('data', 'user', 'reportname','filter', 'reporttitle','periode1','periode2','periode3'));
                $marginleft = 10;
                $marginright = 15;
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
            ->setOption('margin-right', $marginright)
            ->setOption('margin-left', $marginleft)
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

    private function query($reportname) 
    {
        switch ($reportname) {
            case 'profitloss1':
                return "SELECT co.Code AS Comp, t.ProfitLossGroup, t.ProfitLossTotal, t.ProfitLossSeq, a.Code AS Sort, 
                    a1.Code AS Code1, a1.Name AS Name1,
                    a2.Code AS Code2, a2.Name AS Name2, 
                    a.Code AS Code3, a.Name AS Name3,
                    (SUM(IFNULL(j.DebetBase,0) - IFNULL(j.CreditBase,0))) * -1 AS Amount0,
                    IF(t.Code = 'INC' || t.Code = 'OI', -1, 1) * (SUM(IFNULL(j.DebetBase,0) - IFNULL(j.CreditBase,0))) AS Amount1,
                    0 AS Amount2
                    FROM accjournal j
                    LEFT OUTER JOIN accaccount a ON a.Oid = j.Account
                    LEFT OUTER JOIN accaccountgroup a2 ON a.AccountGroup = a2.Oid
                    LEFT OUTER JOIN accaccountsection a1 ON a2.AccountSection = a1.Oid
                    LEFT OUTER JOIN sysaccounttype t ON t.Oid = a.AccountType
                    LEFT OUTER JOIN sysjournaltype jt ON jt.Oid = j.JournalType
                    LEFT OUTER JOIN company co ON co.Oid = j.Company
                    WHERE jt.Code != 'OPEN' 
                    AND ProfitLossGroup IS NOT NULL
                    AND LENGTH(TRIM(ProfitLossGroup)) > 0
                    AND j.GCRecord IS NULL
                    AND 0=0
                    GROUP BY a1.Code, a1.Name, a2.Code, a2.Name, a.Code, a.Name, t.BalanceSheetGroup, t.ProfitLossSeq, t.ProfitLossTotal, t.ProfitLossGroup, t.Code";
            break;
            case 'profitloss2':
            case 'profitloss3':
                return "SELECT co.Code AS Comp, t.ProfitLossGroup, t.ProfitLossTotal, t.ProfitLossSeq, a.Code AS Sort, 
                    a1.Code AS Code1, a1.Name AS Name1,
                    a2.Code AS Code2, a2.Name AS Name2, 
                    a.Code AS Code3, a.Name AS Name3,
                    (SUM(IFNULL(j.DebetBase,0) - IFNULL(j.CreditBase,0))) * -1 AS Amount0,
                    IF(t.Code = 'INC' || t.Code = 'OI', -1, 1) * (SUM(IFNULL(j.DebetBase,0) - IFNULL(j.CreditBase,0))) AS Amount1,
                    0 AS Amount2
                    FROM accjournal j
                    LEFT OUTER JOIN accaccount a ON a.Oid = j.Account
                    LEFT OUTER JOIN accaccountgroup a2 ON a.AccountGroup = a2.Oid
                    LEFT OUTER JOIN accaccountsection a1 ON a2.AccountSection =a1.Oid
                    LEFT OUTER JOIN sysaccounttype t ON t.Oid = a.AccountType
                    LEFT OUTER JOIN sysjournaltype jt ON jt.Oid = j.JournalType
                    LEFT OUTER JOIN company co ON co.Oid = j.Company
                    WHERE jt.Code != 'OPEN' 
                    AND ProfitLossGroup IS NOT NULL
                    AND LENGTH(TRIM(ProfitLossGroup)) > 0
                    AND j.GCRecord IS NULL
                    AND 0=0
                    GROUP BY a1.Code, a1.Name, a2.Code, a2.Name, a.Code, a.Name, t.BalanceSheetGroup, t.ProfitLossSeq, t.ProfitLossTotal, t.ProfitLossGroup, t.Code";
            break;
            case 'profitloss4' :
                return "SELECT co.Code AS Comp, t.ProfitLossGroup, t.ProfitLossSeq, t.ProfitLossTotal, a.Code AS Sort, 
                    a1.Code AS Code1, a1.Name AS Name1,
                    a2.Code AS Code2, a2.Name AS Name2, 
                    a.Code AS Code3, a.Name AS Name3,
                    SUM(j1.Total) * -1 AS p1amt0, IF(t.Code = 'INC' || t.Code = 'OI', -1, 1) * SUM(j1.Total) AS p1amt1,
                    SUM(j2.Total) * -1 AS p2amt0, IF(t.Code = 'INC' || t.Code = 'OI', -1, 1) * SUM(j2.Total) AS p2amt1,
                    SUM(j3.Total) * -1 AS p3amt0, IF(t.Code = 'INC' || t.Code = 'OI', -1, 1) * SUM(j3.Total) AS p3amt1,
                    'Period-1' AS Period1,'Period-2' AS Period2,'Period-3' AS Period3
                    FROM accaccount a 
                    LEFT OUTER JOIN accaccountgroup a2 ON a.AccountGroup = a2.Oid
                    LEFT OUTER JOIN accaccountsection a1 ON a2.AccountSection = a1.Oid
                    LEFT OUTER JOIN sysaccounttype t ON t.Oid = a.AccountType
                    LEFT OUTER JOIN company co ON co.Oid = a.Company
                    LEFT OUTER JOIN (
                        SELECT j.Account, (SUM(IFNULL(j.DebetBase,0) - IFNULL(j.CreditBase,0))) AS Total
                        FROM accjournal j 
                        LEFT OUTER JOIN sysjournaltype jt ON jt.Oid = j.JournalType
                        WHERE jt.Code != 'Open' AND j.GCRecord IS NULL AND 1=1
                        GROUP BY j.Account) AS j1 ON a.Oid = j1.Account
                    LEFT OUTER JOIN (
                        SELECT j.Account, (SUM(IFNULL(j.DebetBase,0) - IFNULL(j.CreditBase,0))) AS Total
                        FROM accjournal j 
                        LEFT OUTER JOIN sysjournaltype jt ON jt.Oid = j.JournalType
                        WHERE jt.Code != 'Open' AND j.GCRecord IS NULL AND 2=2
                        GROUP BY j.Account) AS j2 ON a.Oid = j2.Account
                    LEFT OUTER JOIN (
                    SELECT j.Account, (SUM(IFNULL(j.DebetBase,0) - IFNULL(j.CreditBase,0))) AS Total
                    FROM accjournal j 
                    LEFT OUTER JOIN sysjournaltype jt ON jt.Oid = j.JournalType
                    WHERE jt.Code != 'Open' AND j.GCRecord IS NULL AND 3=3
                    GROUP BY j.Account) AS j3 ON a.Oid = j3.Account    
                    WHERE ProfitLossGroup IS NOT NULL
                    AND LENGTH(TRIM(ProfitLossGroup)) > 0
                    GROUP BY a1.Code, a1.Name, a2.Code, a2.Name, a.Code, a.Name, t.BalanceSheetGroup, t.ProfitLossSeq, t.ProfitLossTotal, Period1, Period2, Period3, t.ProfitLossGroup, t.Code
                    HAVING SUM(j1.Total) != 0 OR SUM(j2.Total) != 0 OR SUM(j3.Total) != 0";
            break;
        }
        return "";
    }
}
