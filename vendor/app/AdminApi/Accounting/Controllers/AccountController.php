<?php
    namespace App\AdminApi\Accounting\Controllers;

    use Validator;
    use Carbon\Carbon;
    use Illuminate\Http\Request;
    use Illuminate\Http\Response;
    use Illuminate\Support\Facades\DB;
    use Illuminate\Support\Facades\Auth;
    use App\Laravel\Http\Controllers\Controller;
    use App\Core\Accounting\Entities\Account;
    use App\Core\Master\Entities\Bank;
    use App\Core\Security\Services\RoleModuleService;
    use App\AdminApi\Development\Controllers\CRUDDevelopmentController;

// Bank
// code
// name
// currency
// address
// accountno
// accountname


    class AccountController extends Controller
    {
        protected $roleService;
        private $module;
        private $crudController;
        public function __construct(
            RoleModuleService $roleService
        ) {
            $this->module = 'accaccount';
            $this->roleService = $roleService;
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
                    'fieldToSave' => 'Company',
                    "hiddenField" => "CompanyName",
                    'type' => 'combobox',
                    'column' => '1/3',
                    'validationParams' => 'required',
                    'source' => 'company',
                    'onClick' => [
                        'action' => 'request',
                        'store' => 'combosource/company',
                        'params' => null
                    ]
                ],
                [
                    'fieldToSave' => 'AccountType',
                    "hiddenField" => "AccountTypeName",
                    'type' => 'combobox',
                    'column' => '1/3',
                    'validationParams' => 'required',
                    'source' => 'data/accounttype',
                    'onClick' => [
                        'action' => 'request',
                        'store' => 'data/accounttype',
                        'params' => null
                    ]
                ],
                [
                    'type' => 'action',
                    'column' => '1/3'
                ]
            ];
        }

        public function list(Request $request)
        {
            try {
                $data = DB::table($this->module.' as data');
                if ($request->has('Company')) if ($request->input('Company') != 'null') $data->whereRaw("data.Company = '" . $request->input('Company') . "'");
                if ($request->has('AccountType')) if ($request->input('AccountType') != 'null') $data->whereRaw("data.AccountType = '" . $request->input('AccountType') . "'");
                
                $data = $this->crudController->list($this->module, $data, $request);
                foreach($data->data as $row) $row->Action = $this->action($row->Oid);
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
                $data = DB::table($this->module.' as data');
                $data = $this->crudController->index($this->module, $data, $request, false);
                return response()->json($data, Response::HTTP_OK);
            } catch (\Exception $e) {
                return response()->json(
                    errjson($e),
                    Response::HTTP_UNPROCESSABLE_ENTITY
                );
            }
        }

        private function action($Oid) {
            return [
                [
                    "name" => "Edit",
                    "icon" => "EditIcon",
                    "type" => "open_form",
                    "url" => "account/form?item={Oid}",
                    "afterRequest" => "apply"
                ],
                [
                    "name" => "Edit Bank",
                    "icon" => "EditIcon",
                    "type" => "open_form",
                    "newTab" => true,
                    "url" => "bank/form?item={Oid}",
                    "afterRequest" => "apply"
                ],
                [
                    "name" => "Delete",
                    "icon" => "TrashIcon",
                    "type" => "delete"
                ]
            ];
        }

        private function showSub($Oid)
        {
            try {
                $data = $this->crudController->detail($this->module, $Oid);
                $data->Action = $this->action($Oid);
                return $data;
            } catch (\Exception $e) {
                err_return($e);
            }
        }

        public function show(Account $data)
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
                    if (in_array($data->AccountTypeObj->Code,['CASH','BANK'])) {
                        $bank = null;
                        if ($data->Bank) $bank = Bank::where('Oid', $data->Bank)->first();
                        if (!$bank) {
                            $bank = new Bank();
                            $bank->Company = $data->Company;
                            if (!$bank->Oid) $bank->Oid = $data->Oid;
                            $bank->Code = $data->Code;
                            $bank->Name = $data->Name;
                            $bank->IsActive = true;
                            $bank->save();
                            $data->Bank = $bank->Oid;
                            $data->save();
                        }
                    }
                });
                return response()->json($data, Response::HTTP_OK);
            } catch (\Exception $e) {
                return response()->json(
                    errjson($e),
                    Response::HTTP_UNPROCESSABLE_ENTITY
                );
            }
        }

        public function destroy(Account $data)
        {
            try {
                return $this->crudController->delete($this->module, $data->Oid);
            } catch (\Exception $e) {
                return response()->json(
                    errjson($e),
                    Response::HTTP_UNPROCESSABLE_ENTITY
                );
            }
        }
    }
