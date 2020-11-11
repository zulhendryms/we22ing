<?php

namespace App\AdminApi\Trading\Controllers;

use Validator;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Laravel\Http\Controllers\Controller;
use App\Core\Trading\Entities\TransactionStock;
use App\Core\Internal\Entities\JournalType;
use App\Core\Internal\Entities\Status;
use App\Core\Security\Services\RoleModuleService;
use App\AdminApi\Development\Controllers\CRUDDevelopmentController;

class TransactionStockController extends Controller
{
    protected $roleService;
    private $module;
    private $crudController;
    public function __construct(
        RoleModuleService $roleService
    ) {
        $this->module = 'trdtransactionstock';
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
            $jt = JournalType::where('Code','Stock')->first();
            // $data = DB::table($this->module . ' as data')->whereNull('data.PurchaseInvoice')->whereNull('data.PointOfSale');
            $data = DB::table($this->module . ' as data')->where('data.JournalType', $jt->Oid);
            $data = $this->crudController->list($this->module, $data, $request);
            foreach ($data->data as $row) {
                $tmp = TransactionStock::where('Oid',$row->Oid)->first();
                if ($tmp) $row->Action = $this->action($tmp);
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
        try {
            $data = $this->crudController->detail($this->module, $Oid);
            $data->Action = $this->action($data);
            return $data;
        } catch (\Exception $e) {            
            return response()->json(
                errjson($e),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
    }

    public function show(TransactionStock $data)
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
                $jt = JournalType::where('Code','Stock')->first();
                if (!isset($data->JournalType)) $data->JournalType = $jt->Oid;
                $data->save();
            });
            $data = $this->showSub($data->Oid);
            return response()->json($data, Response::HTTP_OK);
        } catch (\Exception $e) {            
            return response()->json(
                errjson($e),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
    }

    public function destroy(TransactionStock $data)
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

    public function action(TransactionStock $data)
    {
        $url = 'transactionstock';
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
        $actionDelete = [
            'name' => 'Delete',
            'icon' => 'TrashIcon',
            'type' => 'confirm',
            'delete' => $url . '/{Oid}'
        ];
        $return = [];
        // switch ($data->StatusObj->Code) {
        switch ($data->Status ? $data->StatusObj->Code : "entry") {
            case "":
                $return[] = $actionPosted;
                $return[] = $actionDelete;
                break;
            case "posted":
                $return[] = $actionEntry;
                break;
            case "entry":
                $return[] = $actionPosted;
                $return[] = $actionDelete;
                break;
        }
        return $return;
    }

    public function post(TransactionStock $data)
    {
        try {
            $data->Status = Status::where('Code', 'posted')->first()->Oid;
            $data->save();
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_NOT_FOUND
            );
        }
    }
    public function unpost(TransactionStock $data)
    {
        try {
            $data->Status = Status::where('Code', 'entry')->first()->Oid;
            $data->save();
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_NOT_FOUND
            );
        }
    }
}
