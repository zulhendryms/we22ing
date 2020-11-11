<?php

namespace App\AdminApi\Travel\Controllers;

use Validator;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Laravel\Http\Controllers\Controller;
use App\Core\Security\Services\RoleModuleService;
use App\Core\POS\Entities\PointOfSale;
use App\AdminApi\Development\Controllers\CRUDDevelopmentController;

class TravelProfitLossController extends Controller
{
    protected $roleService;
    private $module;
    private $crudController;
    public function __construct(RoleModuleService $roleService)
    {
        $this->roleService = $roleService;
        $this->module = 'trvtransaction';
        $this->crudController = new CRUDDevelopmentController();
    }

    public function fields()
    {
        $fields = []; //f = 'FIELD, t = TITLE

        $fields[] = ['w' => 80, 't' => 'text', 'h' => 0, 'n' => 'MARKET', 'f' => 'bpg.Name'];
        $fields[] = ['w' => 110, 't' => 'text', 'h' => 0, 'n' => 'TOUR CODE', 'f' => 'data.Code'];
        $fields[] = ['w' => 100, 't' => 'text', 'h' => 0, 'n' => 'GUIDE', 'f' => 't1.Name'];
        $fields[] = ['w' => 110, 't' => 'text', 'h' => 0, 'n' => 'AGENT CODE', 'f' => 'bp.Code'];
        $fields[] = ['w' => 150, 't' => 'text', 'h' => 0, 'n' => 'AGENT NAME', 'f' => 'bp.Name'];
        $fields[] = ['w' => 60,  't' => 'text', 'h' => 0, 'n' => 'ADT', 'f' => 'tt.QtyAdult'];
        $fields[] = ['w' => 60,  't' => 'text', 'h' => 0, 'n' => 'CWB', 'f' => 'tt.QtyCWB'];
        $fields[] = ['w' => 60,  't' => 'text', 'h' => 0, 'n' => 'CNB', 'f' => 'tt.QtyCNB'];
        $fields[] = ['w' => 58,  't' => 'text', 'h' => 0, 'n' => 'INFANT', 'f' => 'tt.QtyInfant'];
        $fields[] = ['w' => 60,  't' => 'text', 'h' => 0, 'n' => 'TL', 'f' => 'tt.QtyTL'];
        $fields[] = ['w' => 78,  't' => 'text', 'h' => 0, 'n' => 'EX-BED', 'f' => 'tt.QtyExBed'];
        $fields[] = ['w' => 60,  't' => 'text', 'h' => 0, 'n' => 'PAX', 'f' => 'tt.QtyTotalPax'];
        $fields[] = ['w' => 60, 't' => 'text', 'h' => 0, 'n' => 'CUR', 'f' => 'c.Code'];
        $fields[] = ['w' => 100,  't' => 'text', 'h' => 0, 'n' => 'TOUR FARE', 'f' => 'tt.AmountTourFareTotal'];
        $fields[] = ['w' => 70,  't' => 'text', 'h' => 0, 'n' => 'EX RATE', 'f' => 'tt.Rate'];
        $fields[] = ['w' => 120,  't' => 'text', 'h' => 0, 'n' => 'T.FARE(SGD)', 'f' => 'tt.AmountTourFareTotal'];
        $fields[] = ['w' => 97,  't' => 'text', 'h' => 0, 'n' => 'SHOP COM', 'f' => 'tt.OptionalTour1IsAceRevenue'];
        $fields[] = ['w' => 149,  't' => 'text', 'h' => 0, 'n' => 'DI TAY/OTH INCOME', 'f' => 'tt.IncomeOther'];
        $fields[] = ['w' => 79,  't' => 'text', 'h' => 0, 'n' => 'CHOCO', 'f' => 'tt.IncomeTotalBox'];
        $fields[] = ['w' => 96,  't' => 'text', 'h' => 0, 'n' => 'OPT TOUR', 'f' => 'tt.OptionalTour1AmountTicket'];
        $fields[] = ['w' => 107,  't' => 'text', 'h' => 0, 'n' => 'AMT SALES', 'f' => 'ttd.SalesTotal'];
        $fields[] = ['w' => 100,  't' => 'text', 'h' => 0, 'n' => 'HOTEL AMT', 'f' => 'ttd.HotelPax'];
        $fields[] = ['w' => 111,  't' => 'text', 'h' => 0, 'n' => 'GUIDE CLAIM', 'f' => 'tt.IncomeTourGuide'];
        $fields[] = ['w' => 128,  't' => 'text', 'h' => 0, 'n' => 'COACH / COMBI', 'f' => 'tt.ExpenseCombiCoach'];
        $fields[] = ['w' => 75,  't' => 'text', 'h' => 0, 'n' => 'SERDIZ', 'f' => 'tt.IncomeSerdiz'];
        $fields[] = ['w' => 70,  't' => 'text', 'h' => 0, 'n' => 'A.C', 'f' => 'tt.AmountAgentCommission'];
        $fields[] = ['w' => 81,  't' => 'text', 'h' => 0, 'n' => 'TICKETS', 'f' => 'ttd.PurchaseTotal'];
        $fields[] = ['w' => 96,  't' => 'text', 'h' => 0, 'n' => 'AMT COST', 'f' => 'data.TotalCost'];
        $fields[] = ['w' => 80,  't' => 'text', 'h' => 0, 'n' => 'PROFIT', 'f' => 'tt.IncomeToCompany'];
        $fields[] = ['w' => 96,  't' => 'text', 'h' => 0, 'n' => 'ARR DATE', 'f' => 'tt.DateFrom'];
        $fields[] = ['w' => 99,  't' => 'text', 'h' => 0, 'n' => 'DEPT DATE', 'f' => 'tt.DateUntil'];
        $fields[] = ['w' => 111,  't' => 'text', 'h' => 0, 'n' => 'STAFF NAME', 'f' => 'u.Name'];
        $fields[] = ['w' => 157,  't' => 'text', 'h' => 0, 'n' => 'PRO INV#1', 'f' => 'tt.IncomeSerdiz'];
        $fields[] = ['w' => 60,  't' => 'text', 'h' => 0, 'n' => 'CUR', 'f' => 'tt.IncomeSerdiz'];
        $fields[] = ['w' => 120,  't' => 'text', 'h' => 0, 'n' => 'AMOUNT', 'f' => 'tt.IncomeSerdiz'];
        $fields[] = ['w' => 159,  't' => 'text', 'h' => 0, 'n' => 'PRO INV #2', 'f' => 'tt.IncomeSerdiz'];
        $fields[] = ['w' => 60,  't' => 'text', 'h' => 0, 'n' => 'CUR', 'f' => 'tt.IncomeSerdiz'];
        $fields[] = ['w' => 120,  't' => 'text', 'h' => 0, 'n' => 'AMOUNT', 'f' => 'tt.IncomeSerdiz'];
        return $fields;
    }

    public function config(Request $request)
    {
        $fields = $this->crudController->jsonConfig($this->fields());
        $fields[0]['cellRenderer'] = 'actionCell';
        
        $i = 0;
        foreach($fields as $row) {
            if ($row['headerName'] == 'TICKETS') $fields[$i]['field'] = 'TICKETS.Title';
            if ($row['headerName'] == 'INFANT') $fields[$i]['field'] = 'INFANT.Title';
            $fields[$i]['cellStyle'] = [
                'border' => '0.1px solid #f2f2f2',
                'paddingLeft' => '3px !important',
                'paddingRight' => '1px !important',
                'fontSize' => '9px'
            ];
            $i = $i + 1;
        }
        return $fields;
    }

    public function list(Request $request)
    {
        $fields = $this->fields();
        $data = DB::table('pospointofsale as data') //jointable
            ->leftJoin('traveltransaction AS tt', 'tt.Oid', '=', 'data.Oid')
            ->leftJoin('trvtransactiondetail AS ttd', 'tt.Oid', '=', 'ttd.TravelTransaction')
            ->leftJoin('mstbusinesspartner AS bp', 'bp.Oid', '=', 'data.Customer')
            ->leftJoin('mstbusinesspartnergroup AS bpg', 'bpg.Oid', '=', 'bp.BusinessPartnerGroup')
            ->leftJoin('mstcurrency AS c', 'c.Oid', '=', 'tt.Currency')
            ->leftJoin('trvguide AS t1', 't1.Oid', '=', 'tt.TravelGuide1')
            ->leftJoin('user AS u', 'u.Oid', '=', 'data.User')
            ->leftJoin('sysstatus AS s', 's.Oid', '=', 'data.Status')
            ->leftJoin('trvtraveltype AS tty', 'tty.Oid', '=', 'tt.TravelType')
            ->leftJoin('company AS Company', 'Company.Oid', '=', 'data.Company')
            ->whereIn('tty.Code', ['FIT','GIT'])
            ;
        $data = $this->crudController->jsonList($data, $this->fields(), $request, 'pospointofsale', 'bpg.Code');
        foreach($data as $row) {
            $row->INFANT = [
                'Title' => $row->TICKETS+1500,
                'Details' => [
                    [
                        'A' => 1500,
                        'B' => 1500,
                        'C' => 1500,
                    ],
                    [
                        'A' => 1500,
                        'B' => 1500,
                        'C' => 1500,
                    ]
                ]
            ];
            $row->TICKETS = [
                'Title' => $row->TICKETS+1000,
                'Details' => [
                    [
                        'A' => 1500,
                        'B' => 1500,
                        'C' => 1500,
                    ],
                    [
                        'A' => 1500,
                        'B' => 1500,
                        'C' => 1500,
                    ]
                ]
            ];
        }

        return $this->crudController->jsonListReturn($data, $fields);
    }

    public function presearch(Request $request)
    {
        return null;
    }

    public function index(Request $request)
    {
        try {
            $user = Auth::user();
            $type = $request->input('type') ?: 'combo';
            $data = PointOfSale::whereNull('GCRecord');

            $data = $data->orderBy('Code')->get();
            return $data;
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_NOT_FOUND
            );
        }
    }
}
