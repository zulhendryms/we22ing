<?php

namespace App\AdminApi\POS\Controllers;

use Validator;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Laravel\Http\Controllers\Controller;
use App\Core\POS\Entities\WalletBalance;
use App\Core\Security\Services\RoleModuleService;
use App\Core\Internal\Entities\Status;
use App\Core\Master\Entities\Currency;
use App\Core\Master\Entities\BusinessPartner;
use App\Core\Master\Entities\BusinessPartnerGroupUser;
use App\Core\Security\Entities\User;
use App\AdminApi\Development\Controllers\CRUDDevelopmentController;
use Carbon\Carbon;

class WalletBalanceController extends Controller
{
    protected $roleService;
    private $module;
    private $crudController;
    public function __construct(
        RoleModuleService $roleService
    ) {
        $this->roleService = $roleService;
        $this->module = 'poswalletbalance';
        $this->crudController = new CRUDDevelopmentController();
    }

    public function config(Request $request)
    {
        try {
            return $this->crudController->config($this->module);
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
                "fieldToSave" => "Status",
                "type" => "combobox",
                'hiddenField'=> 'StatusName',
                "column" => "1/5",
                "source" => [
                    [
                        "Oid" => "Entry",
                        "Name" => "Entry"
                    ],
                    [
                        "Oid" => "Posted",
                        "Name" => "Posted"
                    ],
                ],
                "store" => "",
                "defaultValue" => "All"
            ],
            [
                'fieldToSave' => 'BusinessPartner',
                'type' => 'autocomplete',
                'column' => '1/6',
                'validationParams' => 'required',
                'default' => null,
                'source' => [],
                'store' => 'autocomplete/businesspartner',
                'hiddenField'=> 'BusinessPartnerName',
                'params' => [
                    'type' => 'combo',
                    'role' => 'Customer',
                    'term' => ''
                ]
            ],
            [
                'fieldToSave' => 'DateFrom',
                'type' => 'inputdate',
                'column' => '1/5',
                'validationParams' => 'required',
                'default' => Carbon::parse(now())->startOfMonth()->format('Y-m-d')
            ],
            [
                'fieldToSave' => 'DateTo',
                'type' => 'inputdate',
                'column' => '1/5',
                'validationParams' => 'required',
                'default' => Carbon::parse(now())->endOfMonth()->format('Y-m-d')
            ],
            [
                "type" => "action",
                "column" => "1/5"
            ]
        ];
    }

    public function list(Request $request)
    {
        try {
            $data = DB::table($this->module . ' as data');
            if ($request->has('Company')) {
                if ($request->input('Company') != 'null') $data = $data->whereRaw("data.Company = '" . $request->input('Company') . "'");
            }
            if ($request->has('BusinessPartner')) {
                if ($request->input('BusinessPartner') != 'null') $data->whereRaw("data.BusinessPartner = '" . $request->input('BusinessPartner') . "'");
            }
            if ($request->has('DateFrom')) {
                if ($request->input('DateFrom') != 'null') $data = $data->whereRaw("data.Date >= '" . $request->input('DateFrom') . "'");
            }
            if ($request->has('DateTo')) {
                if ($request->input('DateTo') != 'null') $data = $data->whereRaw("data.Date <= '" . $request->input('DateTo') . "'");
            }
            
            $data = $this->crudController->list($this->module, $data, $request);
            foreach ($data->data as $row) {
                $tmp = WalletBalance::where('Oid', $row->Oid)->first();
                $row->Action = $this->action($tmp);
            }
            return response()->json($data, Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
    }

    public function index(Request $request)
    {
        try {
            $data = DB::table($this->module . ' as data');
            $data = $this->crudController->index($this->module, $data, $request, false);
            return response()->json($data, Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
    }

    private function showSub($Oid)
    {
        $data = $this->crudController->detail($this->module, $Oid);
        $data->Action = $this->action($data);
        return $data;
    }

    public function show(WalletBalance $data)
    {
        try {
            return $this->showSub($data->Oid);
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
    }

    public function save(Request $request, $Oid = null)
    {
        try {
            $data;
            DB::transaction(function () use ($request, &$data, $Oid) {
                $data = $this->crudController->saving($this->module, $request, $Oid, false);

                $company = Auth::user()->CompanyObj;
                if (!isset($data->Code)) $data->Code = '<<Auto>>';
                if ($data->Code == '<<Auto>>') $data->Code = now()->format('ymdHis') . '-' . str_random(3);
                if (!isset($data->Date)) $data->Date = now();
                if (!isset($data->Source)) $data->Source = 'Backend';
                if (!isset($data->BusinessPartner)) { //kalo tdk ada customer
                    if (!isset($data->User)) $data->BusinessPartner = $company->CustomerCash; //isi dari company
                    else $data->BusinessPartner = User::findOrFail($data->User)->BusinessPartner; //isi dari user
                }
                $customer = BusinessPartner::findOrFail($data->BusinessPartner);
                if (!isset($data->User)) { //kalo tdk ada user
                    if (isset($data->BusinessPartner)) { //isi dari customer
                        $user = User::where('BusinessPartner', $data->BusinessPartner)->first();
                        $data->User = $user ? $user->Oid : null;
                    }
                }
                if (!isset($data->Currency)) $data->Currency = $customer->SalesCurrency;
                $cur = Currency::findOrFail($data->Currency);
                if (!isset($data->Status)) $data->Status = Status::entry()->first()->Oid;
                $rate = $cur->getRate($data->Date) ? $cur->getRate($data->Date)->MidRate : 1;
                $data->DebetBase = $cur->toBaseAmount($data->DebetAmount, $rate);
                $data->CreditBase = $cur->toBaseAmount($data->DebetAmount, $rate);
                $data->Type = isset($request->Type) ? $request->Type : 'Top-Up';
                $data->save();

                if (!$data) throw new \Exception('Data is failed to be saved');
            });

            $role = $this->roleService->list('WalletBalance'); //rolepermission
            $data = $this->showSub($data->Oid);
            $data->Action = $this->roleService->generateActionMaster($role);
            return response()->json(
                $data,
                Response::HTTP_CREATED
            );
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
    }

    public function destroy(WalletBalance $data)
    {
        try {
            return $this->crudController->delete($this->module, $data);
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
    }

    public function summaryconfig()
    {
        $fields = []; //f = 'FIELD, t = TITLE
        $fields[] = serverSideConfigField('Oid');
        $fields[] = ['w' => 150, 'h' => 0, 'n' => 'Company'];
        $fields[] = ['w' => 500, 'h' => 0, 'n' => 'BusinessPartner'];
        $fields[] = ['w' => 120, 'h' => 0, 'n' => 'Currency'];
        $fields[] = ['w' => 250, 'h' => 0, 't' => 'double', 'n' => 'Balance'];
        $fields = $this->crudController->jsonConfig($fields);

        return $fields;
    }

    public function summarylist(Request $request)
    {
        $company = Auth::user()->Company;

        $query = "SELECT
            bp.Oid, co.Code AS Company, CONCAT(bp.Name, ' - ', bp.Code) AS BusinessPartner,
            c.Code AS Currency,
            FORMAT(SUM(IFNULL(w.DebetAmount,0)-IFNULL(w.CreditAmount,0)),2) AS Balance
            FROM poswalletbalance w
            LEFT OUTER JOIN mstbusinesspartner bp ON bp.Oid = w.BusinessPartner
            LEFT OUTER JOIN company co ON co.Oid = bp.Company
            LEFT OUTER JOIN mstcurrency c ON w.Currency = c.Oid
            WHERE w.GCRecord IS NULL AND bp.Company='{$company}'
            GROUP BY bp.Name, c.Code, bp.Oid;";

        $data = DB::select($query);
        foreach($data as $row) {
            $row->Action = [
                [                    
                    'name' => 'Open in detail',
                    'icon' => 'ArrowUpRightIcon',
                    'type' => 'open_form',
                    'newTab' => true,
                    'url' => 'walletbalance?BusinessPartner={Oid}&BusinessPartnerName={BusinessPartner}&DateStart=' . Carbon::parse(now())->startOfMonth()->format('Y-m-d') . '&DateUnti=' . Carbon::parse(now())->endOfMonth()->format('Y-m-d'),
                    // 'url' => 'walletbalance?Company={Company}&CompanyName={CompanyName}&Account={Oid}&AccountName={AccountName}&DateStart=' . Carbon::parse(now())->startOfMonth()->format('Y-m-d') . '&DateUnti=' . Carbon::parse(now())->endOfMonth()->format('Y-m-d'),
                ]
            ];
        }

        return response()->json(
            $data,
            Response::HTTP_CREATED
        );
    }

    public function action(WalletBalance $data)
    {
        $url = 'walletbalance';
        $actionEntry = [
            'name' => 'Change to ENTRY',
            'icon' => 'UnlockIcon',
            'type' => 'confirm',
            'post' => $url . '/{Oid}/unpost',
        ];
        $actionPosted = [
            'name' => 'Change to POSTED',
            'icon' => 'CheckIcon',
            'type' => 'confirm',
            'post' => $url . '/{Oid}/post',
        ];
        $actionCancelled = [
            'name' => 'Change to Cancelled',
            'icon' => 'XIcon',
            'type' => 'confirm',
            'post' => $url . '/{Oid}/cancelled',
        ];
        $return = [];
        switch ($data->Status ? $data->StatusObj->Code : "entry") {
            case "":
                $return[] = $actionPosted;
                break;
            case "entry":
                $return[] = $actionPosted;
                $return[] = $actionCancelled;
                break;
            case "posted":
                $return[] = $actionEntry;
                break;
                break;
        }
        return $return;
    }

    public function post(WalletBalance $data)
    {
        try {
            DB::transaction(function () use ($data) {
                $data->Status = Status::posted()->first()->Oid;
                $data->save();
            });

            return response()->json(
                $data,
                Response::HTTP_NO_CONTENT
            );
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
    }

    public function unpost(WalletBalance $data)
    {
        try {
            DB::transaction(function () use ($data) {
                $data->Status = Status::entry()->first()->Oid;
                $data->save();
            });

            return response()->json(
                $data,
                Response::HTTP_NO_CONTENT
            );
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
    }

    public function cancelled(WalletBalance $data)
    {
        try {
            DB::transaction(function () use ($data) {
                $data->Status = Status::cancelled()->first()->Oid;
                $data->save();
            });

            return response()->json(
                $data,
                Response::HTTP_NO_CONTENT
            );
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
    }
}
