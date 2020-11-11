<?php

namespace App\AdminApi\POS\Controllers;

use Carbon\Carbon;
use Validator;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Laravel\Http\Controllers\Controller;
use App\Core\PointOfSale\Entities\POSSession;
use App\Core\Security\Services\RoleModuleService;
use App\Core\Internal\Entities\Status;
use App\AdminApi\Development\Controllers\CRUDDevelopmentController;

class POSSessionController extends Controller
{
    protected $roleService;
    private $module;
    private $crudController;
    public function __construct(
        RoleModuleService $roleService
    ) {
        $this->roleService = $roleService;
        $this->module = 'possession';
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
            $data = $this->crudController->list($this->module, $data, $request);
            $role = $this->roleService->list('POSSession');
            $action = $this->roleService->action('POSSession');
            // foreach($data as $row) $row->Role = $this->roleService->generateRoleMasterCopy($row);
            foreach ($data->data as $row) {
                $tmp = POSSession::findOrFail($row->Oid);
                $row->Action = $this->roleService->action($tmp);
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
            $user = Auth::user();
            $type = $request->input('type') ?: 'combo';
            $data = POSSession::with(['UserObj'])->whereNull('GCRecord');
            if ($user->BusinessPartner) {
                $data = $data->where('CreatedAt', $user->BusinessPartner);
            }
            if ($type == 'list') {
                $data->with(['CurrencyObj','UserObj','WarehouseObj']);
            }
            if ($request->has('date')) {
                $data = $data
                    ->where('Date', '>=', Carbon::parse($request->date)->startOfMonth()->toDateString())
                    ->where('Date', '<', Carbon::parse($request->date)->startOfMonth()->addMonths(1)->toDateString());
            }
            $data = $data->orderBy('Date', 'DESC')->get();
            
            $result = [];
            $role = $this->roleService->list('POSSession');
            $action = $this->roleService->action('POSSession');
            
            foreach ($data as $row) {
                $decimal = $row->CurrencyObj ? $row->CurrencyObj->Decimal : null;
                $result[] = [
                    'Oid' => $row->Oid,
                    'Code' => $row->Code,
                    'Date' => Carbon::parse($row->Date)->format('Y-m-d'),
                    'Ended' => $row->Ended,
                    'TotalAmount' => number_format($row->TotalAmount, $decimal),
                    'CurrencyName' => $row->CurrencyObj ? $row->CurrencyObj->Code : null,
                    'WarehouseName' => $row->WarehouseObj ? $row->WarehouseObj->Name.' - '.$row->WarehouseObj->Code : null,
                    'UserName' => $row->UserObj ? $row->UserObj->UserName : null,
                    'StatusName' => $row->StatusObj ? $row->StatusObj->Name : null,
                    'Role' => $this->GenerateRole($row, $role, $action)
                ];
            }
            return $result;
            // return (new POSSessionCollection($data))->type($type);
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_NOT_FOUND
            );
        }
    }

    private function showSub($Oid)
    {
        $data = $this->crudController->detail($this->module, $Oid);
        // $data->Action = $this->action($data);
        return $data;
    }

    public function show($data)
    {        
        try {
            return $this->showSub($data);
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
                if (!$data->Status) {
                    $data->Status = Status::where('Code', 'entry')->first()->Oid;
                }
                if (!$data) {
                    throw new \Exception('Data is failed to be saved');
                }
            });

            $role = $this->roleService->list('POSSession'); //rolepermission
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

    public function destroy(POSSession $data)
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

    public function end(POSSession $data)
    {
        try {
            DB::transaction(function () use ($data) {
                $data->Ended = Carbon::now();
                $data->save();
                $this->salesPosSessionService->post($data->Oid);
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

    private function generateRole(POSSession $row, $role = null, $action = null)
    {
        if (!$role) {
            $role = $this->roleService->list('POSSession');
        }
        if (!$action) {
            $action = $this->roleService->action('POSSession');
        }

        return [
            'IsRead' => $role->IsRead,
            'IsAdd' => $role->IsAdd,
            'IsEdit' => $this->roleService->isAllowDelete($row->StatusObj, $role->IsEdit),
            'IsDelete' => 0, //$this->roleService->isAllowDelete($row->StatusObj, $role->IsDelete),
            'Entry' => $this->roleService->isAllowEntry($row->StatusObj, $action->Entry),
            'Post' => $this->roleService->isAllowPost($row->StatusObj, $action->Posted),
        ];
    }

    public function changeDate(Request $request)
    {
        try {
            if (!$request->has('oid')) {
                return;
            }
            if (!$request->has('date')) {
                return;
            }
            $oid = $request->oid;
            $date = $request->date;
            $query = "UPDATE possession SET Date = '".$date."' WHERE Oid = '".$oid."'";
            DB::Update($query);
            $query = "UPDATE pospointofsale SET Date = '".$date."' WHERE POSSession = '".$oid."'";
            DB::Update($query);
            $query = "UPDATE accjournal LEFT OUTER JOIN pospointofsale p ON p.Oid = accjournal.PointOfSale SET accjournal.Date = '".$date."' WHERE p.POSSession = '".$oid."'";
            DB::Update($query);
            return response()->json(
                null,
                Response::HTTP_NO_CONTENT
            );
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
    }

    public function autocomplete(Request $request)
    {
        $type = $request->input('type') ?: 'combo';
        $term = $request->term;
        $user = Auth::user();

        $data = DB::select("SELECT p.Oid, CONCAT(DATE_FORMAT(p.Date, '%Y-%m-%d '), u.UserName) AS Name
            FROM possession p LEFT OUTER JOIN user u ON u.Oid = p.User         
            WHERE CONCAT(DATE_FORMAT(p.Date, '%Y-%m-%d '),u.UserName) LIKE '{$term}%' AND p.Ended IS NULL ORDER BY p.Date");
        return $data;
    }
}
