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
use App\Core\POS\Entities\PointOfSale;
use App\Core\Master\Entities\Company;
use App\Core\Master\Entities\Currency;
use App\AdminApi\Report\Services\ReportService;
use App\AdminApi\Development\Controllers\CRUDDevelopmentController;
use App\AdminApi\Development\Controllers\ReportGeneratorController;
use Barryvdh\Snappy\Facades\SnappyPdf;
use Carbon\Carbon;

class ReportTravelLOAController extends Controller
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
        $this->reportName = 'travelstock';
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
      // $data = DB::select($query);

      switch ($reportname) {
        case 'creditcardtransaction':
            $reporttitle = "Report Credit Card Transaction";
            $dataReport = (object) [
                "reporttitle" => 'Report Credit Card Transaction',
                "reportname" => $request->input('report'),
                "CompanyObj" => $user->CompanyObj,
            ];
            $data = DB::table('trdtransactionstock as data');
            $data = $data->whereRaw("DATE_FORMAT(data.Date, '%Y-%m-%d') >= '{$datefrom->format('Y-m-d')}'");
            $data = $data->whereRaw("DATE_FORMAT(data.Date, '%Y-%m-%d') <= '{$dateto->format('Y-m-d')}'");
            $data = $data->get();
            foreach ($data as $row) {
              $row = $this->crudController->detail('trvtransactiondetail', $row->Oid);
            }
            // return view('AdminApi\Report::travel.traveltransaction_'.$reportname, compact('data', 'dataReport','datefrom','dateto'));
            $pdf = SnappyPdf::loadView('AdminApi\Report::travel.travelloa_'.$reportname, compact('data', 'dataReport','datefrom','dateto'));
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
      return "";
  }


}
