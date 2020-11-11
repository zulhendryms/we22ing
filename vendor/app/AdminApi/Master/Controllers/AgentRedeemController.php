<?php

namespace App\AdminApi\Master\Controllers;

use Validator;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Laravel\Http\Controllers\Controller;
use App\Core\Master\Entities\Company;
use App\Core\Security\Services\RoleModuleService;
use App\Core\Internal\Services\AutoNumberService;
use App\AdminApi\Development\Controllers\CRUDDevelopmentController;
use App\Core\POS\Entities\ETicket;

class AgentRedeemController extends Controller
{
    protected $roleService;
    private $crudController;
    public function __construct(
        RoleModuleService $roleService
    ) {
        $this->roleService = $roleService;
        $this->crudController = new CRUDDevelopmentController();
    }

    public function fields()
    {
        $fields = []; //f = 'FIELD, t = TITLE
        $fields[] = ['w' => 0, 'h' => 1, 'n' => 'Oid'];
        $fields[] = ['w' => 150, 'r' => 0, 't' => 'text', 'n' => 'Code'];
        $fields[] = ['w' => 150, 'r' => 0, 't' => 'text', 'n' => 'Ticket'];
        $fields[] = ['w' => 450, 'r' => 0, 't' => 'text', 'n' => 'Item'];
        $fields[] = ['w' => 400, 'r' => 0, 't' => 'text', 'n' => 'BusinessPartner'];
        $fields[] = ['w' => 100, 'r' => 0, 't' => 'text', 'n' => 'Date'];
        $fields[] = ['w' => 100, 'r' => 0, 't' => 'text', 'n' => 'Expiry'];
        $fields[] = ['w' => 100, 'r' => 0, 't' => 'text', 'n' => 'DateRedeem'];
        $fields[] = ['w' => 100, 'r' => 0, 't' => 'text', 'n' => 'Status'];
        $fields[] = ['w' => 0, 'h' => 1, 'n' => 'URL'];
        return $fields;
    }
    public function config(Request $request)
    {
        $fields = $this->crudController->jsonConfig($this->fields(), false, true);
        $fields[0]['cellRenderer'] = 'actionCell';
        return $fields;
    }

    public function list(Request $request)
    {
        try {
            $criteria = null;
            if ($request->has('BusinessPartner')) $criteria = $criteria . " AND et.BusinessPartner = '{$request->input('BusinessPartner')}'";
            if ($request->has('Status')) {
                switch ($request->input('Status')) {
                    case 'Open':
                        $criteria = $criteria . " AND et.DateRedeem IS NULL";
                        break;
                    case 'Redeemed':
                        $criteria = $criteria . " AND et.DateRedeem IS NOT NULL";
                        break;
                }
            }

            $query = "SELECT et.Oid, ttd.Code Code, et.Code Ticket, DATE_FORMAT(ttd.Date,'%d-%m-%Y') AS Date, 
                DATE_FORMAT(et.DateExpiry,'%d-%m-%Y') AS Expiry, DATE_FORMAT(et.DateRedeem,'%d-%m-%Y') AS DateRedeem, 
                i.Name Item, bp.Name BusinessPartner, 
                CASE WHEN et.DateRedeem IS NULL THEN 'Open' ELSE 'Redeemed' END AS Status, et.URL URL
                FROM poseticket et 
                LEFT OUTER JOIN mstitem i ON i.Oid = et.Item 
                LEFT OUTER JOIN mstbusinesspartner bp ON et.BusinessPartner = bp.Oid
                LEFT OUTER JOIN trvtransactiondetail ttd ON et.TravelTransactionDetail = ttd.Oid
                WHERE et.Item IS NOT NULL {$criteria}
                ORDER BY Date
                ";
            $data = DB::select($query);
            foreach ($data as $row) {
                $row->Action = $this->action();
            }
            return response()->json(
                $data,
                Response::HTTP_OK
            );
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
    }

    public function presearch(Request $request)
    {
        return [
            [
                'fieldToSave' => "Status",
                'hiddenField' => "StatusName",
                'hiddenField'=> 'StatusName',
                'type' => "combobox",
                'source' => [],
                'column' => "1/3",
                'default' => "Open",
                'source' => [
                    ['Oid' => 'Open', 'Name' => 'Open'],
                    ['Oid' => 'Redeemed', 'Name' => 'Redeemed'],
                ]
            ], [
                'fieldToSave' => 'BusinessPartner',
                'validationParams' => 'required',
                'type' => 'autocomplete',
                'column' => '1/3',
                'source' => [],
                'store' => 'autocomplete/businesspartner',
                'hiddenField'=> 'BusinessPartnerName',
                'default' => [
                    'localCompany',
                    'BusinessPartner'
                ],
            ], [
                'type' => 'action',
                'column' => '1/3'
            ]
        ];
    }

    private function action()
    {
        $data = [
            [
            'name' => 'Redeem',
            'icon' => 'CheckIcon',
            'type' => 'confirm',
            'post' => 'agentredeem/redeem/{Oid}',
            'afterRequest' => "apply",
            ],
            [
                'name' => 'Download',
                'icon' => 'DocumentIcon',
                'type' => 'download',
                'url' => '{URL}',
                'afterRequest' => "init",
            ],
        ];
        return $data;
    }

    public function redeem(Request $request, $Oid = null)
    {
        try {
            if (!$Oid) return null;
            else $data = ETicket::findOrFail($Oid);
            DB::transaction(function () use ($request, &$data) {
                $data->DateRedeem = Carbon::now()->addHours(company_timezone())->toDateTimeString();
                $data->save();
            });
            return response()->json($data, Response::HTTP_CREATED);
        } catch (\Exception $e) {
            errjson($e);
        }
    }
}
