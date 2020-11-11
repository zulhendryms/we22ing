<?php

namespace App\AdminApi\Travel\Controllers;

use Validator;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Laravel\Http\Controllers\Controller;
use App\Core\Travel\Entities\TravelItemPriceBusinessPartner;
use App\Core\Security\Services\RoleModuleService;
use App\Core\Master\Entities\BusinessPartnerGroupUser;
use App\AdminApi\Development\Controllers\CRUDDevelopmentController;

class TravelItemPriceBusinessPartnerController extends Controller
{
    protected $roleService;
    private $module;
    private $crudController;
    public function __construct(
        RoleModuleService $roleService
    ) {
        $this->module = 'trvitempricebusinesspartner';
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

    public function list(Request $request)
    {
        try {
            $user = Auth::user();
            $data = DB::table($this->module.' as data');

            if ($request->has('BusinessPartnerGroup')) {
                $data = $data->where('data.BusinessPartnerGroup', $request->input('BusinessPartnerGroup'));
            }
            if ($request->has('BusinessPartner')) {
                $data = $data->where('data.BusinessPartner', $request->input('BusinessPartner'));
            }
            if ($request->has('ItemContent')) {
                $data = $data->where('data.ItemContent', $request->input('ItemContent'));
            }
            if ($request->has('Item')) {
                $data = $data->where('Item', $request->input('Item'));
            }


            // filter businesspartnergroupuser
            $businessPartnerGroupUser = BusinessPartnerGroupUser::select('BusinessPartnerGroup')->where('User', $user->Oid)->pluck('BusinessPartnerGroup');
            if ($businessPartnerGroupUser->count() > 0) {
                $data->whereIn('BusinessPartner.BusinessPartnerGroup', $businessPartnerGroupUser);
            }
            if ($businessPartnerGroupUser->count() > 0) {
                $data->whereIn('data.BusinessPartnerGroup', $businessPartnerGroupUser);
            }


            $data = $this->crudController->list($this->module, $data, $request, true);
            return response()->json($data, Response::HTTP_OK);
        } catch (\Exception $e) {
            errjson($e);
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
        $data = $this->crudController->detail($this->module, $Oid);
        // $data->Action = $this->action($data);
        return $data;
    }

    public function show($data)
    {
        try {
            $data = TravelItemPriceBusinessPartner::where('Oid', $data)->first();
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
                if (!$data) {
                    throw new \Exception('Data is failed to be saved');
                }
            });

            $role = $this->roleService->list('TravelItemPriceBusinessPartner'); //rolepermission
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

    public function destroy(TravelItemPriceBusinessPartner $data)
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

    public function presearch(Request $request)
    {
        return $this->crudController->presearch('trvitempricebusinesspartner');
    }

    public function dashboard(Request $request)
    {
        $chart = [];

        $tmp = new \stdClass();
        $tmp->Title = "Test";
        $tmp->Subtitle = "Test";
        $tmp->DataType = "Sales";
        $tmp->Sum = "amount";
        $tmp->Sequence = 1;
        $tmp->ChartType = "SquareArea";
        $tmp->Icon = "dollar-sign";
        $tmp->Color = "primary";
        $tmp->Criteria = null;

        $dashboard = new DashboardChartController();
        $chart[] = $dashboard->chartAreaSquare($tmp, 'square');
        $chart[] = $dashboard->chartAreaSquare($tmp, 'title');
        $chart[] = $dashboard->chartAreaSquare($tmp, 'landscape');

        return $chart;
    }
}
