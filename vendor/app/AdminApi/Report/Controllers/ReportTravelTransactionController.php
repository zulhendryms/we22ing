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

class ReportTravelTransactionController extends Controller
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
        $this->reportName = 'traveltransaction';
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

      // $query = $this->query($reportname);
      $criteria1 = ""; $criteria2 = ""; $criteria3 = ""; $criteria4 = ""; $criteria5 = ""; $criteria6 = "";  
      $filter="";
      
      $datefrom = Carbon::parse($request->input('DateStart'));
      $dateto = Carbon::parse($request->input('DateUntil'));
      // $firstDayOfMonth =$datefrom->modify('first day of this month');
      // $firstDayOfMonth =Carbon::parse($request->input('DateStart'))->modify('first day of this month');


      
      $criteria1 = $criteria1." AND DATE_FORMAT(tt.DateFrom, '%Y-%m-%d') >= '".$datefrom->format('Y-m-d')."'";
      $filter = $filter."Date From = '".strtoupper($datefrom->format('Y-m-d'))."'; "; 
      $criteria1 = $criteria1." AND DATE_FORMAT(tt.DateFrom, '%Y-%m-%d') <= '".$dateto->format('Y-m-d')."'";
      $filter = $filter."Date To = '".strtoupper($dateto->format('Y-m-d'))."'; "; 

      // $criteria2 = $criteria2." AND DATE_FORMAT(j.Date, '%Y-%m-%d') <= '".$firstDayOfMonth->format('Y-m-d')."'";

      // $criteria3 = $criteria3." AND j.Date = '".$dateto->format('Y-m')."'";
      
      // $criteria6 = $criteria6." AND DATE_FORMAT(j.Date, '%Y-%m-%d') >= '".$firstDayOfMonth->format('Y-m-d')."'";
      // $criteria6 = $criteria6." AND DATE_FORMAT(j.Date, '%Y-%m-%d') < '".$datefrom->format('Y-m-d')."'";

      // if ($filter) $filter = substr($filter,0);
      // if ($criteria1) $query = str_replace(" AND 1=1",$criteria1,$query);
      // if ($criteria2) $query = str_replace(" AND 2=2",$criteria2,$query);
      // if ($criteria3) $query = str_replace(" AND 3=3",$criteria3,$query);
      // if ($criteria4) $query = str_replace(" AND 4=4",$criteria4,$query);
      // if ($criteria5) $query = str_replace(" AND 5=5",$criteria5,$query);
      // if ($criteria6) $query = str_replace(" AND 6=6",$criteria6,$query);
      // logger($query);
      // $data = DB::select($query);

      switch ($reportname) {
        case 'outstandingtravel':
            $reporttitle = "Report Outstanding TravelTransaction";
            $dataReport = (object) [
                "reporttitle" => 'Report Outstanding TravelTransaction',
                "reportname" => $request->input('report'),
                "CompanyObj" => $user->CompanyObj,
            ];
            // $data = DB::table('pospointofsale as data')
            //   ->leftJoin('traveltransaction AS TravelTransaction', 'TravelTransaction.Oid', '=', 'data.Oid')
            //   ->whereIsNull('GCRecord')
            //   ->whereNotExists(function($query){
            //       $query->select(DB::table('trdsalesinvoice as SalesInvoice'))
            //       ->where('data.Oid', '=', 'SalesInvoice.PointOfSale');
            //   })->limit(5);
              $query = "SELECT 
                  p.Oid, p.Code,c.name Company, DATE_FORMAT(tt.DateFrom, '%d/%m/%y') AS DateFrom,
                  DATE_FORMAT(tt.DateUntil, '%d/%m/%y') AS DateUntil, bp.Name Customer,
                  tt.QtyTotalPax, tt.AmountTourFareTotal, s.Name Status
                  FROM pospointofsale p
                  LEFT OUTER JOIN traveltransaction tt ON p.Oid = tt.Oid
                  LEFT OUTER JOIN trvtransactiondetail ttd ON tt.Oid = ttd.TravelTransaction
                  LEFT OUTER JOIN company c ON c.Oid = p.Company
                  LEFT OUTER JOIN mstbusinesspartner bp ON p.Customer = bp.Oid
                  LEFT OUTER JOIN sysstatus s ON p.Status = s.Oid
                  WHERE p.GCRecord IS NULL {$criteria1} AND NOT EXISTS(SELECT * FROM trdsalesinvoice si WHERE si.PointOfSale = p.Oid)";
             $data = DB::select($query);
           
            // return view('AdminApi\Report::travel.traveltransaction_'.$reportname, compact('data', 'dataReport','datefrom','dateto'));
            $pdf = SnappyPdf::loadView('AdminApi\Report::travel.traveltransaction_'.$reportname, compact('data', 'dataReport','datefrom','dateto'));
        break;
        case 'git':
            $reporttitle = "Report Transaction GIT";
            $dataReport = (object) [
                "reporttitle" => 'Report Transaction GIT',
                "reportname" => $request->input('report'),
                "CompanyObj" => $user->CompanyObj,
            ];
            $data = DB::table('pospointofsale as data');
            $data = $data->leftJoin('traveltransaction AS TravelTransaction', 'TravelTransaction.Oid', '=', 'data.Oid');
            $data = $data->leftJoin('trvtraveltype AS TravelType', 'TravelType.Oid', '=', 'TravelTransaction.TravelType');
            $data = $data->whereRaw("DATE_FORMAT(data.Date, '%Y-%m-%d') >= '{$datefrom->format('Y-m-d')}'");
            $data = $data->whereRaw("DATE_FORMAT(data.Date, '%Y-%m-%d') <= '{$dateto->format('Y-m-d')}'");
            $data = $data->where('TravelType.Code','GIT');
            $data = $data->pluck('data.Oid');
            $data = PointOfSale::whereIn('Oid',$data)->get();
           
            // return view('AdminApi\Report::travel.traveltransaction_'.$reportname, compact('data', 'dataReport','datefrom','dateto'));
            $pdf = SnappyPdf::loadView('AdminApi\Report::travel.traveltransaction_'.$reportname, compact('data', 'dataReport','datefrom','dateto'))->setPaper('A4', 'landscape');
        break;
        case 'fit':
            $reporttitle = "Report Transaction FIT";
            $dataReport = (object) [
                "reporttitle" => 'Report Transaction FIT',
                "reportname" => $request->input('report'),
                "CompanyObj" => $user->CompanyObj,
            ];
            $data = DB::table('pospointofsale as data');
            $data = $data->leftJoin('traveltransaction AS TravelTransaction', 'TravelTransaction.Oid', '=', 'data.Oid');
            $data = $data->leftJoin('trvtraveltype AS TravelType', 'TravelType.Oid', '=', 'TravelTransaction.TravelType');
            $data = $data->whereRaw("DATE_FORMAT(data.Date, '%Y-%m-%d') >= '{$datefrom->format('Y-m-d')}'");
            $data = $data->whereRaw("DATE_FORMAT(data.Date, '%Y-%m-%d') <= '{$dateto->format('Y-m-d')}'");
            $data = $data->where('TravelType.Code','FIT');
            $data = $data->pluck('data.Oid');
            $data = PointOfSale::whereIn('Oid',$data)->get();
           
            // return view('AdminApi\Report::travel.traveltransaction_'.$reportname, compact('data', 'dataReport','datefrom','dateto'));
            $pdf = SnappyPdf::loadView('AdminApi\Report::travel.traveltransaction_'.$reportname, compact('data', 'dataReport','datefrom','dateto'))->setPaper('A4', 'landscape');
        break;
        case 'gitinterfacelist':
            $reporttitle = "REPORT GIT INTERFACE LIST";
            $dataReport = (object) [
                "reporttitle" => 'REPORT GIT INTERFACE LIST',
                "reportname" => $request->input('report'),
                "CompanyObj" => $user->CompanyObj,
            ];
            $data = DB::table('trvtransactiondetail as data');
            $data = $data->leftJoin('traveltransaction AS TravelTransaction', 'TravelTransaction.Oid', '=', 'data.TravelTransaction');
            $data = $data->leftJoin('trvtraveltype AS TravelType', 'TravelType.Oid', '=', 'TravelTransaction.TravelType');
            $data = $data->whereRaw("DATE_FORMAT(data.Date, '%Y-%m-%d') >= '{$datefrom->format('Y-m-d')}'");
            $data = $data->whereRaw("DATE_FORMAT(data.Date, '%Y-%m-%d') <= '{$dateto->format('Y-m-d')}'");
            $data = $data->where('TravelType.Code','GIT');
            $data = $data->get();
            foreach ($data as $row) {
              $row = $this->crudController->detail('trvtransactiondetail', $row->Oid);
            }
            // return view('AdminApi\Report::travel.traveltransaction_'.$reportname, compact('data', 'dataReport','datefrom','dateto'));
            $pdf = SnappyPdf::loadView('AdminApi\Report::travel.traveltransaction_'.$reportname, compact('data', 'dataReport','datefrom','dateto'));
        break;
        case 'fitinterfacelist':
            $reporttitle = "REPORT FIT INTERFACE LIST";
            $dataReport = (object) [
                "reporttitle" => 'REPORT FIT INTERFACE LIST',
                "reportname" => $request->input('report'),
                "CompanyObj" => $user->CompanyObj,
            ];
            $data = DB::table('trvtransactiondetail as data');
            $data = $data->leftJoin('traveltransaction AS TravelTransaction', 'TravelTransaction.Oid', '=', 'data.TravelTransaction');
            $data = $data->leftJoin('trvtraveltype AS TravelType', 'TravelType.Oid', '=', 'TravelTransaction.TravelType');
            $data = $data->whereRaw("DATE_FORMAT(data.Date, '%Y-%m-%d') >= '{$datefrom->format('Y-m-d')}'");
            $data = $data->whereRaw("DATE_FORMAT(data.Date, '%Y-%m-%d') <= '{$dateto->format('Y-m-d')}'");
            $data = $data->where('TravelType.Code','FIT');
            $data = $data->get();
            foreach ($data as $row) {
              $row = $this->crudController->detail('trvtransactiondetail', $row->Oid);
            }
            // return view('AdminApi\Report::travel.traveltransaction_'.$reportname, compact('data', 'dataReport','datefrom','dateto'));
            $pdf = SnappyPdf::loadView('AdminApi\Report::travel.traveltransaction_'.$reportname, compact('data', 'dataReport','datefrom','dateto'));
        break;
        case 'acc_otherincome':
            $reporttitle = "REPORT OTHER INCOME EXPENSES";
            $dataReport = (object) [
                "reporttitle" => 'REPORT OTHER INCOME EXPENSES',
                "reportname" => $request->input('report'),
                "CompanyObj" => $user->CompanyObj,
            ];
            $data = DB::table('pospointofsale as data');
            $data = $data->leftJoin('traveltransaction AS TravelTransaction', 'TravelTransaction.Oid', '=', 'data.Oid');
            $data = $data->leftJoin('trvtransactiondetail AS TravelTransactionDetail', 'TravelTransaction.Oid', '=', 'TravelTransactionDetail.TravelTransaction');
            $data = $data->whereRaw("DATE_FORMAT(data.Date, '%Y-%m-%d') >= '{$datefrom->format('Y-m-d')}'");
            $data = $data->whereRaw("DATE_FORMAT(data.Date, '%Y-%m-%d') <= '{$dateto->format('Y-m-d')}'");
            $data = $data->get();
            foreach ($data as $row) {
              $row = $this->crudController->detail('trvtransactiondetail', $row->Oid);
            }
            // return view('AdminApi\Report::travel.traveltransaction_'.$reportname, compact('data', 'dataReport','datefrom','dateto'));
            $pdf = SnappyPdf::loadView('AdminApi\Report::travel.traveltransaction_'.$reportname, compact('data', 'dataReport','datefrom','dateto'));
        break;
        case 'gitexpenseslist':
            $reporttitle = "REPORT GIT EXPENSES LIST";
            $dataReport = (object) [
                "reporttitle" => 'REPORT GIT EXPENSES LIST',
                "reportname" => $request->input('report'),
                "CompanyObj" => $user->CompanyObj,
            ];
            $data = DB::table('pospointofsale as data');
            $data = $data->leftJoin('traveltransaction AS TravelTransaction', 'TravelTransaction.Oid', '=', 'data.Oid');
            $data = $data->leftJoin('trvtransactiondetail AS TravelTransactionDetail', 'TravelTransaction.Oid', '=', 'TravelTransactionDetail.TravelTransaction');
            $data = $data->leftJoin('trvtraveltype AS TravelType', 'TravelType.Oid', '=', 'TravelTransaction.TravelType');
            $data = $data->whereRaw("DATE_FORMAT(data.Date, '%Y-%m-%d') >= '{$datefrom->format('Y-m-d')}'");
            $data = $data->whereRaw("DATE_FORMAT(data.Date, '%Y-%m-%d') <= '{$dateto->format('Y-m-d')}'");
            $data = $data->where('TravelType.Code','GIT');
            $data = $data->get();
            foreach ($data as $row) {
              $row = $this->crudController->detail('trvtransactiondetail', $row->Oid);
            }
            // return view('AdminApi\Report::travel.traveltransaction_'.$reportname, compact('data', 'dataReport','datefrom','dateto'));
            $pdf = SnappyPdf::loadView('AdminApi\Report::travel.traveltransaction_'.$reportname, compact('data', 'dataReport','datefrom','dateto'));
        break;
        case 'fitexpenseslist':
            $reporttitle = "REPORT FIT EXPENSES LIST";
            $dataReport = (object) [
                "reporttitle" => 'REPORT FIT EXPENSES LIST',
                "reportname" => $request->input('report'),
                "CompanyObj" => $user->CompanyObj,
            ];
            $data = DB::table('pospointofsale as data');
            $data = $data->leftJoin('traveltransaction AS TravelTransaction', 'TravelTransaction.Oid', '=', 'data.Oid');
            $data = $data->leftJoin('trvtransactiondetail AS TravelTransactionDetail', 'TravelTransaction.Oid', '=', 'TravelTransactionDetail.TravelTransaction');
            $data = $data->leftJoin('trvtraveltype AS TravelType', 'TravelType.Oid', '=', 'TravelTransaction.TravelType');
            $data = $data->whereRaw("DATE_FORMAT(data.Date, '%Y-%m-%d') >= '{$datefrom->format('Y-m-d')}'");
            $data = $data->whereRaw("DATE_FORMAT(data.Date, '%Y-%m-%d') <= '{$dateto->format('Y-m-d')}'");
            $data = $data->where('TravelType.Code','FIT');
            $data = $data->get();
            foreach ($data as $row) {
              $row = $this->crudController->detail('trvtransactiondetail', $row->Oid);
            }
            // return view('AdminApi\Report::travel.traveltransaction_'.$reportname, compact('data', 'dataReport','datefrom','dateto'));
            $pdf = SnappyPdf::loadView('AdminApi\Report::travel.traveltransaction_'.$reportname, compact('data', 'dataReport','datefrom','dateto'));
          break;
          case 'bankledgerhotel':
            $reporttitle = "REPORT BANK LEDGER HOTEL";
            $dataReport = (object) [
                "reporttitle" => 'REPORT BANK LEDGER HOTEL',
                "reportname" => $request->input('report'),
                "CompanyObj" => $user->CompanyObj,
            ];
            $data = DB::table('trvtransactiondetail as data');
            $data = $data->leftJoin('mstbusinesspartner AS BusinessPartner', 'BusinessPartner.Oid', '=', 'data.BusinessPartner');
            $data = $data->whereRaw("DATE_FORMAT(data.Date, '%Y-%m-%d') >= '{$datefrom->format('Y-m-d')}'");
            $data = $data->whereRaw("DATE_FORMAT(data.Date, '%Y-%m-%d') <= '{$dateto->format('Y-m-d')}'");
            $data = $data->where('OrderType','Hotel');
            $data = $data->get();
            // return view('AdminApi\Report::travel.traveltransaction_'.$reportname, compact('data', 'dataReport','datefrom','dateto'));
            $pdf = SnappyPdf::loadView('AdminApi\Report::travel.traveltransaction_'.$reportname, compact('data', 'dataReport','datefrom','dateto'));
          break;
          case 'roomreservation':
            $reporttitle = "REPORT ROOM RESERVATION SALES SUMMARY";
            $dataReport = (object) [
                "reporttitle" => 'REPORT ROOM RESERVATION SALES SUMMARY',
                "reportname" => $request->input('report'),
                "CompanyObj" => $user->CompanyObj,
            ];
            $data = DB::table('trvtransactiondetail as data');
            $data = $data->leftJoin('traveltransaction AS TravelTransaction', 'TravelTransaction.Oid', '=', 'data.TravelTransaction');
            $data = $data->leftJoin('mstbusinesspartner AS BusinessPartner', 'BusinessPartner.Oid', '=', 'data.BusinessPartner');
            $data = $data->whereRaw("DATE_FORMAT(data.Date, '%Y-%m-%d') >= '{$datefrom->format('Y-m-d')}'");
            $data = $data->whereRaw("DATE_FORMAT(data.Date, '%Y-%m-%d') <= '{$dateto->format('Y-m-d')}'");
            $data = $data->get();
            // return view('AdminApi\Report::travel.traveltransaction_'.$reportname, compact('data', 'dataReport','datefrom','dateto'));
            $pdf = SnappyPdf::loadView('AdminApi\Report::travel.traveltransaction_'.$reportname, compact('data', 'dataReport','datefrom','dateto'))->setPaper('A4', 'landscape');
          break;
          case 'exchangeorderlist':
            $reporttitle = "REPORT EXCHANGE ORDER LIST";
            $dataReport = (object) [
                "reporttitle" => 'REPORT EXCHANGE ORDER LIST',
                "reportname" => $request->input('report'),
                "CompanyObj" => $user->CompanyObj,
            ];
            $data = DB::table('trvtransactiondetail as data');
            $data = $data->whereRaw("DATE_FORMAT(data.Date, '%Y-%m-%d') >= '{$datefrom->format('Y-m-d')}'");
            $data = $data->whereRaw("DATE_FORMAT(data.Date, '%Y-%m-%d') <= '{$dateto->format('Y-m-d')}'");
            $data = $data->get();
            // return view('AdminApi\Report::travel.traveltransaction_'.$reportname, compact('data', 'dataReport','datefrom','dateto'));
            $pdf = SnappyPdf::loadView('AdminApi\Report::travel.traveltransaction_'.$reportname, compact('data', 'dataReport','datefrom','dateto'));
          break;
          case 'attractionsummary':
            $reporttitle = "REPORT ATTRACTION SUMMARY";
            $dataReport = (object) [
                "reporttitle" => 'REPORT ATTRACTION SUMMARY',
                "reportname" => $request->input('report'),
                "CompanyObj" => $user->CompanyObj,
            ];
            $data = DB::table('trvtransactiondetail as data');
            $data = $data->leftJoin('traveltransaction AS TravelTransaction', 'TravelTransaction.Oid', '=', 'data.Oid');
            $data = $data->leftJoin('trvtransactionflight AS TransactionFlight', 'TransactionFlight.Oid', '=', 'TravelTransaction.Oid');
            $data = $data->whereRaw("DATE_FORMAT(data.Date, '%Y-%m-%d') >= '{$datefrom->format('Y-m-d')}'");
            $data = $data->whereRaw("DATE_FORMAT(data.Date, '%Y-%m-%d') <= '{$dateto->format('Y-m-d')}'");
            $data = $data->where('OrderType','Attraction');
            $data = $data->get();
            // return view('AdminApi\Report::travel.traveltransaction_'.$reportname, compact('data', 'dataReport','datefrom','dateto'));
            $pdf = SnappyPdf::loadView('AdminApi\Report::travel.traveltransaction_'.$reportname, compact('data', 'dataReport','datefrom','dateto'));
          break;
          case 'overseas_git':
            $reporttitle = "REPORT OVERSEAS - GIT";
            $dataReport = (object) [
                "reporttitle" => 'REPORT OVERSEAS - GIT',
                "reportname" => $request->input('report'),
                "CompanyObj" => $user->CompanyObj,
            ];
            $data = DB::table('trvtransactiondetail as data');
            $data = $data->leftJoin('traveltransaction AS TravelTransaction', 'TravelTransaction.Oid', '=', 'data.Oid');
            $data = $data->whereRaw("DATE_FORMAT(data.Date, '%Y-%m-%d') >= '{$datefrom->format('Y-m-d')}'");
            $data = $data->whereRaw("DATE_FORMAT(data.Date, '%Y-%m-%d') <= '{$dateto->format('Y-m-d')}'");
            $data = $data->get();
            // return view('AdminApi\Report::travel.traveltransaction_'.$reportname, compact('data', 'dataReport','datefrom','dateto'));
            $pdf = SnappyPdf::loadView('AdminApi\Report::travel.traveltransaction_'.$reportname, compact('data', 'dataReport','datefrom','dateto'));
          break;
          case 'overseas_roomreservation':
            $reporttitle = "REPORT OVERSEAS - ROOM RESERVATION REPORT";
            $dataReport = (object) [
                "reporttitle" => 'REPORT OVERSEAS - ROOM RESERVATION REPORT',
                "reportname" => $request->input('report'),
                "CompanyObj" => $user->CompanyObj,
            ];
            $data = DB::table('trvtransactiondetail as data');
            $data = $data->leftJoin('traveltransaction AS TravelTransaction', 'TravelTransaction.Oid', '=', 'data.Oid');
            $data = $data->leftJoin('trvtransactionflight AS TransactionFlight', 'TransactionFlight.Oid', '=', 'TravelTransaction.Oid');
            $data = $data->whereRaw("DATE_FORMAT(data.Date, '%Y-%m-%d') >= '{$datefrom->format('Y-m-d')}'");
            $data = $data->whereRaw("DATE_FORMAT(data.Date, '%Y-%m-%d') <= '{$dateto->format('Y-m-d')}'");
            $data = $data->get();
            // return view('AdminApi\Report::travel.traveltransaction_'.$reportname, compact('data', 'dataReport','datefrom','dateto'));
            $pdf = SnappyPdf::loadView('AdminApi\Report::travel.traveltransaction_'.$reportname, compact('data', 'dataReport','datefrom','dateto'));
          break;
          case 'paymentenquiry':
            $reporttitle = "REPORT PAYMENT BALANCE GSF";
            $dataReport = (object) [
                "reporttitle" => 'REPORT PAYMENT BALANCE GSF',
                "reportname" => $request->input('report'),
                "CompanyObj" => $user->CompanyObj,
            ];
            $data = DB::table('pospointofsale as data');
            $data = $data->leftJoin('traveltransaction AS TravelTransaction', 'TravelTransaction.Oid', '=', 'data.Oid');
            // $data = $data->leftJoin('trvtransactiondetail AS TravelTransactionDetail', 'TravelTransaction.Oid', '=', 'TravelTransactionDetail.TravelTransaction');
            $data = $data->whereRaw("DATE_FORMAT(data.Date, '%Y-%m-%d') >= '{$datefrom->format('Y-m-d')}'");
            $data = $data->whereRaw("DATE_FORMAT(data.Date, '%Y-%m-%d') <= '{$dateto->format('Y-m-d')}'");
            $data = $data->get();
            foreach ($data as $row) {
              $row = $this->crudController->detail('trvtransactiondetail', $row->Oid);
            }
            // return view('AdminApi\Report::travel.traveltransaction_'.$reportname, compact('data', 'dataReport','datefrom','dateto'));
            $pdf = SnappyPdf::loadView('AdminApi\Report::travel.traveltransaction_'.$reportname, compact('data', 'dataReport','datefrom','dateto'));
          break;
          case 'advancecashpayment':
            $reporttitle = "REPORT ADVANCE CASH PAYMENT";
            $dataReport = (object) [
                "reporttitle" => 'REPORT ADVANCE CASH PAYMENT',
                "reportname" => $request->input('report'),
                "CompanyObj" => $user->CompanyObj,
            ];
            $data = DB::table('pospointofsale as data');
            // $data = $data->leftJoin('traveltransaction AS TravelTransaction', 'TravelTransaction.Oid', '=', 'data.Oid');
            // $data = $data->leftJoin('trvtransactiondetail AS TravelTransactionDetail', 'TravelTransaction.Oid', '=', 'TravelTransactionDetail.TravelTransaction');
            $data = $data->whereRaw("DATE_FORMAT(data.Date, '%Y-%m-%d') >= '{$datefrom->format('Y-m-d')}'");
            $data = $data->whereRaw("DATE_FORMAT(data.Date, '%Y-%m-%d') <= '{$dateto->format('Y-m-d')}'");
            $data = $data->get();
            foreach ($data as $row) {
              $row = $this->crudController->detail('trvtransactiondetail', $row->Oid);
            }
            // return view('AdminApi\Report::travel.traveltransaction_'.$reportname, compact('data', 'dataReport','datefrom','dateto'));
            $pdf = SnappyPdf::loadView('AdminApi\Report::travel.traveltransaction_'.$reportname, compact('data', 'dataReport','datefrom','dateto'));
          break;
          case 'gitsearch':
            $reporttitle = "REPORT GIT SEARCH";
            $dataReport = (object) [
                "reporttitle" => 'REPORT GIT SEARCH',
                "reportname" => $request->input('report'),
                "CompanyObj" => $user->CompanyObj,
            ];
            $data = DB::table('trdsalesinvoice as data');
            $data = $data->leftJoin('pospointofsale AS PointOfSale', 'PointOfSale.Oid', '=', 'data.PointOfSale');
            $data = $data->leftJoin('traveltransaction AS TravelTransaction', 'TravelTransaction.Oid', '=', 'PointOfSale.Oid');
            $data = $data->leftJoin('trvtransactiondetail AS TravelTransactionDetail', 'TravelTransaction.Oid', '=', 'TravelTransactionDetail.TravelTransaction');
            $data = $data->whereRaw("DATE_FORMAT(data.Date, '%Y-%m-%d') >= '{$datefrom->format('Y-m-d')}'");
            $data = $data->whereRaw("DATE_FORMAT(data.Date, '%Y-%m-%d') <= '{$dateto->format('Y-m-d')}'");
            $data = $data->get();
            foreach ($data as $row) {
              $row = $this->crudController->detail('trvtransactiondetail', $row->Oid);
            }
            // return view('AdminApi\Report::travel.traveltransaction_'.$reportname, compact('data', 'dataReport','datefrom','dateto'));
            $pdf = SnappyPdf::loadView('AdminApi\Report::travel.traveltransaction_'.$reportname, compact('data', 'dataReport','datefrom','dateto'));
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

}
