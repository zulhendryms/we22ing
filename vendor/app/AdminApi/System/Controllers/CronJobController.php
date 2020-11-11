<?php

namespace App\AdminApi\System\Controllers;

use Validator;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Laravel\Http\Controllers\Controller;
use App\Core\Internal\Entities\Country;
use App\Core\Security\Services\RoleModuleService;
use App\Core\Base\Services\HttpService;
use App\Core\Internal\Services\FileCloudService;
use App\AdminApi\Development\Controllers\CRUDDevelopmentController;

class CronJobController extends Controller
{
    protected $fileCloudService;
    private $httpService;
    protected $roleService;
    private $module;
    private $crudController;
    public function __construct(
        FileCloudService $fileCloudService,
        RoleModuleService $roleService,
        HttpService $httpService
        )
    {
        $this->fileCloudService = $fileCloudService;
        $this->roleService = $roleService;
        $this->httpService = $httpService;
        $this->httpService->baseUrl('http://api1.ezbooking.co:888')->json();
        $this->module = 'cronjob';
        $this->crudController = new CRUDDevelopmentController();
    }

    public function config(Request $request)
    {
        $fields = []; //f = 'FIELD, t = TITLE
        $fields[] = serverSideConfigField('Oid');
        $fields[] = ['w'=> 120, 'h'=>0, 'n'=>'Code'];
        $fields[] = ['w'=> 300, 'h'=>0, 'n'=>'Name'];
        $fields[] = ['w'=> 300, 'h'=>0, 'n'=>'LastExecutionDate'];
        $fields = $this->crudController->jsonConfig($fields);
        $fields[0]['cellRenderer'] = 'actionCell';
        return $fields;
    }

    public function list(Request $request)
    {
        $user = Auth::user();
        $data = $this->httpService->get('/portal/api/development/cronjob/list?company='.$user->Company);
        foreach($data as $row) $row->Role = [
            'IsRead' => true,
            'IsAdd' => true,
            'IsEdit' => true,
            'IsDelete' => true,
        ];
        return $data;
    }

    public function show($data)
    {
        $result = $this->httpService->get('/portal/api/development/cronjob/show/'.$data);
        return response()->json(
            $result, Response::HTTP_CREATED
        );
    }

    public function save(Request $request, $Oid = null)
    {
        $request = json_decode(json_encode(object_to_array(json_decode($request->getContent()))))   ; //WILLIAM ZEF
        return $this->httpService->post("/portal/api/development/cronjob/save/".$Oid, $request);
    }
}
