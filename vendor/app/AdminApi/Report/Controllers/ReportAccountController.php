<?php

namespace App\AdminApi\Report\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

use App\Laravel\Http\Controllers\Controller;
use App\Core\Accounting\Entities\AccountSection;
use App\Core\Accounting\Entities\AccountGroup;
use App\Core\Internal\Entities\AccountType;
use App\AdminApi\Report\Services\ReportService;
use App\Core\Master\Entities\Company;
use App\AdminApi\Development\Controllers\ReportGeneratorController;

use Barryvdh\DomPDF\Facade as PDF;
use Barryvdh\Snappy\Facades\SnappyPdf;
use Carbon\Carbon;

class ReportAccountController extends Controller
{
    protected $reportService;
    protected $reportName;
    private $reportGeneratorController;

    public function __construct(ReportService $reportService)
    {
        $this->reportName = 'Account';
        $this->reportService = $reportService;
        $this->reportGeneratorController = new ReportGeneratorController();
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

        $criteria = $criteria . reportQueryCompany('accaccount');
        if ($request->input('p_Company')) {
            $val = Company::findOrFail($request->input('p_Company'));
            $valSource = Company::findOrFail($val->CompanySource);
            $criteria = $criteria . " AND (a.Company = '".$val->Oid."' OR a.Company='".$valSource->Oid."' OR a.Company='".$valSource->CompanySource."')";
            $filter = $filter . " AND Company = '" . strtoupper($val->Name) . "'";
        }
        if ($request->input('AccountSection')) {
            $val = AccountSection::findOrFail($request->input('AccountSection'));
            $criteria = $criteria." AND ag.AccountSection = '".$val->Oid."'";
            $filter = $filter."Account Section = '".strtoupper($val->Name)."'; ";
        }
        if ($request->input('p_AccountGroup')) {
            $val = AccountGroup::findOrFail($request->input('p_AccountGroup'));
            $criteria = $criteria." AND a.AccountGroup = '".$val->Oid."'";
            $filter = $filter."AccountGroup = '".strtoupper($val->Name)."'; ";
        }
        if ($request->input('p_AccountType')) {
            $val = AccountType::findOrFail($request->input('p_AccountType'));
            $criteria = $criteria." AND a.AccountType = '".$val->Oid."'";
            $filter = $filter."AccountType = '".strtoupper($val->Name)."'; ";
        }
        if ($filter) $filter = substr($filter,0);
        if ($criteria) $query = str_replace(" AND 1=1",$criteria,$query);
        $data = DB::select($query);

        switch ($reportname) {
            case 'account1':
                $reporttitle = "Report Account Order By Name";
                $pdf = SnappyPdf::loadView('AdminApi\Report::pdf.'.$reportname, compact('data', 'user', 'reportname','filter', 'reporttitle'));
            break;
            case 'account2':
                $reporttitle = "Report Account Group By Account Group";
                $pdf = SnappyPdf::loadView('AdminApi\Report::pdf.'.$reportname, compact('data', 'user', 'reportname','filter', 'reporttitle'));
            break;
            case 'account3':
                $reporttitle = "Report Account Group By Account Section";
                $pdf = SnappyPdf::loadView('AdminApi\Report::pdf.'.$reportname, compact('data', 'user', 'reportname','filter', 'reporttitle'));
            break;
            case 'account4':
                $reporttitle = "Report Account With Balance";
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
            case 'account1':
                return "SELECT a.Oid, a.Code, a.Name, co.Code AS Comp,
                    CONCAT(ag.Name,' - ',ag.Code) AS AccountGroup,
                    CONCAT(acs.Name,' - ',acs.Code) AS AccountSection,
                    CONCAT(act.Name,' - ',act.Code) AS AccountType,
                    CONCAT(c.Code) AS Currency,
                    CONCAT(co.Name,' - ',co.Code) AS Company
                    FROM accaccount a
                    LEFT OUTER JOIN accaccountgroup ag ON a.AccountGroup = ag.Oid
                    LEFT OUTER JOIN accaccountsection acs ON ag.AccountSection = acs.Oid
                    LEFT OUTER JOIN sysaccounttype act ON a.AccountType = act.Oid
                    LEFT OUTER JOIN mstcurrency c ON a.Currency = c.Oid
                    LEFT OUTER JOIN company co ON a.Company = co.Oid
                    WHERE a.GCRecord IS NULL AND 1=1
                    ORDER BY a.Code";
            case 'account2' :                
                return "SELECT a.Oid, a.Code, a.Name, co.Code AS Comp,
                    CONCAT(ag.Name,' - ',ag.Code) AS AccountGroup,
                    CONCAT(acs.Name,' - ',acs.Code) AS AccountSection,
                    CONCAT(act.Name,' - ',act.Code) AS AccountType,
                    CONCAT(c.Code) AS Currency,
                    CONCAT(co.Name,' - ',co.Code) AS Company
                    FROM accaccount a
                    LEFT OUTER JOIN accaccountgroup ag ON a.AccountGroup = ag.Oid
                    LEFT OUTER JOIN accaccountsection acs ON ag.AccountSection = acs.Oid
                    LEFT OUTER JOIN sysaccounttype act ON a.AccountType = act.Oid
                    LEFT OUTER JOIN mstcurrency c ON a.Currency = c.Oid
                    LEFT OUTER JOIN company co ON a.Company = co.Oid
                    WHERE a.GCRecord IS NULL AND 1=1
                    ORDER BY a.Code";
            case 'account3' :                
                return "SELECT CONCAT(ast.Name,' - ',ast.Code) AS AccountSection,
                    CONCAT(ag.Name,' - ',ag.Code) AS AccountGroup,
                    a.Code, a.Name, co.Code AS Comp,
                    CONCAT(at.Name,' - ',at.Code) AS AccountType,
                    c.Code AS Currency
                    FROM accaccount a
                    LEFT OUTER JOIN accaccountgroup ag ON a.AccountGroup = ag.Oid
                    LEFT OUTER JOIN mstcurrency c ON a.Currency = c.Oid
                    LEFT OUTER JOIN sysaccounttype at ON a.AccountType = at.Oid
                    LEFT OUTER JOIN accaccountsection ast ON ag.AccountSection = ast.Oid
                    WHERE a.GCRecord IS NULL AND 1=1
                    ORDER BY a.Code";
            case 'account4' :                
                return "SELECT a.Code, a.Name, c.Code AS Currency, cc.Code AS CurrencyBase,
                    CONCAT(ag.Name,'_',ag.Code) AS AccountGroup,
                    ast.Name AS AccountSection, co.Code AS Comp,
                    CASE WHEN co.Currency = a.Currency THEN SUM(IFNULL(j.DebetBase,0) - IFNULL(j.CreditBase,0)) ELSE SUM(IFNULL(j.DebetAmount,0) - IFNULL(j.CreditAmount,0)) END AS Amount,
                    SUM(IFNULL(j.DebetBase,0) - IFNULL(j.CreditBase,0)) AS BaseAmount
                    FROM accaccount a
                    LEFT OUTER JOIN accjournal j ON j.Account = a.Oid
                    LEFT OUTER JOIN mstcurrency c ON a.Currency = c.Oid
                    LEFT OUTER JOIN company co ON a.Company = co.Oid
                    LEFT OUTER JOIN mstcurrency cc ON co.Currency = cc.Oid
                    LEFT OUTER JOIN accaccountgroup ag ON a.AccountGroup = ag.Oid
                    LEFT OUTER JOIN accaccountsection ast ON ag.AccountSection = ast.Oid
                    GROUP BY a.Code, a.Name, c.Code, cc.Code, ag.Name, ag.Code, ast.Name";
            default:
                return "";
        }
        return "";
    }


}
