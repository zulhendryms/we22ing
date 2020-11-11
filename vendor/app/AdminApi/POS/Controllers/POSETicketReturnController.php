<?php
    namespace App\AdminApi\POS\Controllers;

    use Validator;
    use Carbon\Carbon;
    use Illuminate\Http\Request;
    use Illuminate\Http\Response;
    use Illuminate\Support\Facades\DB;
    use Illuminate\Support\Facades\Auth;
    use App\Laravel\Http\Controllers\Controller;
    use App\Core\PointOfSale\Entities\POSETicketReturn;
    use App\Core\Security\Services\RoleModuleService;
    use App\AdminApi\Development\Controllers\CRUDDevelopmentController;

    class POSETicketReturnController extends Controller
    {
        protected $roleService;
        private $module;
        private $crudController;
        public function __construct(
            RoleModuleService $roleService
        ) {
            $this->module = 'poseticketreturn';
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
                $data = DB::table($this->module.' as data');
                $data = $this->crudController->list($this->module, $data, $request);
                foreach ($data->data as $row) $row->Action = $this->action($row->Oid);
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
                $data->Action = $this->action($data->Oid);
                return $data;
            } catch (\Exception $e) {
                err_return($e);
            }
        }

        public function show(POSETicketReturn $data)
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
                    $data->Action = $this->action($data->Oid);
                });
                return response()->json($data, Response::HTTP_OK);
            } catch (\Exception $e) {
                return response()->json(
                    errjson($e),
                    Response::HTTP_UNPROCESSABLE_ENTITY
                );
            }
        }

        public function destroy(POSETicketReturn $data)
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

        

    public function action($data)
    {
        $data = POSETicketReturn::where('Oid',$data)->first();
        $url = 'poseticketreturn';
        $actionPost = [
            'name' => 'Change to Posted',
            'icon' => 'CheckIcon',
            'type' => 'confirm',
            'post' => $url.'/{Oid}/post',
        ];
        // $actionUnpost = [
        //     'name' => 'Change to Entry',
        //     'icon' => 'UnlockIcon',
        //     'type' => 'confirm',
        //     'post' => $url.'/{Oid}/unpost',
        // ];
        // $actionCancelled = [
        //     'name' => 'Change to Cancel',
        //     'icon' => 'XIcon',
        //     'type' => 'confirm',
        //     'post' => $url.'/{Oid}/cancelled',
        // ];
        $actionPrintprereport = [
            'name' => 'Print PreReport',
            'icon' => 'PrinterIcon',
            'type' => 'open_report',
            'hide' => true,
            'get' => 'prereport/'.$url.'/{Oid}',
        ];
        $return = [];
        switch ($data->StatusObj ? $data->StatusObj->Code : 'entry') {
            case "":
                $return[] = $actionPost;
                break;
            case "posted":
                $return[] = $actionPrintprereport;
                break;
            case "entry":
                $return[] = $actionPost;
                break;
        }
        $return = actionCheckCompany($this->module, $return);
        return $return;
    }    

    public function eticketSearch(Request $request)
    {
        // pt.Oid, CONCAT(pt.Code, ' ',IFNULL(i.Name,''),' ',IFNULL(i.Code,'')) AS Name
        $query = "SELECT 
                pt.Oid, pt.Code AS Name
                FROM poseticket pt
                LEFT OUTER JOIN mstitem i ON pt.Item = i.Oid
                WHERE pt.GCRecord IS NULL
                AND pt.PointOfSale ='{$request->input('pointofsale')}'
                AND pt.Company IS NOT NULL
                AND pt.Item IS NOT NULL
                ORDER BY pt.Code
                ";
        $data = DB::select($query);
        return response()->json(
            $data,
            Response::HTTP_OK
        );
    }

    public function eticketAdd(Request $request)
    {
        try {
            $result = [];
            DB::transaction(function () use ($request, &$result) {
                $eTicketReturn = POSETicketReturn::findOrFail($request->input('oid'));
                $request = json_decode(json_encode(object_to_array(json_decode($request->getContent()))));
                $string = "";
                foreach ($request as $row) {
                    $string = ($string ? $string . "," : null) . "'" . $row . "'";
                }
                $query = "SELECT pt.*, i.Name AS Items
                        FROM poseticket pt
                        LEFT OUTER JOIN mstitem i ON pt.Item = i.Oid
                        WHERE pt.GCRecord IS NULL AND pt.Oid IN ({$string})
                    ";
                $data = DB::select($query);
                foreach ($data as $row) {
                    $poseticket = ETicket::findOrFail($row->Oid);
                    $details = new POSETicketReturnDetail();
                    $details->Company = $eTicketReturn->Company;
                    $details->POSETicketReturn = $eTicketReturn->Oid;
                    $details->POSETicket = $poseticket->Oid;
                    $details->Item = $poseticket->Item;
                    $details->save();
                    $details->ItemObj = $poseticket->ItemObj;

                    $result[] = $details;
                }
            });

            return response()->json(
                $result,
                Response::HTTP_CREATED
            );
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
    }

    public function statusPost(POSETicketReturn $data)
    {
        try {
            DB::transaction(function () use (&$data) {
                $data->Status = Status::where('Code', 'Posted')->first()->Oid;
                $data->save();

                foreach ($data->Details as $row) {
                    $detail = ETicket::where('Oid',$row->POSETicket)->first();
                    $detail->PointOfSale = null;
                    $detail->save();
                }
            });
            $data = $this->showSub($data->Oid);

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
