<?php

namespace App\AdminApi\POS\Controllers;

use Validator;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Laravel\Http\Controllers\Controller;
use App\Core\POS\Entities\ETicket;
use App\Core\PointOfSale\Entities\POSETicketWithdraw;
use App\Core\PointOfSale\Entities\POSETicketWithdrawDetail;
use App\Core\Security\Services\RoleModuleService;
use App\AdminApi\Development\Controllers\CRUDDevelopmentController;

class POSETicketWithdrawController extends Controller
{
    protected $roleService;
    private $module;
    private $crudController;
    public function __construct(
        RoleModuleService $roleService
    ) {
        $this->roleService = $roleService;
        $this->module = 'poseticketwithdraw';
        $this->crudController = new CRUDDevelopmentController();
    }

    public function config(Request $request)
    {
        try {
            return $this->crudController->config($this->module);
        } catch (\Exception $e) {
  return response()->json(
  errjson($e), 
Response::HTTP_UNPROCESSABLE_ENTITY);
}
    }

    public function presearch(Request $request)
    {
        try {
            return $this->crudController->presearch($this->module);
        } catch (\Exception $e) {
  return response()->json(
  errjson($e), 
Response::HTTP_UNPROCESSABLE_ENTITY);
}
    }

    public function list(Request $request)
    {
        try {
            $data = DB::table('poseticketwithdraw as data');
            $data = $this->crudController->list('poseticketwithdraw', $data, $request,true);
            return response()->json($data, Response::HTTP_OK);
        } catch (\Exception $e) {
  return response()->json(
  errjson($e), 
Response::HTTP_UNPROCESSABLE_ENTITY);
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
Response::HTTP_UNPROCESSABLE_ENTITY);
}
    }

    private function showSub($Oid)
    {
        $data = $this->crudController->detail($this->module, $Oid);
        $data->Action = $this->action($data);
        return $data;
    }

    public function show(POSETicketWithdraw $data)
    {
        try {
            return $this->showSub($data->Oid);
        } catch (\Exception $e) {
  return response()->json(
  errjson($e), 
Response::HTTP_UNPROCESSABLE_ENTITY);
}
    }

    public function save(Request $request, $Oid = null)
    {
        try {
            $data;
            DB::transaction(function () use ($request, &$data, $Oid) {
                $data = $this->crudController->saving($this->module, $request, $Oid, false);

                $tmp = []; // textarea multirow
                foreach (preg_split("/((\r?\n)|(\r\n?))/", $request->ETicketList) as $line) {
                    $tmp = array_merge($tmp, [$line]);
                }
                $etickets = ETicket::whereIn('Code',$tmp)->get();
                foreach ($etickets as $row) {
                    $detail = POSETicketWithdrawDetail::where('POSEticket',$row->Oid)->first();
                    if (!$detail) $detail = new POSETicketWithdrawDetail();
                    $detail->Company = $data->Company;
                    $detail->POSETicketWithdraw = $data->Oid;
                    $detail->POSEticket = $row->Oid;
                    $detail->save();
                }

                if (!$data) throw new \Exception('Data is failed to be saved');
            });

            $role = $this->roleService->list('POSETicketWithdraw'); //rolepermission
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


    public function destroy(SalesInvoPOSETicketWithdrawice $data)
    {
        try {
            return $this->crudController->delete($this->module, $data);
        } catch (\Exception $e) {
  return response()->json(
  errjson($e), 
Response::HTTP_UNPROCESSABLE_ENTITY);
}
    }
}
