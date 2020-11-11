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
use App\Core\Master\Entities\BusinessPartner;
use App\AdminApi\Report\Services\ReportService;

use Barryvdh\DomPDF\Facade as PDF;
use Barryvdh\Snappy\Facades\SnappyPdf;
use Carbon\Carbon;

class ReportARAPController extends Controller
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
        $this->reportName = 'arap';
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
      $criteria1 = ""; $criteria2 = ""; $criteria3 = ""; $criteria4 = ""; 
      $filter="";
      
      $datefrom = Carbon::parse($request->input('DateStart'));
      $dateto = Carbon::parse($request->input('DateUntil'));
      $firstDayOfMonth =Carbon::parse($request->input('DateStart'))->modify('first day of this month');

      $filter = $filter."Date From = '".strtoupper($datefrom->format('Y-m-d'))."'; "; 
      $filter = $filter."Date To = '".strtoupper($dateto->format('Y-m-d'))."'; "; 

      $criteria1 = $criteria1." AND DATE_FORMAT(j.Date, '%Y-%m-%d') >= '".$datefrom->format('Y-m-d')."'";
      $criteria1 = $criteria1." AND DATE_FORMAT(j.Date, '%Y-%m-%d') <= '".$dateto->format('Y-m-d')."'";

      $criteria3 = $criteria3." AND j.Date <= '".$datefrom->format('Y-m-d')."'";
      
      $criteria2 = $criteria2." AND j.Date >= '".$firstDayOfMonth->format('Y-m-d')."'";
      $criteria2 = $criteria2." AND j.Date < '".$datefrom->format('Y-m-d')."'";
  
      $criteria4 = $criteria4." AND DATE_FORMAT(cb.Date, '%Y-%m-%d') >= '".$datefrom->format('Y-m-d')."'";
      $criteria4 = $criteria4." AND DATE_FORMAT(cb.Date, '%Y-%m-%d') <= '".$dateto->format('Y-m-d')."'";

      if ($request->input('p_Account')) {
          $val = Account::findOrFail($request->input('p_Account'));
          $criteria1 = $criteria1." AND j.Account = '".$val->Oid."'";
          $criteria2 = $criteria2." AND j.Account = '".$val->Oid."'";
          $criteria3 = $criteria3." AND j.Account = '".$val->Oid."'";
          $criteria4 = $criteria4." AND j.Account = '".$val->Oid."'";
          $filter = $filter."Account = '".strtoupper($val->Name)."'; ";
      }
      if ($request->input('p_BusinessPartner')) {
          $val = BusinessPartner::findOrFail($request->input('p_BusinessPartner'));
          $criteria1 = $criteria1." AND j.BusinessPartner = '".$val->Oid."'";
          $criteria2 = $criteria2." AND j.BusinessPartner = '".$val->Oid."'";
          $criteria3 = $criteria3." AND j.BusinessPartner = '".$val->Oid."'";
          $criteria4 = $criteria4." AND j.BusinessPartner = '".$val->Oid."'";
          $filter = $filter."B.Partner = '".strtoupper($val->Name)."'; ";
      }
      if ($request->input('p_AccountGroup')) {
        $val = AccountGroup::findOrFail($request->input('p_AccountGroup'));
        $criteria1 = $criteria1." AND a.AccountGroup = '".$val->Oid."'";
        $criteria2 = $criteria2." AND a.AccountGroup = '".$val->Oid."'";
        $criteria3 = $criteria3." AND a.AccountGroup = '".$val->Oid."'";
        $criteria4 = $criteria4." AND a.AccountGroup = '".$val->Oid."'";
        $filter = $filter."A. Group= '".strtoupper($val->Name)."'; ";
      }

      if ($filter) $filter = substr($filter,0);
      if ($criteria1) $query = str_replace(" AND 1=1",$criteria1,$query);
      if ($criteria2) $query = str_replace(" AND 2=2",$criteria2,$query);
      if ($criteria3) $query = str_replace(" AND 3=3",$criteria3,$query);
      if ($criteria4) $query = str_replace(" AND 4=4",$criteria4,$query);
      
      logger($query);
      $data = DB::select($query);

      switch ($reportname) {
        case 'arap1':
            $reporttitle = "Report AR / AP Detail Order By Date";
            $pdf = SnappyPdf::loadView('AdminApi\Report::pdf.'.$reportname, compact('data', 'user', 'reportname','filter', 'reporttitle'));
        break;
        case 'arap2':
            $reporttitle = "Report AR / AP Detail Order By Date (Incl. Base Amt)";
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
          default:
              return "SELECT * FROM ( SELECT
                        'CompanyName' AS CompanyName,
                        'CriteriaString' AS CriteriaString,
                        1 AS Seq1,
                        1 AS Seq2,
                        CONCAT(a.Name, ' - ', a.Code) AS AccountCashBank,
                        '' AS Account,
                        '' AS Code,
                        date_add(j.Date,interval -DAY(j.Date)+1 DAY) AS Date,
                        'Saldo awal bulan ' AS Description,
                        cur.Code AS Currency,
                        IF(SUM(IFNULL(j.DebetAmount,0)-IFNULL(j.CreditAmount,0)) > 0, SUM(IFNULL(j.DebetAmount,0)-IFNULL(j.CreditAmount,0)), 0) AS DebetAmount,
                        IF(SUM(IFNULL(j.DebetAmount,0)-IFNULL(j.CreditAmount,0)) < 0, SUM(IFNULL(j.DebetAmount,0)-IFNULL(j.CreditAmount,0))*-1, 0) AS CreditAmount,
                        IF(SUM(IFNULL(j.DebetBase,0)-IFNULL(j.CreditBase,0)) > 0, SUM(IFNULL(j.DebetBase,0)-IFNULL(j.CreditBase,0)), 0) AS DebetBase,
                        IF(SUM(IFNULL(j.DebetBase,0)-IFNULL(j.CreditBase,0)) < 0, SUM(IFNULL(j.DebetBase,0)-IFNULL(j.CreditBase,0))*-1, 0) AS CreditBase,
                        AVG(j.Rate) AS Rate,
                        '' AS JournalType,
                        bp.Name AS BusinessPartner,
                        '' AS Status,
                        bcur.Code AS basecurrency
                      FROM accjournal j
                        LEFT OUTER JOIN accaccount a ON a.Oid = j.Account
                        LEFT OUTER JOIN sysaccounttype at ON at.Oid = a.AccountType
                        LEFT OUTER JOIN sysjournaltype jt ON jt.Oid = j.JournalType
                        LEFT OUTER JOIN mstcurrency cur ON cur.Oid = j.Currency
                        LEFT OUTER JOIN company co ON j.Company = co.Oid
                        LEFT OUTER JOIN mstcurrency bcur ON bcur.Oid = co.Currency  
                        LEFT OUTER JOIN mstbusinesspartner bp ON bp.Oid = j.BusinessPartner
                      WHERE j.GCRecord IS NULL 
                        AND jt.Code = 'OPEN' 
                        AND at.Code IN ('AR','AP', 'PDP', 'SDP')
                        AND 3=3
                      GROUP BY a.Code, a.Name, cur.Code, bcur.Code, j.BusinessPartner
                      HAVING SUM(IFNULL(j.DebetBase,0)-IFNULL(j.CreditBase,0)) + SUM(IFNULL(j.DebetAmount,0)-IFNULL(j.CreditAmount,0)) != 0
                      UNION ALL
                      SELECT
                        'CompanyName' AS CompanyName,
                        'CriteriaString' AS CriteriaString,
                        1 AS Seq1,
                        2 AS Seq2,
                        CONCAT(a.Name, ' - ', a.Code) AS AccountCashBank,
                        '' AS Account,
                        '' AS Code,
                        date_add(j.Date,interval -DAY(j.Date)+1 DAY) AS Date,
                        CONCAT('Transaksi bulan ', DATE_FORMAT(j.Date, '%b %Y')) AS Description,
                        cur.Code AS Currency,
                        IF(SUM(IFNULL(j.DebetAmount,0)-IFNULL(j.CreditAmount,0)) > 0, SUM(IFNULL(j.DebetAmount,0)-IFNULL(j.CreditAmount,0)), 0) AS DebetAmount,
                        IF(SUM(IFNULL(j.DebetAmount,0)-IFNULL(j.CreditAmount,0)) < 0, SUM(IFNULL(j.DebetAmount,0)-IFNULL(j.CreditAmount,0))*-1, 0) AS CreditAmount,
                        IF(SUM(IFNULL(j.DebetBase,0)-IFNULL(j.CreditBase,0)) > 0, SUM(IFNULL(j.DebetBase,0)-IFNULL(j.CreditBase,0)), 0) AS DebetBase,
                        IF(SUM(IFNULL(j.DebetBase,0)-IFNULL(j.CreditBase,0)) < 0, SUM(IFNULL(j.DebetBase,0)-IFNULL(j.CreditBase,0))*-1, 0) AS CreditBase,
                        AVG(j.Rate) AS Rate,
                        '' AS JournalType,
                        bp.Name AS BusinessPartner,
                        '' AS Status,
                        bcur.Code AS basecurrency
                      FROM accjournal j
                        LEFT OUTER JOIN accaccount a ON a.Oid = j.Account
                        LEFT OUTER JOIN sysaccounttype at ON at.Oid = a.AccountType
                        LEFT OUTER JOIN sysjournaltype jt ON jt.Oid = j.JournalType
                        LEFT OUTER JOIN mstcurrency cur ON cur.Oid = j.Currency
                        LEFT OUTER JOIN company co ON j.Company = co.Oid
                        LEFT OUTER JOIN mstcurrency bcur ON bcur.Oid = co.Currency  
                        LEFT OUTER JOIN mstbusinesspartner bp ON bp.Oid = j.BusinessPartner
                      WHERE j.GCRecord IS NULL 
                        AND jt.Code != 'OPEN' 
                        AND at.Code IN ('AR','AP', 'PDP', 'SDP')
                        AND 2=2
                      GROUP BY a.Code, a.Name, cur.Code, bcur.Code, j.BusinessPartner
                      HAVING SUM(IFNULL(j.DebetBase,0)-IFNULL(j.CreditBase,0)) + SUM(IFNULL(j.DebetAmount,0)-IFNULL(j.CreditAmount,0)) != 0
                      UNION ALL
                      SELECT
                        'CompanyName' AS CompanyName,
                        'CriteriaString' AS CriteriaString,
                        3 AS Seq1,
                        IF(at.Code = 'AR' AND at.Code = 'SDP', IF(j.DebetAmount > 0,3,4),IF(j.DebetAmount > 0,4,3)) AS Seq2,
                        CONCAT(a.Name, ' - ', a.Code) AS AccountCashBank,
                        '' AS Account,
                        j.Code AS Code,
                        j.Date,
                        j.Description,
                        cur.Code AS Currency,
                        j.DebetAmount,
                        j.CreditAmount,
                        j.DebetBase,
                        j.CreditBase,
                        j.Rate,
                        jt.Code AS JournalType,
                        bp.Name AS BusinessPartner,
                        j.Status,
                        bcur.Code AS basecurrency
                      FROM accjournal j
                        LEFT OUTER JOIN accaccount a ON a.Oid = j.Account
                        LEFT OUTER JOIN sysaccounttype at ON at.Oid = a.AccountType
                        LEFT OUTER JOIN sysjournaltype jt ON jt.Oid = j.JournalType
                        LEFT OUTER JOIN mstcurrency cur ON cur.Oid = j.Currency
                        LEFT OUTER JOIN mstbusinesspartner bp ON bp.Oid = j.BusinessPartner
                        LEFT OUTER JOIN company co ON j.Company = co.Oid
                        LEFT OUTER JOIN mstcurrency bcur ON bcur.Oid = co.Currency  
                      WHERE j.GCRecord IS NULL 
                        AND jt.Code != 'OPEN' 
                        AND at.Code IN ('AR','AP', 'PDP', 'SDP')
                        AND 1=1) arap GROUP BY arap.BusinessPartner, arap.Date, arap.code, arap.DebetBase, arap.CreditBase"; 
      }
      return "";
  }
}