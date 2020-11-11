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
use App\Core\Accounting\Entities\CashBank;
use App\Core\Master\Entities\BusinessPartner;
use App\AdminApi\Report\Services\ReportService;
use App\Core\Master\Entities\Company;

use Barryvdh\DomPDF\Facade as PDF;
use Barryvdh\Snappy\Facades\SnappyPdf;
use Carbon\Carbon;

class ReportCashBankController extends Controller
{
    protected $reportService;
    protected $reportName;

    public function __construct(ReportService $reportService)
    {
        $this->reportName = 'cashbank';
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

      $datefrom = Carbon::parse($request->input('DateStart'));
      $dateto = Carbon::parse($request->input('DateUntil'));

      // $firstDayOfMonth =$datefrom->modify('first day of this month');
      $firstDayOfMonth =Carbon::parse($request->input('DateStart'))->modify('first day of this month');

      
      if ($reportname == 'crosstab') {
        $query = "SELECT CONCAT(acb.Name,' - ',acb.Code) AS GroupName,
          sum((case when DATE_FORMAT(cb.Date,'%d')=1 then cb.TotalBase else 0 END)*(case when cb.Type=0 OR cb.Type=2 then 1 else -1 END)) AS d1,
          sum((case when DATE_FORMAT(cb.Date,'%d')=2 then cb.TotalBase else 0 END)*(case when cb.Type=0 OR cb.Type=2 then 1 else -1 END)) AS d2,
          sum((case when DATE_FORMAT(cb.Date,'%d')=3 then cb.TotalBase else 0 END)*(case when cb.Type=0 OR cb.Type=2 then 1 else -1 END)) AS d3,
          sum((case when DATE_FORMAT(cb.Date,'%d')=4 then cb.TotalBase else 0 END)*(case when cb.Type=0 OR cb.Type=2 then 1 else -1 END)) AS d4,
          sum((case when DATE_FORMAT(cb.Date,'%d')=5 then cb.TotalBase else 0 END)*(case when cb.Type=0 OR cb.Type=2 then 1 else -1 END)) AS d5,
          sum((case when DATE_FORMAT(cb.Date,'%d')=6 then cb.TotalBase else 0 END)*(case when cb.Type=0 OR cb.Type=2 then 1 else -1 END)) AS d6,
          sum((case when DATE_FORMAT(cb.Date,'%d')=7 then cb.TotalBase else 0 END)*(case when cb.Type=0 OR cb.Type=2 then 1 else -1 END)) AS d7,
          sum((case when DATE_FORMAT(cb.Date,'%d')=8 then cb.TotalBase else 0 END)*(case when cb.Type=0 OR cb.Type=2 then 1 else -1 END)) AS d8,
          sum((case when DATE_FORMAT(cb.Date,'%d')=9 then cb.TotalBase else 0 END)*(case when cb.Type=0 OR cb.Type=2 then 1 else -1 END)) AS d9,
          sum((case when DATE_FORMAT(cb.Date,'%d')=10 then cb.TotalBase else 0 END)*(case when cb.Type=0 OR cb.Type=2 then 1 else -1 END)) AS d10,
          sum((case when DATE_FORMAT(cb.Date,'%d')=11 then cb.TotalBase else 0 END)*(case when cb.Type=0 OR cb.Type=2 then 1 else -1 END)) AS d11,
          sum((case when DATE_FORMAT(cb.Date,'%d')=12 then cb.TotalBase else 0 END)*(case when cb.Type=0 OR cb.Type=2 then 1 else -1 END)) AS d12,
          sum((case when DATE_FORMAT(cb.Date,'%d')=13 then cb.TotalBase else 0 END)*(case when cb.Type=0 OR cb.Type=2 then 1 else -1 END)) AS d13,
          sum((case when DATE_FORMAT(cb.Date,'%d')=14 then cb.TotalBase else 0 END)*(case when cb.Type=0 OR cb.Type=2 then 1 else -1 END)) AS d14,
          sum((case when DATE_FORMAT(cb.Date,'%d')=15 then cb.TotalBase else 0 END)*(case when cb.Type=0 OR cb.Type=2 then 1 else -1 END)) AS d15,
          sum((case when DATE_FORMAT(cb.Date,'%d')=16 then cb.TotalBase else 0 END)*(case when cb.Type=0 OR cb.Type=2 then 1 else -1 END)) AS d16,
          sum((case when DATE_FORMAT(cb.Date,'%d')=17 then cb.TotalBase else 0 END)*(case when cb.Type=0 OR cb.Type=2 then 1 else -1 END)) AS d17,
          sum((case when DATE_FORMAT(cb.Date,'%d')=18 then cb.TotalBase else 0 END)*(case when cb.Type=0 OR cb.Type=2 then 1 else -1 END)) AS d18,
          sum((case when DATE_FORMAT(cb.Date,'%d')=19 then cb.TotalBase else 0 END)*(case when cb.Type=0 OR cb.Type=2 then 1 else -1 END)) AS d19,
          sum((case when DATE_FORMAT(cb.Date,'%d')=20 then cb.TotalBase else 0 END)*(case when cb.Type=0 OR cb.Type=2 then 1 else -1 END)) AS d20,
          sum((case when DATE_FORMAT(cb.Date,'%d')=21 then cb.TotalBase else 0 END)*(case when cb.Type=0 OR cb.Type=2 then 1 else -1 END)) AS d21,
          sum((case when DATE_FORMAT(cb.Date,'%d')=22 then cb.TotalBase else 0 END)*(case when cb.Type=0 OR cb.Type=2 then 1 else -1 END)) AS d22,
          sum((case when DATE_FORMAT(cb.Date,'%d')=23 then cb.TotalBase else 0 END)*(case when cb.Type=0 OR cb.Type=2 then 1 else -1 END)) AS d23,
          sum((case when DATE_FORMAT(cb.Date,'%d')=24 then cb.TotalBase else 0 END)*(case when cb.Type=0 OR cb.Type=2 then 1 else -1 END)) AS d24,
          sum((case when DATE_FORMAT(cb.Date,'%d')=25 then cb.TotalBase else 0 END)*(case when cb.Type=0 OR cb.Type=2 then 1 else -1 END)) AS d25,
          sum((case when DATE_FORMAT(cb.Date,'%d')=26 then cb.TotalBase else 0 END)*(case when cb.Type=0 OR cb.Type=2 then 1 else -1 END)) AS d26,
          sum((case when DATE_FORMAT(cb.Date,'%d')=27 then cb.TotalBase else 0 END)*(case when cb.Type=0 OR cb.Type=2 then 1 else -1 END)) AS d27,
          sum((case when DATE_FORMAT(cb.Date,'%d')=28 then cb.TotalBase else 0 END)*(case when cb.Type=0 OR cb.Type=2 then 1 else -1 END)) AS d28,
          sum((case when DATE_FORMAT(cb.Date,'%d')=29 then cb.TotalBase else 0 END)*(case when cb.Type=0 OR cb.Type=2 then 1 else -1 END)) AS d29,
          sum((case when DATE_FORMAT(cb.Date,'%d')=30 then cb.TotalBase else 0 END)*(case when cb.Type=0 OR cb.Type=2 then 1 else -1 END)) AS d30,
          sum((case when DATE_FORMAT(cb.Date,'%d')=31 then cb.TotalBase else 0 END)*(case when cb.Type=0 OR cb.Type=2 then 1 else -1 END)) AS d31
          FROM acccashbank cb
          LEFT OUTER JOIN accaccount acb ON cb.Account = acb.Oid
          WHERE cb.GCRecord IS NULL AND acb.GCRecord IS NULL
          AND cb.Date >= '".$datefrom->format('Y-m-d')."'
          AND cb.Date <= '".$dateto->format('Y-m-d')."'
          GROUP BY acb.Oid;";
        $data = DB::select($query);

      } else {
          $query = $this->query($reportname);
          $criteria1 = ""; $criteria2 = ""; $criteria3 = ""; $criteria4 = ""; $criteria5 = ""; $criteria6 = "";
          $filter=""; $criteria7 = "";
          
          $criteria1 = $criteria1." AND DATE_FORMAT(j.Date, '%Y-%m-%d') >= '".$datefrom->format('Y-m-d')."'";
          $filter = $filter."Date From = '".strtoupper($datefrom->format('Y-m-d'))."'; "; 
          $criteria1 = $criteria1." AND DATE_FORMAT(j.Date, '%Y-%m-%d') <= '".$dateto->format('Y-m-d')."'";
          $filter = $filter."Date To = '".strtoupper($dateto->format('Y-m-d'))."'; "; 

          $criteria7 = $criteria7." AND DATE_FORMAT(ac.Date, '%Y-%m-%d') >= '".$datefrom->format('Y-m-d')."'";
          $filter = $filter."Date From = '".strtoupper($datefrom->format('Y-m-d'))."'; "; 
          $criteria7 = $criteria7." AND DATE_FORMAT(ac.Date, '%Y-%m-%d') <= '".$dateto->format('Y-m-d')."'";
          $filter = $filter."Date To = '".strtoupper($dateto->format('Y-m-d'))."'; "; 

          $criteria2 = $criteria2." AND DATE_FORMAT(j.Date, '%Y-%m-%d') <= '".$datefrom->format('Y-m-d')."'";

          $criteria1 = $criteria1 . reportQueryCompany('acccashbank');
          if ($request->input('p_Company')) {
              $val = Company::findOrFail($request->input('p_Company'));
              $criteria1 = $criteria1 . " AND co.Oid = '" . $val->Oid . "'";
              $filter = $filter . " AND Company = '" . strtoupper($val->Name) . "'";
          }
          if ($request->input('p_Account')) {
              $val = Account::findOrFail($request->input('p_Account'));
              $criteria3 = $criteria3." AND acb.Oid = '".$val->Oid."'";
              $criteria4 = $criteria4." AND j.Account = '".$val->Oid."'";
              $filter = $filter."Account = '".strtoupper($val->Name)."'; ";
          }
          if ($request->input('p_AccountGroup')) {
            $val = AccountGroup::findOrFail($request->input('p_AccountGroup'));
            $criteria1 = $criteria1." AND a.AccountGroup = '".$val->Oid."'";
            $criteria2 = $criteria2." AND a.AccountGroup = '".$val->Oid."'";
            $criteria3 = $criteria3." AND a.AccountGroup = '".$val->Oid."'";
            $criteria4 = $criteria4." AND a.AccountGroup = '".$val->Oid."'";
            $criteria5 = $criteria5." AND a.AccountGroup = '".$val->Oid."'";
            $criteria6 = $criteria6." AND a.AccountGroup = '".$val->Oid."'";
            $filter = $filter."A. Group = '".strtoupper($val->Name)."'; ";
          }

          $criteria5 = $criteria5." AND DATE_FORMAT(cb.Date, '%Y-%m-%d') >= '".$datefrom->format('Y-m-d')."'";
          $criteria5 = $criteria5." AND DATE_FORMAT(cb.Date, '%Y-%m-%d') <= '".$dateto->format('Y-m-d')."'";

          $criteria6 = $criteria6." AND DATE_FORMAT(j.Date, '%Y-%m-%d') >= '".$firstDayOfMonth."'";
          $criteria6 = $criteria6." AND DATE_FORMAT(j.Date, '%Y-%m-%d') < '".$datefrom->format('Y-m-d')."'";

          if ($filter) $filter = substr($filter,0);
          if ($criteria1) $query = str_replace(" AND 1=1",$criteria1,$query);
          if ($criteria2) $query = str_replace(" AND 2=2",$criteria2,$query);
          if ($criteria3) $query = str_replace(" AND 3=3",$criteria3,$query);
          if ($criteria4) $query = str_replace(" AND 4=4",$criteria4,$query);
          if ($criteria5) $query = str_replace(" AND 5=5",$criteria5,$query);
          if ($criteria6) $query = str_replace(" AND 6=6",$criteria6,$query);
          if ($criteria7) $query = str_replace(" AND 7=7",$criteria7,$query);
      }

      $data = DB::select($query);

      switch ($reportname) {
        case 'cashbank1':
            $reporttitle = "Report CashBank Detail Order By Date";
            $pdf = SnappyPdf::loadView('AdminApi\Report::pdf.'.$reportname, compact('data', 'user', 'reportname','filter', 'reporttitle'));
          break;
          case 'cashbank2':
            $reporttitle = "Report CashBank Detail (Incl. Base Amt)";
            $pdf = SnappyPdf::loadView('AdminApi\Report::pdf.'.$reportname, compact('data', 'user', 'reportname','filter', 'reporttitle'))->setPaper('A4', 'landscape');
          break;
          case 'cashbank3':
            $reporttitle = "Report CashBank Summary (Incl. Base Amt)";
            $pdf = SnappyPdf::loadView('AdminApi\Report::pdf.'.$reportname, compact('data', 'user', 'reportname','filter', 'reporttitle'));
          break;
          case 'cashbank4':
            $reporttitle = "Report Credit Card";
            // return view('AdminApi\Report::pdf.'.$reportname, compact('data', 'user', 'reportname','filter', 'reporttitle'));
            $pdf = SnappyPdf::loadView('AdminApi\Report::pdf.'.$reportname, compact('data', 'user', 'reportname','filter', 'reporttitle'))->setPaper('A4', 'landscape');
        break;
        case 'cashbank5':
          $reporttitle = "Report Expense";
          $pdf = SnappyPdf::loadView('AdminApi\Report::pdf.'.$reportname, compact('data', 'user', 'reportname','filter', 'reporttitle'))->setPaper('A4', 'landscape');
        break;
        case 'cashbank6':
          $reporttitle = "Report CashBank by Daily";
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
              return "SELECT * FROM (SELECT
              co.Code AS Comp,
              'CompanyName' AS CompanyName,
              'CriteriaString' AS CriteriaString,
              3 AS Seq1, IF(j.DebetAmount > 0,4,3) AS Seq2,
              CONCAT(acb.Name, ' - ', acb.Code) AS AccountCashBank,
              CONCAT(a.Name, ' - ', a.Code) AS Account,
              j.Code AS Code,
              j.Date,
              j.Description,
              cur.Code AS Currency,
              IFNULL(j.CreditCashBank,0) AS DebetAmount,
              IFNULL(j.DebetCashBank,0) AS CreditAmount,
              IFNULL(j.CreditBase,0) AS DebetBase,
              IFNULL(j.DebetBase,0) AS CreditBase,
              j.Rate,
              jt.Code AS JournalType,
              bp.Name AS BusinessPartner,
              j.Status,
              bcur.Code AS basecurrency
            FROM accjournal j
              LEFT OUTER JOIN accaccount a ON a.Oid = j.Account
              LEFT OUTER JOIN sysaccounttype at ON at.Oid = a.AccountType
              LEFT OUTER JOIN sysjournaltype jt ON jt.Oid = j.JournalType
              LEFT OUTER JOIN acccashbank cb ON cb.Oid = j.CashBank
              LEFT OUTER JOIN accaccount acb ON acb.Oid = cb.Account
              LEFT OUTER JOIN mstcurrency cur ON cur.Oid = acb.Currency
              LEFT OUTER JOIN mstbusinesspartner bp ON bp.Oid = j.BusinessPartner
              LEFT OUTER JOIN company co ON j.Company = co.Oid
              LEFT OUTER JOIN mstcurrency bcur ON bcur.Oid = co.Currency 
            WHERE j.GCRecord IS NULL 
              AND jt.Code != 'OPEN' 
              AND jt.Code = 'CASH' 
              AND a.Oid != acb.Oid
              AND 1=1 AND 3=3
            UNION ALL
            SELECT 
              co.Code AS Comp,
              'CompanyName' AS CompanyName, 'CriteriaString' AS CriteriaString, 3 AS Seq1, IF(cb.TransferAmount > 0,3,4) AS Seq2,
              CONCAT(acb.Name, ' - ', acb.Code) AS AccountCashBank,
              CONCAT(a.Name, ' - ', a.Code) AS Account,
              cb.Code AS Code, cb.Date, 
              CONCAT('Transfer: ',a.Name, ' - ', a.Code) AS Account,
              cur.Code AS Currency,
              IFNULL(cb.TransferAmount,0) AS DebetAmount,
              0 AS CreditAmount,
              IFNULL(cb.TransferAmount * cb.TransferRateBase,0) AS DebetBase,
              0 AS CreditBase,
              cb.Rate, 'Transfer' AS JournalType, null AS BusinessPartner, cb.Status, bcur.Code AS basecurrency
            FROM acccashbank cb
              LEFT OUTER JOIN accaccount a ON a.Oid = cb.Account
              LEFT OUTER JOIN sysaccounttype at ON at.Oid = a.AccountType
              LEFT OUTER JOIN accaccount acb ON acb.Oid = cb.TransferAccount
              LEFT OUTER JOIN mstcurrency cur ON cur.Oid = acb.Currency
              LEFT OUTER JOIN company co ON cb.Company = co.Oid
              LEFT OUTER JOIN mstcurrency bcur ON bcur.Oid = co.Currency 
            WHERE cb.GCRecord IS NULL 
              AND cb.Type = 4
              AND 5=5 AND 3=3
            UNION ALL
            SELECT
              co.Code AS Comp,
              'CompanyName' AS CompanyName,
              'CriteriaString' AS CriteriaString,
              3 AS Seq1, IF(jt.Code = 'AUTO', 5, IF(j.DebetAmount > 0,3,4)) AS Seq2,
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
              LEFT OUTER JOIN mstcurrency cur ON cur.Oid = a.Currency
              LEFT OUTER JOIN mstbusinesspartner bp ON bp.Oid = j.BusinessPartner
              LEFT OUTER JOIN company co ON j.Company = co.Oid
              LEFT OUTER JOIN mstcurrency bcur ON bcur.Oid = co.Currency 
            WHERE j.GCRecord IS NULL 
              AND jt.Code != 'OPEN' 
              AND jt.Code != 'CASH' 
              AND at.Code IN ('CASH','BANK')
              AND 1=1 AND 4=4
            UNION ALL
            SELECT
              co.Code AS Comp,
              'CompanyName' AS CompanyName,
              'CriteriaString' AS CriteriaString,
              2 AS Seq1, 2 AS Seq2,
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
              '' AS BusinessPartner,
              '' AS Status,
              bcur.Code AS basecurrency
            FROM accjournal j
              LEFT OUTER JOIN accaccount a ON a.Oid = j.Account
              LEFT OUTER JOIN sysaccounttype at ON at.Oid = a.AccountType
              LEFT OUTER JOIN sysjournaltype jt ON jt.Oid = j.JournalType
              LEFT OUTER JOIN mstcurrency cur ON cur.Oid = a.Currency
              LEFT OUTER JOIN mstbusinesspartner bp ON bp.Oid = j.BusinessPartner
              LEFT OUTER JOIN company co ON j.Company = co.Oid
              LEFT OUTER JOIN mstcurrency bcur ON bcur.Oid = co.Currency 
            WHERE j.GCRecord IS NULL 
              AND jt.Code != 'OPEN' 
              AND at.Code IN ('CASH','BANK')
              AND 6=6 AND 4=4
            GROUP BY a.Code, a.Name, cur.Code, bcur.Code
            HAVING SUM(IFNULL(j.DebetBase,0)-IFNULL(j.CreditBase,0)) + SUM(IFNULL(j.DebetAmount,0)-IFNULL(j.CreditAmount,0)) != 0
            UNION ALL
            SELECT
              co.Code AS Comp,
              'CompanyName' AS CompanyName,
              'CriteriaString' AS CriteriaString,
              1 AS Seq1, 1 AS Seq2,
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
              '' AS BusinessPartner,
              '' AS Status,
              bcur.Code AS basecurrency
            FROM accjournal j
              LEFT OUTER JOIN accaccount a ON a.Oid = j.Account
              LEFT OUTER JOIN sysaccounttype at ON at.Oid = a.AccountType
              LEFT OUTER JOIN sysjournaltype jt ON jt.Oid = j.JournalType
              LEFT OUTER JOIN mstcurrency cur ON cur.Oid = a.Currency
              LEFT OUTER JOIN company co ON j.Company = co.Oid
              LEFT OUTER JOIN mstcurrency bcur ON bcur.Oid = co.Currency 
            WHERE j.GCRecord IS NULL 
              AND jt.Code = 'OPEN' 
              AND at.Code IN ('CASH','BANK')
              AND 2=2 AND 4=4
            GROUP BY a.Code, a.Name, cur.Code, bcur.Code 
            HAVING SUM(IFNULL(j.DebetBase,0)-IFNULL(j.CreditBase,0)) + SUM(IFNULL(j.DebetAmount,0)-IFNULL(j.CreditAmount,0)) != 0
            ) cashbank ORDER BY cashbank.AccountCashBank, cashbank.Date, cashbank.DebetBase, cashbank.CreditBase"; 
          case 'cashbank4':
            return "SELECT
              ac.Code AS LOAno,
              co.Code AS Comp,
              u.Name AS StaffName,
              DATE_FORMAT(ac.Date, '%e %b %Y') AS IssueDate,
              tt.Code AS TourCode,
              bp.Name AS SupplierName,
              bpg.Name AS SupplierGroup,
              a.Name AS CashBank,
              IFNULL(ac.TotalAmount,0) AS Amount,
              ac.Note AS Remark,
              ac.CodeReff AS FinRef,
              s.Name AS BillStatus
              
              FROM acccashbank ac
              LEFT OUTER JOIN acccashbankdetail acd ON ac.Oid = acd.CashBank
              LEFT OUTER JOIN accaccount a ON ac.Account = a.Oid
              LEFT OUTER JOIN trvtransactiondetail ttd ON acd.TravelTransactionDetail = ttd.Oid
              LEFT OUTER JOIN traveltransaction tt ON ttd.TravelTransaction = tt.Oid
              LEFT OUTER JOIN mstbusinesspartner bp ON ac.BusinessPartner = bp.Oid
              LEFT OUTER JOIN mstbusinesspartnergroup bpg ON bp.BusinessPartnerGroup = bpg.Oid
              LEFT OUTER JOIN company co ON ac.Company = co.Oid
              LEFT OUTER JOIN user u ON acd.CreatedBy = u.Oid
              LEFT OUTER JOIN sysstatus s ON ac.Status = s.Oid
              WHERE ac.GCRecord IS NULL AND 7=7";
          case 'cashbank5':
            return "SELECT
              p.Code AS TourCode,
              co.Code AS Comp,
              ity.Name AS Source,
              DATE_FORMAT(p.Date, '%e %b %Y') AS Date,
              ic.Name AS Description,
              'Account' AS Accountpay,
              cur.Code AS Currency,
              IFNULL(ttd.SalesTotal,0) AS GrossAmt,
              0 AS Gst,
              0 AS GstAmount,
              IFNULL(ttd.SalesTotal,0) AS NettAmount,
              ttd.SalesDescription AS Remarks,
              u.Name AS PaidBy,
              s.Code AS TourStatus
              
              FROM traveltransaction tt
              LEFT OUTER JOIN pospointofsale p ON tt.Oid = p.Oid
              LEFT OUTER JOIN trvtransactiondetail ttd ON ttd.TravelTransaction = tt.Oid
              LEFT OUTER JOIN acccashbankdetail acd ON ttd.Oid = acd.TravelTransactionDetail
              LEFT OUTER JOIN acccashbank ac ON acd.CashBank = ac.Oid  
              LEFT OUTER JOIN mstcurrency cur ON ac.Currency = cur.Oid
              LEFT OUTER JOIN mstbusinesspartner bp ON ttd.BusinessPartner = bp.Oid
              LEFT OUTER JOIN mstbusinesspartnergroup bpg ON bp.BusinessPartnerGroup = bpg.Oid
              LEFT OUTER JOIN mstitemcontent ic ON ttd.ItemContentSource = ic.Oid
              LEFT OUTER JOIN mstitemgroup ig ON ic.ItemGroup = ig.Oid
              LEFT OUTER JOIN sysitemtype ity ON ig.ItemType = ity.Oid
              LEFT OUTER JOIN user u ON ac.CreatedBy = u.Oid
              LEFT OUTER JOIN company co ON p.Company = co.Oid
              LEFT OUTER JOIN sysstatus s ON p.Status = s.Oid";
    }
      return "";
  }


}
