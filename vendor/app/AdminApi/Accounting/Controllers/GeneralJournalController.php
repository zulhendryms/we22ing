<?php
    namespace App\AdminApi\Accounting\Controllers;

    use Validator;
    use Carbon\Carbon;
    use Illuminate\Http\Request;
    use Illuminate\Http\Response;
    use Illuminate\Support\Facades\DB;
    use Illuminate\Support\Facades\Auth;
    use App\Laravel\Http\Controllers\Controller;
    use App\Core\Accounting\Entities\GeneralJournal;
    use App\Core\Security\Services\RoleModuleService;
    use App\AdminApi\Development\Controllers\CRUDDevelopmentController;
    use App\Core\Accounting\Entities\Journal;
    use App\Core\Accounting\Services\GeneralJournalService;
    use App\Core\Internal\Entities\JournalType;

    class GeneralJournalController extends Controller
    {
        protected $roleService;
        private $module;
        private $crudController;
        protected $generalJournalService;
        public function __construct(
            GeneralJournalService $generalJournalService,
            RoleModuleService $roleService
        ) {
            $this->module = 'accgeneraljournal';
            $this->roleService = $roleService;
            $this->crudController = new CRUDDevelopmentController();
            $this->generalJournalService = $generalJournalService;
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
                foreach ($data->data as $row) {
                    $tmp = GeneralJournal::findOrFail($row->Oid);
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
                $data->Action = $this->action($data);
                return $data;
            } catch (\Exception $e) {
                err_return($e);
            }
        }

        public function show(GeneralJournal $data)
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
                    $journalType = JournalType::where('Code','GL')->first();
                    foreach ($data->Details as $row) {
                        $row->JournalType = $journalType->Oid;
                        $row->DebetBase = $row->DebetAmount;
                        $row->CreditBase = $row->CreditAmount;
                        $row->save();
                    }
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

        public function destroy(GeneralJournal $data)
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



        public function action(GeneralJournal $data)
        {
            $url = 'generaljournal';
            $actionEntry = [
                'name' => 'Change to ENTRY',
                'icon' => 'UnlockIcon',
                'type' => 'confirm',
                'post' => $url.'/{Oid}/unpost',
            ];
            $actionPosted = [
                'name' => 'Change to POSTED',
                'icon' => 'CheckIcon',
                'type' => 'confirm',
                'post' => $url.'/{Oid}/post',
            ];
            $actionCancelled = [ 
                'name' => 'Change to Cancelled',
                'icon' => 'XIcon',
                'type' => 'confirm',
                'post' => $url.'/{Oid}/cancelled',
            ];
            $actionViewJournal = [ 
                'name' => 'View Journal',
                'icon' => 'BookOpenIcon',
                'type' => 'open_grid',
                'get' => 'journal?'.$url.'={Oid}',
            ];
            $actionViewStock = [ 
                'name' => 'View Stock',
                'icon' => 'PackageIcon',
                'type' => 'open_grid',
                'get' => 'stock?'.$url.'={Oid}',
            ];
            $actionDelete = [ 
                'name' => 'Delete',
                'icon' => 'TrashIcon',
                'type' => 'confirm',
                'delete' => $url.'/{Oid}'
            ];
            $return = [];
            // switch ($data->StatusObj->Code) {
            switch ($data->Status ? $data->StatusObj->Code : "entry") {
                case "":
                    $return[] = $actionPosted;
                    $return[] = $actionDelete;
                    $return[] = $actionCancelled;
                    break;
                case "posted":
                    $return[] = $actionEntry;
                    $return[] = $actionViewJournal;
                    $return[] = $actionViewStock;
                    break;
                case "entry":
                    $return[] = $actionPosted;
                    $return[] = $actionCancelled;
                    $return[] = $actionDelete;
                    break;
            }
            return $return;
        }

                

    public function post($data)
    {
        try {
            $query = "SELECT ROUND(SUM(IFNULL(DebetBase,0)) - SUM(IFNULL(CreditBase,0)),0) AS Balance FROM accjournal WHERE GeneralJournal='{$data}'";
            $tmp = DB::select($query);
            if ($tmp) if ((int)$tmp[0]->Balance !== 0) throw new \Exception('Transaction is not balance');
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_NOT_FOUND
            );
        }
    }
    public function unpost(GeneralJournal $data)
    {
        try {
            $this->generalJournalService->unpost($data->Oid);
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_NOT_FOUND
            );
        }
    }
    public function cancelled(GeneralJournal $data)
    {
        try {
            $this->generalJournalService->cancelled($data->Oid);
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_NOT_FOUND
            );
        }
    }

    public function journal(GeneralJournal $data)
    {
        try {
            return Journal::where('GeneralJournal', $data->Oid);
            // return $data->Journals();   
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_NOT_FOUND
            );
        }
    }

            }
