<?php

namespace App\AdminApi\PreReport\Controllers;

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

use Barryvdh\DomPDF\Facade as PDF;
use Barryvdh\Snappy\Facades\SnappyPdf;
use Carbon\Carbon;



class PreReportTravelTransactionController extends Controller
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
        $this->reportName = 'prereport';
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
        $date = date_default_timezone_set("Asia/Jakarta");
        $Oid = $request->input('oid');
        $reportname = $request->input('report');
        $user = Auth::user();

        if ($reportname == 'guidesettlementform') {
            $query = $this->query('gsf.parent', $Oid);
            $data = DB::select($query);
            $query = $this->query('gsf.flight', $Oid);
            $dataFlight = DB::select($query);
            $query = $this->query('gsf.detail', $Oid);
            $dataDetail = DB::select($query);
            // return view('AdminApi\PreReport::pdf.traveltransaction_'.$reportname,  compact('data','user', 'reportname','dataFlight', 'dataDetail'));
            $pdf = SnappyPdf::loadView('AdminApi\PreReport::pdf.traveltransaction_'.$reportname, compact('data', 'user', 'reportname','dataFlight','dataDetail'));
        } else if (in_array($reportname, ['touragreement1','touragreement2','touragreement3'])) {
            $query = $this->query('ta.parent', $Oid);
            $data = DB::select($query);
            $query = $this->query('ta.flight', $Oid);
            $dataFlight = DB::select($query);
            $query = $this->query('ta.itinerary', $Oid);
            $dataItinerary = DB::select($query);
            $query = $this->query('ta.hotel', $Oid);
            $dataHotel = DB::select($query);
            $reporttitle = "Tour Agreement";
            // return view('AdminApi\PreReport::pdf.traveltransaction_'.$reportname,  compact('data','user', 'reportname','reporttitle', 'dataFlight','dataItinerary','dataHotel'));
            $pdf = SnappyPdf::loadView('AdminApi\PreReport::pdf.traveltransaction_'.$reportname, compact('data', 'user', 'reportname', 'reporttitle','dataFlight', 'dataItinerary','dataHotel'));
        } else if($reportname == 'servicevoucher') {
            $query = $this->query('servicevoucher', $Oid);
            $data = DB::select($query);
            $query = $this->query('ta.flight', $Oid);
            $dataFlight = DB::select($query);
            $query = $this->query('sv.passenger', $Oid);
            $dataPax = DB::select($query);
            $reporttitle = "Service Voucher";
            // return view('AdminApi\PreReport::pdf.traveltransaction_'.$reportname,  compact('data','dataFlight','dataPax','reportname'));
            $pdf = SnappyPdf::loadView('AdminApi\PreReport::pdf.traveltransaction_'.$reportname, compact('data','dataFlight','dataPax','reportname'));
        } else if($reportname == 'exchangeorder') {
            $query = $this->query('exchangeorder',$Oid);
            $data = DB::select($query);
            $query = $this->query('sv.passenger',$Oid);
            $dataPax = DB::select($query);
            $reporttitle = "Exchange Order";
            // return view('AdminApi\PreReport::pdf.traveltransaction_'.$reportname, compact('data','dataPax'));
            $pdf = SnappyPdf::loadView('AdminApi\PreReport::pdf.traveltransaction_'.$reportname, compact('data','dataPax'));
        } else if($reportname == 'attractionticket') {
            $query = $this->query('attractionticket',$Oid);
            $data = DB::select($query);
            $reporttitle = "Attraction Ticket Listing";
            // return view('AdminApi\PreReport::pdf.traveltransaction_'.$reportname,  compact('data'));
            $pdf = SnappyPdf::loadView('AdminApi\PreReport::pdf.traveltransaction_'.$reportname, compact('data'));
        } else if($reportname == 'outboundinvoice') {
            $query = $this->query('outboundinvoice',$Oid);
            $data = DB::select($query);
            $reporttitle = "SALES REPORT FOR OUTBOUND INVOICE";
            // return view('AdminApi\PreReport::pdf.traveltransaction_'.$reportname,  compact('data'));
            $pdf = SnappyPdf::loadView('AdminApi\PreReport::pdf.traveltransaction_'.$reportname, compact('data'));
        }

        $pdf
            ->setOption('footer-right', "Page [page] of [toPage]")
            ->setOption('footer-font-size', 5)
            ->setOption('footer-line', true)
            ->setOption('page-width', '215.9')
            ->setOption('page-height', '297')
            // ->setOption('margin-right', 15)
            ->setOption('margin-bottom', 10);

        $reportFile = $this->reportService->create($reportname, $pdf);
        $reportPath = $reportFile->getFileName();
        if ($request->input('action')=='download') return response()->download($reportFile->getFilePath())->deleteFileAfterSend(true);
        if ($request->input('action')=='export') return response()->json($this->reportGeneratorController->ReportActionExport($reportPath),Response::HTTP_OK);
        if ($request->input('action')=='email') return response()->json($this->reportGeneratorController->ReportActionEmail($request->input('Email'), $reporttitle, $reportPath),Response::HTTP_OK);
        if ($request->input('action')=='post') return response()->json($this->reportGeneratorController->ReportPost($reporttitle, $reportPath),Response::HTTP_OK);
        return response()
            ->json(
                route(
                    'AdminApi\Report::view',
                    ['reportName' => $reportPath]
                ),
                Response::HTTP_OK
            );
    }

    private function query($reportname, $Oid)
    {
        switch ($reportname) {
            case "gsf.parent": 
                return "SELECT p.Oid,
                    p.Code AS TourCode,
                    p.Note AS Remark,
                    bp.Name AS Agent,
                    t1.Name AS TourGuide1,
                    t2.Name AS TourGuide2,
                    DATE_FORMAT(tt.DateFrom, '%d/%m/%y') AS DateFrom,
                    DATE_FORMAT(tt.DateUntil, '%d/%m/%y') AS DateUntil,
                    IFNULL(tt.QtyAdult,0) AS ADT,
                    IFNULL(tt.QtyChild,0) AS CHD,
                    IFNULL(tt.QtyInfant,0) AS INF,
                    IFNULL(tt.QtyTL,0) AS TL,
                    
                    IFNULL(tt.ExpenseDriver,0) AS DriverTip,
                    IFNULL(tt.ExpensePorter,0) AS PorterTip,
                    IFNULL(tt.ExpenseLuggage,0) AS LuggageTip,
                    IFNULL(tt.ExpenseWater,0) AS MineralWater,
                    IFNULL(tt.ExpenseTaxi,0) AS TaxiClaim,
                    IFNULL(tt.ExpenseCombiCoach,0) AS CoachFee,
                    IFNULL(tt.ExpenseTourGuideTips,0) AS GuideTips,
                    IFNULL(tt.ExpenseTourGuideFee,0) AS GuideFee,
                    IFNULL(tt.ExpenseOther,0) AS Other,
                    IFNULL(tt.ExpenseBalanceToGuide,0) AS BalanceToGuide,

                    IFNULL(tt.IncomeTotalBox,0) AS toTotalBox,
                    IFNULL(tt.IncomeTourLeader,0) AS toTourLeader,
                    IFNULL(tt.IncomeTourGuide,0) AS toTourGuide,
                    IFNULL(tt.IncomeToCompany,0) AS toCompany,
                    IFNULL(tt.IncomeExchangeRate,0) AS ExcangeRate,
                    IFNULL(tt.IncomeSerdiz,0) AS SerdizCosting,
                    IFNULL(tt.IncomeTipsToCompany,0) AS TipstoAce,
                    IFNULL(tt.IncomeOther,0) AS toOther,
                    IFNULL(tt.IncomeBalanceToCompany,0) AS BalanceToCompany,

                    IFNULL(tt.OptionalTour1TicketAdultTotal,0) AS OptionalTour1TicketAdult,
                    IFNULL(tt.OptionalTour1TicketChildTotal,0) AS OptionalTour1TicketChild,
                    IFNULL(tt.OptionalTour1TicketSeniorTotal,0) AS OptionalTour1TicketSenior,
                    IFNULL(tt.OptionalTour1AmountMRT,0) AS OptionalTour1AmountMRT,
                    IFNULL(tt.OptionalTour1AmountTicket,0) AS OptionalTour1AmountTicket,
                    IFNULL(tt.OptionalTour1AmountDriver,0) AS OptionalTour1AmountDriver,
                    IFNULL(tt.OptionalTour1AmountTourLeader,0) AS OptionalTour1AmountTourLeader,
                    IFNULL(tt.OptionalTour1AmountTourGuide,0) AS OptionalTour1AmountTourGuide,
                    IFNULL(tt.OptionalTour1AmountTourBalance,0) AS OptionalTour1AmountTourBalance,
                    IFNULL(tt.OptionalTour1AmountTourBalanceTotal,0) AS OptionalTour1AmountTourBalanceTotal,

                    IFNULL(tt.OptionalTour2TicketAdultTotal,0) AS OptionalTour2TicketAdult,
                    IFNULL(tt.OptionalTour2TicketChildTotal,0) AS OptionalTour2TicketChild,
                    IFNULL(tt.OptionalTour2TicketSeniorTotal,0) AS OptionalTour2TicketSenior,
                    IFNULL(tt.OptionalTour2AmountMRT,0) AS OptionalTour2AmountMRT,
                    IFNULL(tt.OptionalTour2AmountTicket,0) AS OptionalTour2AmountTicket,
                    IFNULL(tt.OptionalTour2AmountDriver,0) AS OptionalTour2AmountDriver,
                    IFNULL(tt.OptionalTour2AmountTourLeader,0) AS OptionalTour2AmountTourLeader,
                    IFNULL(tt.OptionalTour2AmountTourGuide,0) AS OptionalTour2AmountTourGuide,
                    IFNULL(tt.OptionalTour2AmountTourBalance,0) AS OptionalTour2AmountTourBalance,
                    IFNULL(tt.OptionalTour2AmountTourBalanceTotal,0) AS OptionalTour2AmountTourBalanceTotal

                    FROM pospointofsale p
                    LEFT OUTER JOIN traveltransaction tt ON tt.Oid = p.Oid
                    LEFT OUTER JOIN mstbusinesspartner bp ON bp.Oid = p.Customer
                    LEFT OUTER JOIN trvguide t1 ON tt.TravelGuide1 = t1.Oid
                    LEFT OUTER JOIN trvguide t2 ON tt.TravelGuide2 = t2.Oid
                    WHERE p.GCRecord IS NULL AND p.Oid =  '" . $Oid . "'
                ";
            case "gsf.flight": 
                return "SELECT 
                tf.FlightType,
                DATE_FORMAT(tf.FlightDate, '%d/%m/%y') AS FlightDate,
                tf.FlightNo,
                tf.FlightRemark
              
                FROM pospointofsale p
                LEFT OUTER JOIN traveltransaction tt ON tt.Oid = p.Oid
                LEFT OUTER JOIN trvtransactionflight tf ON tt.Oid = tf.TravelTransaction
                WHERE p.GCRecord IS NULL AND p.Oid =  '" . $Oid . "'
                ";
            case "gsf.detail": 
                return "SELECT 
                p.Oid,
                bp.Name AS BusinessPartner,
                LEFT(IFNULL(i.Initial, i.Name),30) AS Item,
                s.code AS ItemType,
                ig.Code AS ItemGroupCode,
                ig.Name AS ItemGroup,
                DATE_FORMAT(IFNULL(ttd.Date,IFNULL(ttd.DateFrom,p.Date)), '%d/%m/%y') AS Date,
                ttd.QtySenior AS QtySnr,
                ttd.QtyAdult AS QtyAdt,
                ttd.QtyChild AS QtyChd,
                IFNULL(ttd.PurchaseTotal,0) AS TotalAmount,
                ttd.OrderType,
                hrt.Name AS TravelHotelRoomType,
                ttd.PurchaseOption
                
                FROM pospointofsale p
                LEFT OUTER JOIN traveltransaction tt ON tt.Oid = p.Oid
                LEFT OUTER JOIN trvtransactiondetail ttd ON tt.Oid = ttd.TravelTransaction
                LEFT OUTER JOIN mstbusinesspartner bp ON ttd.BusinessPartner = bp.Oid
                LEFT OUTER JOIN mstitem i ON ttd.Item = i.Oid
                LEFT OUTER JOIN mstitemgroup ig ON ttd.ItemGroup = ig.Oid
                LEFT OUTER JOIN sysitemtype s ON i.ItemType = s.Oid
                LEFT OUTER JOIN trvhotelroomtype hrt ON hrt.Oid = ttd.TravelHotelRoomType
                WHERE p.GCRecord IS NULL AND p.Oid =  '" . $Oid . "'
                ";
                break;
            case "ta.parent":
                return "SELECT p.Oid,
                    p.Code AS TourCode,
                    bp.Name AS Agent,
                    tg1.Name TourGuide1,
                    tt.DriverDetail AS DriverDetail,
                    tt.Note AS Note,
                    tt.NoteTourGuide AS NoteTourGuide,
                    DATE_FORMAT(p.Date, '%d/%m/%Y')  AS Date,
                    bp1.Name AS Customer,
                    u.UserName,
                    cit.Code AS City,
                    u.Name AS UserFullName,
                    u.PhoneFull AS UserPhone,
                    p.ContactName AS ContactName,
                    p.ContactPhone AS ContactNumber,
                    tt.TourLeader AS TourLeader,
                    tt.CodeReff,
                    tg2.Name TourGuide2,
                    sinv.Code AS SalesInvoiceCode,
                    us.Name AS UserProcess,
                    us.Country AS Country,
                    us.UserPhone AS UserProcessPhone,
                    IFNULL(tt.QtyAdult,0) AS ADT,
                    IFNULL(tt.QtyCWB,0) AS CWB,
                    IFNULL(tt.QtyCNB,0) AS CNB,
                    IFNULL(tt.QtyInfant,0) AS INF,
                    IFNULL(tt.QtyExBed,0) AS ExBed,
                    IFNULL(tt.QtyFOC,0) AS FOC,
                    IFNULL(tt.QtyTL,0) AS TL,
                    IFNULL(tt.QtyAdult,0) + IFNULL(tt.QtyCWB,0) + IFNULL(tt.QtyCNB,0) + IFNULL(tt.QtyInfant,0) + IFNULL(tt.QtyExBed,0) + IFNULL(tt.QtyFOC,0) + IFNULL(tt.QtyTL,0) AS Total1,
                    IFNULL(tt.QtySGL,0) AS SGL,
                    IFNULL(tt.QtyDBL,0) AS DBL,
                    IFNULL(tt.QtyTWN,0) AS TWN,
                    IFNULL(tt.QtyTRP,0) AS TRP,
                    IFNULL(tt.QtySGL,0) + IFNULL(tt.QtyDBL,0) + IFNULL(tt.QtyTWN,0) + IFNULL(tt.QtyTRP,0) + IFNULL(tt.QtyFOC,0) AS Total2
                    FROM pospointofsale p
                    LEFT OUTER JOIN traveltransaction tt ON tt.Oid = p.Oid
                    LEFT OUTER JOIN trvguide tg1 ON tg1.Oid = tt.TravelGuide1
                    LEFT OUTER JOIN trvguide tg2 ON tg2.Oid = tt.TravelGuide2
                    LEFT OUTER JOIN mstbusinesspartner bp ON tt.BusinessPartner = bp.Oid
                    LEFT OUTER JOIN mstbusinesspartner bp1 ON p.Customer = bp1.Oid
                    LEFT OUTER JOIN user u ON p.User = u.Oid
                    LEFT OUTER JOIN mstcity cit ON bp1.City = cit.Oid
                    LEFT OUTER JOIN (SELECT u.Oid, u.Name, s.Code Country, u.PhoneNo UserPhone FROM user u LEFT OUTER JOIN mstcity cit ON u.City = cit.Oid LEFT OUTER JOIN syscountry s ON cit.Country = s.Oid) us ON us.Oid = tt.UserProcess
                    LEFT OUTER JOIN (SELECT PointOfSale, Oid, Code FROM trdsalesinvoice WHERE PointOfSale = '" . $Oid . "' LIMIT 1) sinv ON sinv.PointOfSale = p.Oid
                    WHERE p.GCRecord IS NULL AND p.Oid = '" . $Oid . "'
                    ";
            case "ta.flight":
                return "SELECT 
                    tf.FlightType,
                    DATE_FORMAT(tf.FlightDate, '%d %M %y') AS FlightDate,
                    tf.FlightNo,
                    tfn.Code AS FlightCode,
                    tf.FlightRemark
                    FROM pospointofsale p
                    LEFT OUTER JOIN traveltransaction tt ON tt.Oid = p.Oid
                    LEFT OUTER JOIN trvtransactionflight tf ON tt.Oid = tf.TravelTransaction
                    LEFT OUTER JOIN trvflightnumber tfn ON tf.TravelFlightNumber = tfn.Oid
                    WHERE p.GCRecord IS NULL AND p.Oid =  '" . $Oid . "'
                    ORDER BY tf.FlightDate";
            case "ta.itinerary":
                return "SELECT 
                    ti.Oid,
                    ti.DescriptionEN AS DescEN,
                    DATE_FORMAT(ti.Date, '%d %b %Y') AS Date,
                    DATE_FORMAT(tt.DateFrom, '%d %b %Y') AS DateFrom,
                    DATE_FORMAT(tt.DateUntil, '%d %b %Y') AS DateUntil,
                    tt.CodeReff,
                    ti.MealBreakfast AS MB,
                    ti.MealHitea AS MH1,
                    ti.MealLunch AS ML,
                    ti.MealHitea2 AS MH2,
                    ti.MealDinner AS MD,
                    bp.Name AS Hotel
                    
                    FROM pospointofsale p
                    LEFT OUTER JOIN traveltransaction tt ON tt.Oid = p.Oid
                    LEFT OUTER JOIN trvtransactiondetail ttd ON tt.Oid = ttd.TravelTransaction
                    LEFT OUTER JOIN trvtransactionitinerary ti ON tt.Oid = ti.TravelTransaction
                    LEFT OUTER JOIN mstbusinesspartner bp ON ti.BusinessPartnerHotel = bp.Oid
                    WHERE p.GCRecord IS NULL AND p.Oid =  '" . $Oid . "'
                    GROUP BY ti.Oid ORDER BY ti.Date ASC
                    ";
                break;
            case "ta.hotel":
                return "SELECT p.Oid,
                    SUM(ttd.QtySGL) AS SGL,
                    SUM(ttd.QtyDBL) AS DBL,
                    SUM(ttd.QtyTWN) AS TWN,
                    SUM(ttd.QtyExBed) AS ExBed,
                    SUM(ttd.QtyBreakfast) AS Breakfast,
                    SUM(ttd.QtySC) AS SC,
                    SUM(ttd.QtyFOC) AS FOC,
                    SUM(ttd.QtySGL) + SUM(ttd.QtyDBL) + SUM(ttd.QtyTWN) + SUM(ttd.QtyExBed) + SUM(ttd.QtyBreakfast) + SUM(ttd.QtySC) + SUM(ttd.QtyFOC) AS TotalRoom

                    FROM pospointofsale p
                    LEFT OUTER JOIN trvtransactiondetail ttd ON p.Oid = ttd.TravelTransaction
                    WHERE p.GCRecord IS NULL AND ttd.OrderType = 'Hotel' 
                    AND p.Oid = '" . $Oid . "'
                    ";
                break;
            case "servicevoucher":
                return"SELECT 
                    co.LogoPrint AS CompanyLogo,
                    ttd.BusinessPartner,
                    bp.Name AS BPartner,
                    bp.FullAddress AS Address,
                    bp.PhoneNumber AS PhoneNo,
                    DATE_FORMAT(IFNULL(ttd.Date,ttd.DateFrom), '%d %b %Y') AS Date,
                    u.Name AS User,
                    u.PhoneNo AS UserPhone,
                    tt.Code AS TourCode,
                    DATE_FORMAT(ttd.DateFrom, '%d %b %Y') AS DateFrom,
                    DATE_FORMAT(ttd.DateUntil, '%d %b %Y') AS DateUntil,
                    thr.Name AS RoomType,
                    ttd.CodeReference AS CodeReff,
                    ttd.Description AS Description,
                    ttd.Oid,
                    ttd.HotelNote,
                    ttd.HotelInclusion

                    FROM pospointofsale p
                    LEFT OUTER JOIN traveltransaction tt ON p.Oid = tt.Oid
                    LEFT OUTER JOIN trvtransactiondetail ttd ON tt.Oid = ttd.TravelTransaction
                    LEFT OUTER JOIN mstbusinesspartner bp ON ttd.BusinessPartner = bp.Oid
                    LEFT OUTER JOIN mstbusinesspartner bp1 ON p.Customer = bp.Oid
                    LEFT OUTER JOIN user u ON u.Oid = tt.UserProcess
                    LEFT OUTER JOIN company co ON co.Oid = p.Company
                    LEFT OUTER JOIN trvhotelroomtype thr ON ttd.TravelHotelRoomType = thr.Oid
                    WHERE p.GCRecord IS NULL 
                    AND ttd.OrderType = 'Hotel'
                    AND p.Oid =  '" . $Oid . "'
                    ORDER BY bp.Oid
                    ";
                break;
            
            case "sv.passenger": 
                return "SELECT 
                tp.Name
                FROM pospointofsale p
                LEFT OUTER JOIN traveltransaction tt ON tt.Oid = p.Oid
                LEFT OUTER JOIN trvtransactionpassenger tp ON tt.Oid = tp.TravelTransaction
                WHERE p.GCRecord IS NULL AND p.Oid =  '" . $Oid . "'
                ";
            case "exchangeorder":
                return"SELECT 
                    co.LogoPrint AS CompanyLogo,
                    ttd.Code AS ttdCode,
                    bp1.Name AS Agent,
                    bp.Name AS Customer,
                    bp.PhoneNumber AS PhoneNo,
                    si.Code AS InvoiceCode,
                    bp.Email AS Email,
                    bp1.FaxNumber AS Fax,
                    DATE_FORMAT(IFNULL(ttd.Date,IFNULL(ttd.DateFrom,p.Date)), '%d %M %Y') AS Date,
                    ttd.Name AS Name,
                    i.Name AS Item,
                    ttd.Description AS DescEn,
                    ttd.PurchaseDescription AS Note,
                    e.Name AS StaffName,
                    IFNULL(ttd.Qty,0) AS Qty,
                    IFNULL(ttd.PurchaseAmount,0) AS Amount,
                    IFNULL(p.DiscountAmount,0) AS Discount,
                    0 AS Tax,
                    IFNULL(ttd.Qty,0) * IFNULL(ttd.PurchaseAmount,0) AS Total
                    
                    FROM pospointofsale p
                    INNER JOIN traveltransaction tt ON p.Oid = tt.Oid
                    INNER JOIN trvtransactiondetail tkey ON tt.Oid = tkey.TravelTransaction AND tkey.Oid = '".$Oid."'
                    LEFT OUTER JOIN trvtransactiondetail ttd ON tt.Oid = ttd.TravelTransaction AND tkey.Code = ttd.COde AND tkey.BusinessPartner = ttd.BusinessPartner
                    LEFT OUTER JOIN trdsalesinvoice si ON p.Oid = si.PointOfSale
                    LEFT OUTER JOIN mstitem i ON ttd.Item = i.Oid
                    LEFT OUTER JOIN user e ON tt.UserProcess = e.Oid
                    LEFT OUTER JOIN mstbusinesspartner bp ON ttd.BusinessPartner = bp.Oid
                    LEFT OUTER JOIN mstbusinesspartner bp1 ON p.Customer = bp1.Oid
                    LEFT OUTER JOIN company co ON co.Oid = p.Company
                    LEFT OUTER JOIN user u ON u.Oid = p.User
                    WHERE p.GCRecord IS NULL AND tkey.Oid = '".$Oid."'
                    ORDER BY bp.Name
                    ";
                break;
            case "attractionticket":
                return"SELECT 
                    p.Code, p.Date,
                    DATE_FORMAT(p.Date, '%d/%m/%Y') AS Date,
                    i.Name AS AttractionName,
                    IFNULL(ttd.QtyAdult,0) AS Qty, 
                    IFNULL(tic.Type,'Adult') AS Type,
                    CASE WHEN ttd.APIType = 'auto_stock' THEN 'YES' ELSE 'NO' END AS StockAllocation, 
                    tic.Code AS TicketCode,
                    u.UserName
                    FROM pospointofsale p
                    LEFT OUTER JOIN traveltransaction tt ON p.Oid = tt.Oid
                    LEFT OUTER JOIN trvtransactiondetail ttd ON tt.Oid = ttd.TravelTransaction
                    LEFT OUTER JOIN poseticket tic ON tic.PointOfSale = p.Oid AND tic.TravelTransactionDetail = ttd.Oid
                    LEFT OUTER JOIN mstitem i ON i.Oid = ttd.Item
                    LEFT OUTER JOIN company c ON c.Oid = p.Company
                    LEFT OUTER JOIN user u ON u.Oid = p.User
                    WHERE p.GCRecord IS NULL AND p.Oid =  '" . $Oid . "'
                    ORDER BY tic.Code
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
                    WHERE p.GCRecord IS NULL AND p.Oid =  '" . $Oid . "' AND 1=1 AND 4=4
                    AND tty.Code IN ('Outbound')
                    AND ttd.OrderType IN ('Expense','Income')
                    ORDER BY si.Code, DATE_FORMAT(si.Date, '%Y%m%d')
                    ";
                    break;

        }
        return " ";
    }
}
