<?php

namespace App\AdminApi\Report\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

use App\Laravel\Http\Controllers\Controller;
use App\Core\Master\Entities\BusinessPartner;
use App\Core\Master\Entities\BusinessPartnerGroup;
use App\Core\Security\Entities\User;
use App\AdminApi\Report\Services\ReportService;
use App\Core\Master\Entities\Company;

use Barryvdh\DomPDF\Facade as PDF;
use Barryvdh\Snappy\Facades\SnappyPdf;
use Carbon\Carbon;

class ReportProfitLossTravelController extends Controller
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
        $this->reportName = 'reporttravel';
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

    public function report(Request $request,$Oid = null)
    {
        $reportname = $request->input('report');
        $user = Auth::user();

        $query = $this->query($reportname);
        $criteria = ""; $criteria2 = ""; $criteria3 = ""; $criteria4 = ""; 
        $filter="";

        $datefrom = Carbon::parse($request->input('DateStart'));
        $dateto = Carbon::parse($request->input('DateUntil'));

        $criteria = $criteria." AND DATE_FORMAT(p.Date, '%Y-%m-%d') >= '".$datefrom->format('Y-m-d')."'";
        $filter = $filter."Date From = '".strtoupper($datefrom->format('Y-m-d'))."'; "; 
        $criteria = $criteria." AND DATE_FORMAT(p.Date, '%Y-%m-%d') <= '".$dateto->format('Y-m-d')."'";
        $filter = $filter."Date To = '".strtoupper($dateto->format('Y-m-d'))."'; "; 

        $criteria2 = $criteria2." AND st.Date < '".$datefrom->format('Y-m-d')."'";
        $criteria3 = $criteria3." AND beg.Date < '".$datefrom->format('Y-m-d')."'";
        
        // $criteria = $criteria . reportQueryCompany('pospointofsale');
        if ($request->input('p_Company')) {
            $val = Company::findOrFail($request->input('p_Company'));
            $criteria = $criteria . " AND co.Oid = '" . $val->Oid . "'";
            $filter = $filter . " AND Company = '" . strtoupper($val->Name) . "'";
        }
        if ($request->input('p_BusinessPartnerGroup')) {
            $val = BusinessPartnerGroup::find($request->input('p_BusinessPartnerGroup'));
            $criteria3 = $criteria3." AND bp.BusinessPartnerGroup = '".$val->Oid."'";
            $criteria4 = $criteria4." AND bp.BusinessPartnerGroup = '".$val->Oid."'";
            $filter = $filter."Item Group = '".strtoupper($val->Name)."'; ";
        }
        if ($request->input('p_BusinessPartner')) {
            $val = BusinessPartner::findOrFail($request->input('p_BusinessPartner'));
            $criteria = $criteria." AND tt.BusinessPartner = '".$val->Oid."'";
            $criteria2 = $criteria2." AND tt.BusinessPartner = '".$val->Oid."'";
            $filter = $filter."BusinessPartner = '".strtoupper($val->Name)."'; ";
        }
        if ($request->input('p_User')) {
            $val = User::findOrFail($request->input('p_User'));
            $criteria = $criteria." AND p.User = '".$val->Oid."'";
            $criteria2 = $criteria2." AND p.User = '".$val->Oid."'";
            $filter = $filter."User = '".strtoupper($val->Name)."'; ";
        }
        if ($filter) $filter = substr($filter,0);
        if ($criteria) $query = str_replace(" AND 1=1",$criteria,$query);
        if ($criteria2) $query = str_replace(" AND 2=2",$criteria2,$query);
        if ($criteria3) $query = str_replace(" AND 3=3",$criteria3,$query);
        if ($criteria4) $query = str_replace(" AND 4=4",$criteria4,$query);

        $data = DB::select($query);
// dd($query);
        switch ($reportname) {
        case 'profitlosstravel':
            $reporttitle = "Report Profit Loss Travel";
            // return view('AdminApi\Report::pdf.'.$reportname,  compact('data', 'user', 'reportname','filter', 'reporttitle'));
            $pdf = SnappyPdf::loadView('AdminApi\Report::pdf.profitlosstravel', compact('data', 'user', 'reportname','filter', 'reporttitle'))->setPaper('A4', 'landscape');
        break;
        case'outboundinvoice': 
            $reporttitle = "SALES REPORT FOR OUTBOUND INVOICE";
            // return view('AdminApi\PreReport::pdf.traveltransaction_'.$reportname,  compact('data'));
            $pdf = SnappyPdf::loadView('AdminApi\PreReport::pdf.traveltransaction_'.$reportname, compact('data'));
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
            // ->setOption('page-width', '400')
            // ->setOption('page-height', '215')
            ->setOption('margin-right', 10)
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
            case 'profitlosstravel':
                return "SELECT
                tty.Code,
                co.Code AS Comp,
                p.Code AS TourCode,
                CONCAT(bpg.Name, ' - ', bpg.Code) AS GroupName,
                bp.Code AS AgentCode,
                bp.Name AS AgentName,
                t1.Name AS TourGuide1,
                IFNULL(tt.QtyAdult,0) AS ADT,
                IFNULL(tt.QtyCWB,0) AS CWB,
                IFNULL(tt.QtyCNB,0) AS CNB,
                IFNULL(tt.QtyInfant,0) AS INF,
                IFNULL(tt.QtyTL,0) AS TL,
                IFNULL(tt.QtyExBed,0) AS ExBed,
                IFNULL(tt.QtyFOC,0) AS FOC,
                IFNULL(tt.QtyTotalPax,0) AS PAX,
                c.Code AS Cur,
                IFNULL(tt.AmountTourFareTotal, si.TotalAmount) AS TourFare,
                IFNULL(tt.Rate,0) AS ExRate,
                IFNULL(tt.AmountTourFareTotal,0) AS TourFareSGD,
                IFNULL(tt.OptionalTour1IsAceRevenue,0) AS Shop,
                IFNULL(tt.IncomeOther,0) AS OthIncome,
                IFNULL(tt.IncomeTotalBox,0) AS Choco,
                IFNULL(tt.OptionalTour1AmountTourBalance,0) AS OptTour,
                IFNULL(tt.AmountTourFareTotal,0) AS AmtSales,
                IFNULL(ttdh.PurchaseTotal,0) AS HotelAmount,
                IFNULL(tt.IncomeTourGuide,0) AS GuideClaim,
                IFNULL(tt.ExpenseCombiCoach,0) AS Coach,
                IFNULL(tt.IncomeSerdiz,0) AS Serdiz,
                IFNULL(tt.AmountAgentCommission,0) AS AC,
                IFNULL(ttdattr.PurchaseTotal, 0) AS Tickets,
                IFNULL(ttdnoattr.PurchaseTotal,0) AS AmtCost,

                IFNULL(tt.AmountTourFareTotal,0) - IFNULL(ttdh.PurchaseTotal,0) - IFNULL(tt.IncomeTourGuide,0) - IFNULL(tt.ExpenseCombiCoach,0) - IFNULL(tt.IncomeSerdiz,0) 
                - IFNULL(tt.AmountAgentCommission,0) - IFNULL(ttdattr.PurchaseTotal, 0) - IFNULL(ttdnoattr.PurchaseTotal,0)  AS Profit,
                u.Name AS StaffName
                FROM pospointofsale p
                LEFT OUTER JOIN traveltransaction tt ON tt.Oid = p.Oid
                LEFT OUTER JOIN (
                    SELECT ttdattr.TravelTransaction, SUM(ttdattr.SalesTotal) AS SalesTotal, SUM(ttdattr.PurchaseTotal) AS PurchaseTotal FROM trvtransactiondetail ttdattr 
                    WHERE ttdattr.GCRecord IS NULL AND ttdattr.OrderType ='Attraction' GROUP BY ttdattr.TravelTransaction
                    ) AS ttdattr ON ttdattr.TravelTransaction = p.Oid
                LEFT OUTER JOIN (
                    SELECT ttdnoattr.TravelTransaction, SUM(ttdnoattr.SalesTotal) AS SalesTotal, SUM(ttdnoattr.PurchaseTotal) AS PurchaseTotal FROM trvtransactiondetail ttdnoattr 
                    WHERE ttdnoattr.GCRecord IS NULL AND ttdnoattr.OrderType NOT IN ('Attraction','Hotel') GROUP BY ttdnoattr.TravelTransaction
                    ) AS ttdnoattr ON ttdnoattr.TravelTransaction = p.Oid
                LEFT OUTER JOIN (
                    SELECT ttdh.TravelTransaction, SUM(ttdh.SalesTotal) AS SalesTotal, SUM(ttdh.PurchaseTotal) AS PurchaseTotal FROM trvtransactiondetail ttdh 
                    WHERE ttdh.GCRecord IS NULL AND ttdh.OrderType ='Hotel' GROUP BY ttdh.TravelTransaction
                    ) AS ttdh ON ttdh.TravelTransaction = p.Oid
                LEFT OUTER JOIN (
                    SELECT p.PointOfSale, SUM(TotalAmount) AS TotalAmount FROM trdsalesinvoice p WHERE p.GCRecord IS NULL GROUP BY p.PointOfSale
                    ) AS si ON si.PointOfSale = p.Oid
                LEFT OUTER JOIN mstbusinesspartner bp ON bp.Oid = p.Customer
                LEFT OUTER JOIN mstbusinesspartnergroup bpg ON bp.BusinessPartnerGroup = bpg.Oid
                LEFT OUTER JOIN mstcurrency c ON tt.Currency = c.Oid
                LEFT OUTER JOIN trvguide t1 ON tt.TravelGuide1 = t1.Oid
                LEFT OUTER JOIN user u ON p.User = u.Oid
                LEFT OUTER JOIN sysstatus s ON p.Status = s.Oid
                LEFT OUTER JOIN trvtraveltype tty ON tty.Oid = tt.TravelType
                LEFT OUTER JOIN company co ON co.Oid = p.Company
                WHERE p.GCRecord IS NULL 
                AND CASE WHEN co.Oid='dbb1f435-7a14-4fa0-8535-beceed9cd477' THEN tty.Code IN ('FIT','GIT','Outbound') ELSE tty.Code IN ('FIT','GIT') END AND 1=1
                ORDER BY bpg.Name, p.Code, DATE_FORMAT(tt.DateFrom, '%Y%m%d')
                ";

                
            break;
            case "outboundinvoice":
                return"SELECT 
                si.Code, co.Code AS Comp,
                DATE_FORMAT(si.Date, '%d-%m-%Y') AS Date,
                DATE_FORMAT(ttd.DateFrom, '%d/%m/%Y') AS DateFrom,
                DATE_FORMAT(ttd.DateUntil, '%d/%m/%Y') AS DateUntil,
                u.UserName AS SalesPerson,
                IFNULL(si.TotalAmount,0) AS InvoiceAmount,
                bp.Name AS CustomerName,
                ttd.Code AS DetailCode,
                DATE_FORMAT(ttd.Date, '%d/%m/%Y') AS DetailDate,
                bpd.Name AS DetailBusinessPartner,
                IFNULL(ttd.PurchaseTotal,0) AS DetailAmount
                
                FROM pospointofsale p
                LEFT OUTER JOIN traveltransaction tt ON p.Oid = tt.Oid
                LEFT OUTER JOIN trvtransactiondetail ttd ON tt.Oid = ttd.TravelTransaction
                LEFT OUTER JOIN trdsalesinvoice si ON p.Oid = si.PointOfSale
                LEFT OUTER JOIN mstbusinesspartner bp ON si.BusinessPartner = bp.Oid
                LEFT OUTER JOIN mstbusinesspartner bpd ON ttd.BusinessPartner = bpd.Oid
                LEFT OUTER JOIN mstemployee e ON bp.SalesPerson = e.Oid
                LEFT OUTER JOIN company c ON c.Oid = p.Company
                LEFT OUTER JOIN user u ON u.Oid = p.User
                LEFT OUTER JOIN user u1 ON u1.Oid = ttd.CreatedBy
                LEFT OUTER JOIN company co ON co.Oid = p.Company
                LEFT OUTER JOIN trvtraveltype tty ON tty.Oid = tt.TravelType
                WHERE p.GCRecord IS NULL AND 1=1 AND 4=4
                AND tty.Code IN ('Outbound')
                AND ttd.OrderType IN ('Expense','Income')
                ORDER BY si.Code, DATE_FORMAT(si.Date, '%Y%m%d')
                ";
                break;
        }
        return "";
    }


}