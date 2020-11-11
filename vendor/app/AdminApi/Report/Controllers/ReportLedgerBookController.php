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
use App\Core\Internal\Entities\JournalType;
use App\Core\Master\Entities\BusinessPartner;
use App\Core\Master\Entities\Company;
use App\Core\Master\Entities\Currency;
use App\AdminApi\Report\Services\ReportService;
use App\AdminApi\Development\Controllers\CRUDDevelopmentController;
use App\AdminApi\Development\Controllers\ReportGeneratorController;
use Barryvdh\Snappy\Facades\SnappyPdf;
use Carbon\Carbon;

class ReportLedgerBookController extends Controller
{
    /** @var ReportService $reportService */
    protected $reportService;
    private $crudController;
    protected $reportName;
    private $reportGeneratorController;

    /**
     * @param ReportService $reportService
     * @return void
     */
    public function __construct(ReportService $reportService)
    {
        $this->reportName = 'ledgerbook';
        $this->reportService = $reportService;
        $this->crudController = new CRUDDevelopmentController();
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

    public function report(Request $request, $Oid = null)
    {
      $reportname = $request->input('report');
      $user = Auth::user();

      $query = $this->query($reportname);
      $criteria1 = ""; $criteria2 = ""; $criteria3 = ""; $criteria4 = ""; $criteria5 = ""; $criteria6 = "";  
      $filter="";
      
      $datefrom = Carbon::parse($request->input('DateStart'));
      $dateto = Carbon::parse($request->input('DateUntil'));
      // $firstDayOfMonth =$datefrom->modify('first day of this month');
      $firstDayOfMonth =Carbon::parse($request->input('DateStart'))->modify('first day of this month');


      
      $criteria1 = $criteria1." AND DATE_FORMAT(j.Date, '%Y-%m-%d') >= '".$datefrom->format('Y-m-d')."'";
      $filter = $filter."Date From = '".strtoupper($datefrom->format('Y-m-d'))."'; "; 
      $criteria1 = $criteria1." AND DATE_FORMAT(j.Date, '%Y-%m-%d') <= '".$dateto->format('Y-m-d')."'";
      $filter = $filter."Date To = '".strtoupper($dateto->format('Y-m-d'))."'; "; 

      $criteria2 = $criteria2." AND DATE_FORMAT(j.Date, '%Y-%m-%d') <= '".$firstDayOfMonth->format('Y-m-d')."'";

      $criteria3 = $criteria3." AND j.Date = '".$dateto->format('Y-m')."'";
      
      $criteria6 = $criteria6." AND DATE_FORMAT(j.Date, '%Y-%m-%d') >= '".$firstDayOfMonth->format('Y-m-d')."'";
      $criteria6 = $criteria6." AND DATE_FORMAT(j.Date, '%Y-%m-%d') < '".$datefrom->format('Y-m-d')."'";
  
      $criteria1 = $criteria1 . reportQueryCompany('accjournal');
      if ($request->input('p_Company')) {
          $val = Company::findOrFail($request->input('p_Company'));
          $criteria1 = $criteria1 . " AND co.Oid = '" . $val->Oid . "'";
          $filter = $filter . " AND Company = '" . strtoupper($val->Name) . "'";
      }
      if ($request->input('p_Account')) {
          $val = Account::findOrFail($request->input('p_Account'));
          $criteria1 = $criteria1." AND j.Account = '".$val->Oid."'";
          $criteria2 = $criteria2." AND j.Account = '".$val->Oid."'";
          $criteria3 = $criteria3." AND j.Account = '".$val->Oid."'";
          $criteria4 = $criteria4." AND j.Account = '".$val->Oid."'";
          $criteria5 = $criteria5." AND j.Account = '".$val->Oid."'";
          $criteria6 = $criteria6." AND j.Account = '".$val->Oid."'";
          $filter = $filter." AND Account = '".strtoupper($val->Name)."'";
      }
      if ($request->input('journaltype')) {
          $val = JournalType::findOrFail($request->input('journaltype'));
          $criteria1 = $criteria1." AND j.JournalType = '".$val->Oid."'";
          $criteria2 = $criteria2." AND j.JournalType = '".$val->Oid."'";
          $criteria3 = $criteria3." AND j.JournalType = '".$val->Oid."'";
          $criteria4 = $criteria4." AND j.JournalType = '".$val->Oid."'";
          $criteria5 = $criteria5." AND j.JournalType = '".$val->Oid."'";
          $criteria6 = $criteria6." AND j.JournalType = '".$val->Oid."'";
          $filter = $filter." AND JournalType = '".strtoupper($val->Name)."'";
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

      if ($filter) $filter = substr($filter,0);
      if ($criteria1) $query = str_replace(" AND 1=1",$criteria1,$query);
      if ($criteria2) $query = str_replace(" AND 2=2",$criteria2,$query);
      if ($criteria3) $query = str_replace(" AND 3=3",$criteria3,$query);
      if ($criteria4) $query = str_replace(" AND 4=4",$criteria4,$query);
      if ($criteria5) $query = str_replace(" AND 5=5",$criteria5,$query);
      if ($criteria6) $query = str_replace(" AND 6=6",$criteria6,$query);
      // logger($query);
      $data = DB::select($query);

      switch ($reportname) {
        case 'ledgerbook1':
            $reporttitle = "Report LedgerBook Journal List";
            $pdf = SnappyPdf::loadView('AdminApi\Report::pdf.'.$reportname, compact('data', 'user', 'reportname','filter', 'reporttitle'));
        break;
        case 'ledgerbook2':
            $reporttitle = "Report LedgerBook Detail (Potrait Vers)";
            $pdf = SnappyPdf::loadView('AdminApi\Report::pdf.'.$reportname, compact('data', 'user', 'reportname','filter', 'reporttitle'));
        break;
        case 'ledgerbook3':
            $reporttitle = "Report LedgerBook Detail (Landscape Vers)";
            $pdf = SnappyPdf::loadView('AdminApi\Report::pdf.'.$reportname, compact('data', 'user', 'reportname','filter', 'reporttitle'))->setPaper('A4', 'landscape');
        break;
        case 'ledgerbook4':
            $reporttitle = "Report LedgerBook Unposted Transaction Only";
            $pdf = SnappyPdf::loadView('AdminApi\Report::pdf.'.$reportname, compact('data', 'user', 'reportname','filter', 'reporttitle'));
        break;
        case 'bankledger':
          $reporttitle = "Report Bank Ledger";
          $dataReport = (object) [
              "reporttitle" => 'Report Bank Ledger',
              "reportname" => $request->input('report'),
              "CompanyObj" => $user->CompanyObj,
          ];
          $data = DB::table('accjournal as data');
          $data = $data->whereRaw("DATE_FORMAT(data.Date, '%Y-%m-%d') >= '{$datefrom->format('Y-m-d')}'");
          $data = $data->whereRaw("DATE_FORMAT(data.Date, '%Y-%m-%d') <= '{$dateto->format('Y-m-d')}'");
          $data = $data->get();
          foreach ($data as $row) {
            $row = $this->crudController->detail('accjournal', $row->Oid);
          }
          // return view('AdminApi\Report::pdf.ledgerbook_'.$reportname, compact('data', 'dataReport','datefrom','dateto'));
          $pdf = SnappyPdf::loadView('AdminApi\Report::pdf.ledgerbook_'.$reportname, compact('data', 'dataReport','datefrom','dateto'));
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
          case 'ledgerbook1':
              return "SELECT * FROM (SELECT
              co.Code AS Comp,
              'CompanyName' AS CompanyName,
              'CriteriaString' AS CriteriaString,
              a.Code AS AccountCode,
              a.Name AS Account,
              j.Code AS Code,
              j.Date,
              CONCAT(a.Name,' (',a.Code,') - ',IFNULL(j.Description,''), IF(j.Currency != co.Currency, CONCAT(' ',cur.Code, ' ',FORMAT(j.DebetAmount + j.CreditAmount,cur.Decimal),' x ',FORMAT(j.Rate,0)), '') ) AS Description,
              cur.Code AS Currency,
              j.DebetAmount,
              j.CreditAmount,
              j.DebetBase,
              j.CreditBase,
              j.Rate,
              jt.Name AS JournalType,
              bp.Name AS BusinessPartner,
              j.Status,
              cur.Code AS basecurrency,
              j.Source,
              IF(j.Currency != co.Currency, CONCAT(cur.Code, ' ',FORMAT(j.DebetAmount + j.CreditAmount,cur.Decimal),' x ',FORMAT(j.Rate,0)), '') AS AmountDescription
            FROM accjournal j
              LEFT OUTER JOIN sysjournaltype jt ON jt.Oid = j.JournalType
              LEFT OUTER JOIN mstcurrency cur ON cur.Oid = j.Currency
              LEFT OUTER JOIN mstbusinesspartner bp ON bp.Oid = j.BusinessPartner
              LEFT OUTER JOIN accaccount a ON a.Oid = j.Account
              LEFT OUTER JOIN company co ON j.Company = co.Oid
              LEFT OUTER JOIN mstcurrency bcur ON bcur.Oid = co.Currency  
            WHERE j.GCRecord IS NULL AND jt.Code NOT IN ('OPEN','PL') AND 1=1) ledgerbook GROUP BY ledgerbook.Date, ledgerbook.Code"; 
          break;
          case 'ledgerbook2':
          case 'ledgerbook3':
          case 'ledgerbook4':
              return "SELECT * FROM (SELECT
              co.Code AS Comp,
              0 AS Seq1, 0 AS Seq2,
              'CompanyName' AS CompanyName,
              'CriteriaString' AS CriteriaString,
              a.Code AS AccountCode,
              a.Name AS Account,
              '' AS Code,
              date_add(j.Date,interval -DAY(j.Date)+1 DAY) AS Date,
              'Saldo awal bulan' AS Description,
              cur.Code AS Currency,
              IF(SUM(IFNULL(j.DebetAmount,0)-IFNULL(j.CreditAmount,0)) > 0, SUM(IFNULL(j.DebetAmount,0)-IFNULL(j.CreditAmount,0)), 0) AS DebetAmount,
              IF(SUM(IFNULL(j.DebetAmount,0)-IFNULL(j.CreditAmount,0)) < 0, SUM(IFNULL(j.DebetAmount,0)-IFNULL(j.CreditAmount,0))*-1, 0) AS CreditAmount,
              IF(SUM(IFNULL(j.DebetBase,0)-IFNULL(j.CreditBase,0)) > 0, SUM(IFNULL(j.DebetBase,0)-IFNULL(j.CreditBase,0)), 0) AS DebetBase,
              IF(SUM(IFNULL(j.DebetBase,0)-IFNULL(j.CreditBase,0)) < 0, SUM(IFNULL(j.DebetBase,0)-IFNULL(j.CreditBase,0))*-1, 0) AS CreditBase,
              '' AS Rate,
              '' AS JournalType,
              '' AS BusinessPartner,
              '' AS Status,
              cur.Code AS basecurrency,
              '' AS Source,
              'Opening Balance' AS AmountDescription
            FROM accjournal j
              LEFT OUTER JOIN sysjournaltype jt ON jt.Oid = j.JournalType
              LEFT OUTER JOIN mstcurrency cur ON cur.Oid = j.Currency
              LEFT OUTER JOIN mstbusinesspartner bp ON bp.Oid = j.BusinessPartner
              LEFT OUTER JOIN accaccount a ON a.Oid = j.Account
              LEFT OUTER JOIN company co ON j.Company = co.Oid
              LEFT OUTER JOIN mstcurrency bcur ON bcur.Oid = co.Currency  
            WHERE j.GCRecord IS NULL AND jt.Code = 'OPEN' AND 2=2  
              GROUP BY a.Code, a.Name, cur.Code
              HAVING SUM(IFNULL(j.DebetBase,0)-IFNULL(j.CreditBase,0)) + SUM(IFNULL(j.DebetAmount,0)-IFNULL(j.CreditAmount,0)) != 0
            UNION ALL
            SELECT
            co.Code AS Comp,
              0 AS Seq1, 0 AS Seq2,
              'CompanyName' AS CompanyName,
              'CriteriaString' AS CriteriaString,
              a.Code AS AccountCode,
              a.Name AS Account,
              '' AS Code,
              date_add(j.Date,interval -DAY(j.Date)+1 DAY) AS Date,
              CONCAT('Transaksi bulan ', DATE_FORMAT(j.Date, '%b %Y')) AS Description,
              cur.Code AS Currency,
              IF(SUM(IFNULL(j.DebetAmount,0)-IFNULL(j.CreditAmount,0)) > 0, SUM(IFNULL(j.DebetAmount,0)-IFNULL(j.CreditAmount,0)), 0) AS DebetAmount,
              IF(SUM(IFNULL(j.DebetAmount,0)-IFNULL(j.CreditAmount,0)) < 0, SUM(IFNULL(j.DebetAmount,0)-IFNULL(j.CreditAmount,0))*-1, 0) AS CreditAmount,
              IF(SUM(IFNULL(j.DebetBase,0)-IFNULL(j.CreditBase,0)) > 0, SUM(IFNULL(j.DebetBase,0)-IFNULL(j.CreditBase,0)), 0) AS DebetBase,
              IF(SUM(IFNULL(j.DebetBase,0)-IFNULL(j.CreditBase,0)) < 0, SUM(IFNULL(j.DebetBase,0)-IFNULL(j.CreditBase,0))*-1, 0) AS CreditBase,
              '' AS Rate,
              '' AS JournalType,
              '' AS BusinessPartner,
              '' AS Status,
              cur.Code AS basecurrency,
              '' AS Source,
              'Opening Balance' AS AmountDescription
            FROM accjournal j
              LEFT OUTER JOIN sysjournaltype jt ON jt.Oid = j.JournalType
              LEFT OUTER JOIN mstcurrency cur ON cur.Oid = j.Currency
              LEFT OUTER JOIN mstbusinesspartner bp ON bp.Oid = j.BusinessPartner
              LEFT OUTER JOIN accaccount a ON a.Oid = j.Account
              LEFT OUTER JOIN company co ON j.Company = co.Oid
              LEFT OUTER JOIN mstcurrency bcur ON bcur.Oid = co.Currency  
            WHERE j.GCRecord IS NULL AND jt.Code NOT IN ('OPEN','PL') AND 6=6  
              GROUP BY a.Code, a.Name, cur.Code
              HAVING SUM(IFNULL(j.DebetBase,0)-IFNULL(j.CreditBase,0)) + SUM(IFNULL(j.DebetAmount,0)-IFNULL(j.CreditAmount,0)) != 0
            UNION ALL
            SELECT
            co.Code AS Comp,
              1 AS Seq1, IF(j.DebetAmount > 0,2,3) AS Seq2,
              'CompanyName' AS CompanyName,
              'CriteriaString' AS CriteriaString,
              a.Code AS AccountCode,
              a.Name AS Account,
              j.Code AS Code,
              j.Date,
            CONCAT(IFNULL(j.Description,''),' ',IFNULL(bp.Name,'')) AS Description,
              cur.Code AS Currency,
              IFNULL(j.DebetAmount,0) AS DebetAmount,
              IFNULL(j.CreditAmount,0) AS CreditAmount,
              IFNULL(j.DebetBase,0) AS DebetBase,
              IFNULL(j.CreditBase,0) AS CreditBase,
              IFNULL(j.Rate,0) AS Rate,
              jt.Name AS JournalType,
              bp.Name AS BusinessPartner,
              j.Status,
              cur.Code AS basecurrency,
              j.Source,
              IF(j.Currency != co.Currency, CONCAT(cur.Code, ' ',FORMAT(j.DebetAmount + j.CreditAmount,cur.Decimal),' x ',FORMAT(j.Rate,0)), '') AS AmountDescription
            FROM accjournal j
              LEFT OUTER JOIN sysjournaltype jt ON jt.Oid = j.JournalType
              LEFT OUTER JOIN mstcurrency cur ON cur.Oid = j.Currency
              LEFT OUTER JOIN mstbusinesspartner bp ON bp.Oid = j.BusinessPartner
              LEFT OUTER JOIN accaccount a ON a.Oid = j.Account
              LEFT OUTER JOIN company co ON j.Company = co.Oid
              LEFT OUTER JOIN mstcurrency bcur ON bcur.Oid = co.Currency  
            WHERE j.GCRecord IS NULL AND jt.Code NOT IN ('OPEN','PL') AND 1=1
            ) ledgerbook ORDER BY ledgerbook.AccountCode, ledgerbook.Account, ledgerbook.Date, ledgerbook.DebetAmount, ledgerbook.CreditAmount
            ";
            break;
            case 'bankledger':
              return "SELECT
              ac.Type,
              co.Image AS CompanyLogo,
              co.LogoPrint AS LogoPrint,
              co.Name AS CompanyName,
              co.FullAddress AS CompanyAddress,
              co.PhoneNo AS CompanyPhone,
              co.Email AS CompanyEmail,
              bp.Name AS BusinessPartner,
              bp.FullAddress,
              p.Name AS Project,
              DATE_FORMAT(ac.Date, '%b %e %Y') AS Date,
              ac.Code AS CashBankCode,
              ac.CodeReff,
              ac.TotalAmount,
              ac.TotalAmountWording,
              ac.Note AS Note,
              si.Code AS InvoiceNumber,
              a.Name AS AccountName,
              c.Code AS CurrencyCode,
              c.Name AS CurrencyName,
              IFNULL(acd.AmountCashbank,0) AS Amount,
              IFNULL(acd.Rate,0) AS Rate, b.Name AS BankCode,
              acd.Description AS `Description`,
              u.Name AS Receivedby

              FROM acccashbank ac 
              LEFT OUTER JOIN acccashbankdetail acd ON ac.Oid = acd.CashBank
              LEFT OUTER JOIN accaccount a ON ac.Account = a.Oid
              LEFT OUTER JOIN trdsalesinvoice si ON si.Oid = acd.SalesInvoice
              LEFT OUTER JOIN mstcurrency c ON ac.Currency = c.Oid
              LEFT OUTER JOIN mstbusinesspartner bp ON ac.BusinessPartner = bp.Oid
              LEFT OUTER JOIN company co ON ac.Company = co.Oid
              LEFT OUTER JOIN user u ON ac.CreatedBy = u.Oid
              LEFT OUTER JOIN mstbank b ON a.Bank = b.Oid
              LEFT OUTER JOIN mstproject p ON ac.Project = p.Oid
              WHERE ac.GCRecord IS NULL
              ";
      }
      return "";
  }


}
