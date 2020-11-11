<?php

namespace App\AdminApi\POS\Controllers;

use Validator;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Laravel\Http\Controllers\Controller;
use App\Core\POS\Entities\POSSessionAmount;
use App\Core\POS\Entities\POSSession;
use App\Core\POS\Entities\POSSessionAmountType;
use App\Core\POS\Resources\POSSessionAmountResource;
use App\Core\POS\Resources\POSSessionAmountCollection;
use App\Core\Master\Entities\Currency;
use App\Core\Security\Services\RoleModuleService;
use App\AdminApi\Development\Controllers\CRUDDevelopmentController;

class POSSessionAmountController extends Controller
{
    protected $roleService;
    private $module;
    private $crudController;
    public function __construct(
        RoleModuleService $roleService
    ) {
        $this->roleService = $roleService;
        $this->module = 'possessionamount';
        $this->crudController = new CRUDDevelopmentController();
    }

    public function config(Request $request)
    {
        try {
            $data = $this->crudController->config($this->module);
            foreach ($data as &$row) { //combosource
                if ($row['headerName'] == 'Type') {
                    $row['source'][] = ['Oid' => 1, 'Name' => 'Debet',];
                    $row['source'][] = ['Oid' => 2, 'Name' => 'Credit',];
                }
            }
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
            $user = Auth::user();
            $data = DB::table($this->module.' as data');
            $data = $this->crudController->list($this->module, $data, $request);
            $role = $this->roleService->list('POSSessionAmount'); //rolepermission
            foreach ($data->data as $row) {
                $row->Action = $this->roleService->generateActionMaster($role);
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
            $type = $request->input('type') ?: 'combo';
            $user = Auth::user();
            $data = DB::table($this->module.' as data');
            $data = $this->crudController->index($this->module, $data, $request, false);
            $session = POSSession::where('User', $user->Oid)->whereNull('Ended')->first();
            if ($user->BusinessPartner) {
                $data = $data->where('Code', $user->BusinessPartner);
            }
            if (!$session) {
                return response()->json('Session is already ended', Response::HTTP_NOT_FOUND);
            }
            $data = POSSessionAmount::where('POSSession', $session->Oid)->whereNull('GCRecord');
            $data = $data->get();
            foreach ($data as $row) {
                $Type = $row->Type;
                if ($Type == 0) {
                    $row->TypeName = 'Opening Balance';
                } elseif ($Type == 1) {
                    $row->TypeName = 'Cash In';
                } elseif ($Type == 2) {
                    $row->TypeName = 'Cash Out';
                }
            }
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
        // $data->Action = $this->action($data);
        return $data;
    }

    public function show(POSSessionAmount $data)
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
                $data->AmountBase = $data->Amount;
                $data->Currency = $data->PaymentMethodObj->Currency ?: $data->CompanyObj->Currency;
                $data->save();
                if (!$data) {
                    throw new \Exception('Data is failed to be saved');
                }
            });

            $Type =  $data->Type;
            if ($Type == 0) {
                $data->TypeName = 'Opening Balance';
            } elseif ($Type == 1) {
                $data->TypeName = 'Cash In';
            } elseif ($Type == 2) {
                $data->TypeName = 'Cash Out';
            }

            $role = $this->roleService->list('POSSessionAmount'); //rolepermission
            $data = $this->showSub($data->Oid);
            // $data->Action = $this->roleService->generateActionMaster($role);
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

    public function destroy(POSSessionAmount $data)
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
}
