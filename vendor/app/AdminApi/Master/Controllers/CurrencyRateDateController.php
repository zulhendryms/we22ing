<?php
    namespace App\AdminApi\Master\Controllers;

    use Validator;
    use Carbon\Carbon;
    use Illuminate\Http\Request;
    use Illuminate\Http\Response;
    use Illuminate\Support\Facades\DB;
    use Illuminate\Support\Facades\Auth;
    use App\Laravel\Http\Controllers\Controller;
    use App\Core\Master\Entities\CurrencyRateDate;
    use App\Core\Security\Services\RoleModuleService;
    use App\AdminApi\Development\Controllers\CRUDDevelopmentController;
    use App\Core\Master\Entities\CurrencyRate;

    class CurrencyRateDateController extends Controller
    {
        protected $roleService;
        private $module;
        private $crudController;
        public function __construct(
            RoleModuleService $roleService
        ) {
            $this->module = 'mstcurrencyratedate';
            $this->roleService = $roleService;
            $this->crudController = new CRUDDevelopmentController();
        }

        public function config(Request $request)
        {
            try {
                $fields = $this->crudController->config($this->module);
                $fields[0]->topButton = [
                    [
                        "name" => "Create new rate",
                        "icon" => "ActivityIcon",
                        "type" => "global_form",
                        "showModal" => false,
                        "post" => "currency/rate/insert",
                        "afterRequest" => "init",
                        "form" => [
                            [
                                'fieldToSave' => "Date",
                                'type' => "inputdate",
                                // 'default' => now()->format('Y-m-d');
                            ],
                        ]
                    ]
                ];
                return $fields;
    
            } catch (\Exception $e) {
                return response()->json(
                    errjson($e),
                    Response::HTTP_UNPROCESSABLE_ENTITY
                );
            }
        }
    
        public function presearch(Request $request)
        {
            try {
                return $this->crudController->presearch($this->module);
            } catch (\Exception $e) {
                return response()->json(
                    errjson($e),
                    Response::HTTP_UNPROCESSABLE_ENTITY
                );
            }
        }
    
        public function list(Request $request)
        {
            try {
                $data = DB::table($this->module.' as data');
                $data = $this->crudController->list($this->module, $data, $request, true);
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

        private function showSub($Oid)
        {
            try {
                $data = $this->crudController->detail($this->module, $Oid);
                return $data;
            } catch (\Exception $e) {
                err_return($e);
            }
        }

        public function show(CurrencyRateDate $data)
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
                    $strName="";
                    foreach ($data->Details as $row) {
                        if (!isset($row->SellRate)) $row->SellRate = $row->BuyRate ?: 1;
                        if (!isset($row->BuyRate)) $row->BuyRate = $row->SellRate ?: 1;
                        $row->MidRate = ($row->SellRate + $row->BuyRate)/2;
                        if (in_array($row->CurrencyObj->Code, ['IDR','SGD','USD','MYR'])) {
                            if ($row->MidRate > 1) $strName = 
                                $strName.($strName ? '; ':'').$row->CurrencyObj->Code.' '.$row->MidRate;
                        }
                        $row->save();
                    }
                    $data->Code = Carbon::parse($data->Date)->format('Ymd');
                    $data->Name = $strName;
                    $data->save();
                });
                $role = $this->roleService->list($this->module); //rolepermission
                $data = $this->showSub($data->Oid);
                
                $data->Action = $this->roleService->generateActionMaster($role);
                return response()->json($data, Response::HTTP_OK);
            } catch (\Exception $e) {
                return response()->json(
                    errjson($e),
                    Response::HTTP_UNPROCESSABLE_ENTITY
                );
            }
        }
        
        public function saveall(Request $request) {
            $datas = CurrencyRateDate::with('Details')->whereNull('Name')->get();
            foreach($datas as $data) {
                $strName="";
                foreach ($data->Details as $row) {
                    if (!isset($row->SellRate)) $row->SellRate = $row->BuyRate ?: 1;
                    if (!isset($row->BuyRate)) $row->BuyRate = $row->SellRate ?: 1;
                    $row->MidRate = ($row->SellRate + $row->BuyRate)/2;
                    if (isset($row->Currency->Code)) {
                        if (in_array($row->CurrencyObj->Code, ['IDR','SGD','USD','MYR'])) {
                            if ($row->MidRate > 1) $strName = 
                                $strName.($strName ? '; ':'').$row->CurrencyObj->Code.' '.$row->MidRate;
                        }
                    }
                    $row->save();
                }
                $data->Code = Carbon::parse($data->Date)->format('Ymd');
                $data->Name = $strName;
                $data->save();
            }
        }

        public function destroy(CurrencyRateDate $data)
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
        

    public function functionInsert(Request $request)
    {
        $request = json_decode(json_encode(object_to_array(json_decode($request->getContent()))));

        try {
            $data = CurrencyRateDate::where('Date', $request->Date)->first();
            if (!$data) {
                $data = new CurrencyRateDate();
                $data->Date = $request->Date;
                $data->save();
            }
            $user = Auth::user();
            $query = "INSERT INTO mstcurrencyrate (Oid, Company, CurrencyRateDate, Currency, Date, BuyRate, SellRate, MidRate)
                SELECT UUID(), '{$user->Company}', '" . $data->Oid . "', cr.Currency, '" . $data->Date . "', cr.BuyRate, cr.SellRate, (cr.BuyRate+cr.SellRate) / 2
                FROM mstcurrencyrate cr
                LEFT OUTER JOIN mstcurrencyrate crd ON cr.Currency = crd.Currency AND crd.Date = '" . $data->Date . "'
                WHERE cr.CurrencyRateDate = 
                (SELECT CurrencyRateDate FROM mstcurrencyrate crd WHERE crd.Date <= '" . $data->Date . "' AND CurrencyRateDate IS NOT NULL ORDER BY crd.Date DESC LIMIT 1) AND
                crd.Oid IS NULL AND cr.Company='{$user->Company}';";
            DB::insert($query);
            $query = "INSERT INTO mstcurrencyrate (Oid, Company, CurrencyRateDate, Currency, Date, BuyRate, SellRate, MidRate)
                SELECT UUID(), '{$user->Company}', '" . $data->Oid . "', c.Oid, '" . $data->Date . "', 1,1,1
                FROM mstcurrency c 
                LEFT OUTER JOIN mstcurrencyrate rt ON c.Oid = rt.Currency AND rt.CurrencyRateDate='" . $data->Oid . "'
                WHERE rt.Oid IS NULL AND c.IsActive = 1 AND c.Company='{$user->Company}'";
            DB::insert($query);
            $data = CurrencyRateDate::with('Details')->where('Oid', $data->Oid)->first();
            $role = $this->roleService->list('CurrencyRate');
            $data->Role = $this->roleService->generateRoleMasterCopy($role);

            if ($data->Details()->count() == 0) {
                DB::insert($query);
            }

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

    public function reupdate(Request $request, $Oid = null)
    {
        try {
            $data = CurrencyRateDate::findOrFail($Oid)->first();

            $query = "INSERT INTO mstcurrencyrate (Oid, Company, CurrencyRateDate, Currency, Date, BuyRate, SellRate, MidRate)
                SELECT UUID(), c.Company, '" . $data->Oid . "', c.Oid, '" . $data->Date . "', 1,1,1
                FROM mstcurrency c 
                LEFT OUTER JOIN mstcurrencyrate rt ON c.Oid = rt.Currency AND rt.CurrencyRateDate='" . $data->Oid . "'
                WHERE rt.Oid IS NULL AND c.IsActive = 1";
            DB::insert($query);
            $data = CurrencyRateDate::with('Details')->where('Oid', $data->Oid)->first();
            if ($data->Details()->count() == 0) {
                DB::insert($query);
            }

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

    public function update(Request $request, $Oid = null)
    {
        $request = json_decode(json_encode(object_to_array(json_decode($request->getContent())))); //WILLIAM ZEF

        try {
            $data = CurrencyRateDate::findOrFail($Oid);
            DB::transaction(function () use ($request, &$data) {
                $user = Auth::user();
                if ($data->Details()->count() != 0) {
                    foreach ($data->Details as $rowdb) {
                        $found = false;
                        foreach ($request->Details as $rowapi) {
                            if (isset($rowapi->Oid)) {
                                if ($rowdb->Oid == $rowapi->Oid) {
                                    $found = true;
                                }
                            }
                        }
                        if (!$found) {
                            $detail = CurrencyRate::findOrFail($rowdb->Oid);
                            $detail->delete();
                        }
                    }
                }
                if ($request->Details) {
                    $details = [];
                    foreach ($request->Details as $row) {
                        if (isset($row->Oid)) {
                            $detail = CurrencyRate::findOrFail($row->Oid);
                            $detail->Company = $data->Company ?: $user->Company;
                            $detail->Date = $data->Date;
                            $detail->Currency = $row->Currency;
                            $detail->BuyRate = $row->BuyRate;
                            $detail->SellRate = $row->SellRate;
                            $detail->MidRate = ($row->SellRate + $row->BuyRate) / 2;
                            $detail->save();
                        } else {
                            $details[] = new CurrencyRate([
                                'Company' => $data->Company,
                                'Currency' => $row->Currency,
                                'Date' => $data->Date,
                                'BuyRate' => $row->BuyRate ?: $row->MidRate,
                                'SellRate' => $row->SellRate ?: $row->MidRate,
                                'MidRate' => ($row->SellRate + $row->BuyRate) / 2,
                            ]);
                        }
                    }
                    $data->Details()->saveMany($details);
                    $data->load('Details');
                    $data->fresh();
                }
                if (!$data) {
                    throw new \Exception('Data is failed to be saved');
                }
            });

            // $data = (new CurrencyRateResource($data))->type('detail');
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
    }
