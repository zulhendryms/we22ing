<?php

namespace App\AdminApi\POS\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

use App\Laravel\Http\Controllers\Controller;
use App\Core\POS\Entities\ETicket;
use App\Core\POS\Entities\POSETicketUpload;
use App\Core\POS\Entities\POSETicketLog;
use Illuminate\Support\Facades\DB;
use App\Core\POS\Services\POSETicketService;
use App\Core\Accounting\Services\POSETicketUploadPostService;
use App\Core\Travel\Entities\TravelTransactionDetail;
use App\Core\Base\Services\TravelAPIService;
use Illuminate\Support\Facades\Auth;
use App\Core\Security\Services\RoleModuleService;
use App\Core\Internal\Services\ExportExcelService;
use App\Core\Internal\Services\AutoNumberService;
use Validator;
use Carbon\Carbon;
use App\AdminApi\Development\Controllers\CRUDDevelopmentController;

class POSETicketUploadController extends Controller
{
    protected $roleService;
    protected $posETicketService;
    protected $posEticketUploadPostService;
    protected $excelExportService;
    private $autoNumberService;
    private $travelAPIService;
    private $crudController;
    public function __construct(
        POSETicketService $posETicketService,
        POSETicketUploadPostService $posEticketUploadPostService,
        RoleModuleService $roleService,
        ExportExcelService $excelExportService,
        TravelAPIService $travelAPIService,
        AutoNumberService $autoNumberService
        )
    {
        $this->roleService = $roleService;
        $this->posETicketService = $posETicketService;
        $this->posEticketUploadPostService = $posEticketUploadPostService;
        $this->excelExportService = $excelExportService;
        $this->travelAPIService = $travelAPIService;
        $this->autoNumberService = $autoNumberService;
        $this->crudController = new CRUDDevelopmentController();
    }

    public function summaryPresearch(Request $request) {
        return [
            [
                'fieldToSave' => "ItemContent",
                'type' => "autocomplete",
                'column' => "1/2",
                'source' => [],
                'store' => "autocomplete/itemcontent",
                'hideLabel' => true,
                'params' => [
                    'term' => "",
                    'type' => "combo",
                    'itemtypecode' => 'Attraction'
                ],
                'hiddenField' => "ItemContentName"
            ],
            [
                'type' => 'action',
                'column' => '1/5'
            ]
        ];
    }
    public function summaryFields() {      
        $fields = []; //f = 'FIELD, t = TITLE
        $fields[] = ['w'=> 0, 'r'=>0, 't'=>'text', 'n'=>'Oid', 'f'=>'ItemContent.Oid'];
        $fields[] = ['w'=> 150, 'r'=>0, 't'=>'text', 'n'=>'Code', 'f'=>'ItemContent.Code'];
        $fields[] = ['w'=> 400, 'r'=>0, 't'=>'text', 'n'=>'Name', 'f'=>'ItemContent.Name'];
        $fields[] = ['w'=> 100, 'r'=>0, 't'=>'int', 'n'=>'Stock', 'f'=>'data.Oid', 'count'=>1];
        return $fields;
    }
    public function summaryConfig(Request $request) {
        $fields = $this->summaryFields();
        $fields = $this->crudController->jsonConfig($fields);
        $fields[0]['cellRenderer'] = 'actionCell';
        return $fields;
    }
    public function summaryList(Request $request) {
        $fields = $this->summaryFields();
        $data = DB::table('poseticket as data')
            ->leftJoin('company AS Company', 'Company.Oid', '=', 'data.Company')
            ->leftJoin('mstitem AS Item', 'Item.Oid', '=', 'data.item')
            ->leftJoin('mstitemcontent AS ItemContent', 'ItemContent.Oid', '=', 'Item.ItemContent')
            ->select(['ItemContent.Oid AS Oid','ItemContent.Code','ItemContent.Name'])
            ->whereNull('data.PointOfSale')->whereNotNull('data.Item')
            ->groupBy('Item.Name','Item.Oid','data.Type')
            ;
        if ($request->has('ItemContent')) {
            if ($request->input('ItemContent') == 'null') $data = $data->whereNull('item.ItemContent'); 
            else $data = $data->whereRaw("item.ItemContent >= '".$request->input('ItemContent')."'");
        }
        $data = $this->crudController->jsonList($data, $fields, $request, 'poseticket','Item.Name');
        foreach($data as $row) $row->Action = [
                [                    
                    'name' => 'Open',
                    'icon' => 'ViewIcon',
                    'type' => 'open_form',
                    'url' => 'stocketicket/list?ItemContent={Oid}',
                ]
            ];
        // 'poseticketupload/eticket?item='.$row->Oid.'&costprice='.$row->CostPrice.'&dateexpire='.$row->DateExpiry.'&get=%2Fposeticketupload%2Fdetaillist',        
        return $this->crudController->jsonListReturn($data, $fields);
    }

    public function listPresearch(Request $request) {
        return [
            [
                'fieldToSave' => "OrderBy",
                'overrideLabel' => 'Order By',
                'type' => "combobox",
                'column' => "1/5",
                'source' => [],
                'store' => "",
                'source' => [
                    ['Oid' => 'Name', 'Name' => 'Name & Expiry'],
                    ['Oid' => 'Expiry', 'Name' => 'Expiry'],
                ],
                'defaultValue' => "Name"
            ],
            [
                'fieldToSave' => 'DateValidFrom',
                'overrideLabel' => 'Valid After',
                'type' => 'inputdate',
                'column' => '1/5',
                'default' =>  '2010-01-01',
            ],
            [
                'fieldToSave' => 'DateExpiry',
                'overrideLabel' => 'Expiry Before',
                'type' => 'inputdate',
                'column' => '1/5',
                'default' => '2050-01-01',
            ],
            [

                'fieldToSave' => "ItemContent",
                'type' => "autocomplete",
                'column' => "1/3",
                'source' => [],
                'store' => "autocomplete/itemcontent",
                'params' => [
                    'term' => "",
                    'type' => "combo",
                    'itemtypecode' => 'Attraction'
                ],
                'hiddenField' => "ItemContentName"
            ],
            [
                'type' => 'action',
                'column' => '1/5'
            ]
        ];
    }
    public function listConfig(Request $request) {
        $fields = $this->listFields();
        $fields = $this->crudController->jsonConfig($fields);
        $fields[0]['cellRenderer'] = 'actionCell';
        $i = 0;
        foreach($fields as $row) {
            $fields[$i]['cellStyle'] = [
                'border' => '0.1px solid #f2f2f2',
                'paddingLeft' => '5px !important',
                'paddingRight' => '1px !important',
                'fontSize' => '9px'
            ];
            $i = $i + 1;
        }
        return $fields;
    }
    private function listFields() {
        $fields = []; //f = 'FIELD, t = TITLE
        $fields[] = ['w'=> 0, 'r'=>0, 't'=>'text', 'n'=>'Oid', 'f'=>'Item.Oid'];
        $fields[] = ['w'=> 180, 'r'=>0, 't'=>'text', 'n'=>'Code', 'f'=>'Item.Code'];
        $fields[] = ['w'=> 500, 'r'=>0, 't'=>'text', 'n'=>'Name', 'f'=>'Item.Name'];
        $fields[] = ['w'=> 70, 'r'=>0, 't'=>'text', 'n'=>'Stock', 'f'=>'data.Oid', 'count'=>1];
        $fields[] = ['w'=> 70, 'r'=>0, 't'=>'text', 'n'=>'CostPrice', 'f'=>'data.CostPrice'];
        $fields[] = ['w'=> 100, 'r'=>0, 't'=>'text',  'n'=>'DateValidFrom', 'f'=>'data.DateValidFrom'];
        $fields[] = ['w'=> 100, 'r'=>0, 't'=>'text',  'n'=>'DateExpiry', 'f'=>'data.DateExpiry'];
        return $fields;
    }
    public function listList(Request $request) {
            $fields = $this->listFields();
            $data = DB::table('poseticket as data')
                ->leftJoin('company AS Company', 'Company.Oid', '=', 'data.Company')
                ->leftJoin('mstitem AS Item', 'Item.Oid', '=', 'data.item')
                ->select(['Item.Oid AS Oid','Item.Code','Item.Name','data.CostPrice','data.DateValidFrom', 'data.DataExpiry'])
                ->whereNull('data.PointOfSale')->whereNotNull('data.Item')->whereNull('data.GCRecord')
                ->groupBy('data.CostPrice','data.DateExpiry','Item.Name','Item.Oid','data.Type','data.DateValidFrom')
                ;
            if ($request->has('DateValidFrom')) {
                if ($request->input('DateValidFrom') == 'null') $data = $data->whereNull('data.DateValidFrom'); 
                else $data = $data->whereRaw("data.DateValidFrom >= '".$request->input('DateValidFrom')."'");
            }
            if ($request->has('DateExpiry')) {
                if ($request->input('DateExpiry') == 'null') $data = $data->whereNull('data.DateExpiry'); 
                else $data = $data->whereRaw("data.DateExpiry <= '".$request->input('DateExpiry')."'");
            }
            if ($request->has('ItemContent')) {
                if ($request->input('ItemContent') == 'null') $data = $data->whereNull('Item.ItemContent'); 
                else $data = $data->whereRaw("Item.ItemContent = '".$request->input('ItemContent')."'");
            }
        $sort = $request->has('OrderBy') ? $request->input('OrderBy') : 'Name';
        if ($sort == 'Name') $data = $this->crudController->jsonList($data, $fields, $request, 'poseticket','Item.Name', 'data.DateExpiry');
        else $data = $this->crudController->jsonList($data, $fields, $request, 'poseticket','data.DateExpiry', 'Item.Name');
        foreach($data as $row) {
            $row->DateValidFrom = Carbon::parse($row->DateValidFrom)->format('Y-m-d');
            $row->DateExpiry = Carbon::parse($row->DateExpiry)->format('Y-m-d');
            $row->Action = [
                [                    
                    'name' => 'Open',
                    'icon' => 'ViewIcon',
                    'type' => 'open_form',
                    'url' => 'stocketicket/detail?Item={Oid}&CostPrice={CostPrice}&DateValidFrom={DateValidFrom}&DateExpiry={DateExpiry}',
                ]
            ];
        }
        // 'poseticketupload/eticket?item='.$row->Oid.'&costprice='.$row->CostPrice.'&dateexpire='.$row->DateExpiry.'&get=%2Fposeticketupload%2Fdetaillist',        
        return $this->crudController->jsonListReturn($data, $fields);
    }    

    public function detailPresearch(Request $request) {
        return [
            [
                'fieldToSave' => "Item",
                'type' => "autocomplete",
                'column' => "1/5",
                'source' => [],
                'store' => "autocomplete/item",
                'params' => [
                    'term' => "",
                    'type' => "combo",
                    'auto_stock' => "1",
                    'itemtypecode' => 'Attraction'
                ],
                'hiddenField' => "ItemName"
            ],
            [
                'fieldToSave' => 'CostPrice',
                'type' => 'inputtext',
                'column' => '1/5',
            ],
            [
                'fieldToSave' => 'DateValidFrom',
                'overrideLabel' => 'Valid After',
                'type' => 'inputdate',
                'column' => '1/5',
                'default' =>  '2010-01-01',
            ],
            [
                'fieldToSave' => 'DateExpiry',
                'overrideLabel' => 'Expiry Before',
                'type' => 'inputdate',
                'column' => '1/5',
                'default' => '2050-01-01',
            ],
            [
                'type' => 'action',
                'column' => '1/6'
            ]
        ];
    }
    private function detailfields() {
        $fields = []; //f = 'FIELD, t = TITLE
        $fields[] = ['w'=> 0, 'r'=>0, 't'=>'text', 'n'=>'Oid', 'f'=>'data.Oid'];
        $fields[] = ['w'=> 0, 'h'=>1, 'r'=>0, 't'=>'text', 'n'=>'Item', 'f'=>'data.Item'];
        $fields[] = ['w'=> 200, 'r'=>0, 't'=>'text', 'n'=>'Code', 'f'=>'data.Code'];
        $fields[] = ['w'=> 450, 'r'=>0, 't'=>'text', 'n'=>'Name', 'f'=>'Item.Name'];
        // $fields[] = ['w'=> 0, 'h'=>1, 'r'=>0, 't'=>'text', 'n'=>'Initial', 'f'=>'Item.Initial'];
        // $fields[] = ['w'=> 0, 'h'=>1, 'r'=>0, 't'=>'text', 'n'=>'ItemContent', 'f'=>'ItemContent.Name'];
        $fields[] = ['w'=> 0, 'h'=>1, 'r'=>0, 't'=>'text', 'n'=>'PurchaseInvoice', 'f'=>'PurchaseInvoice.Code',];
        $fields[] = ['w'=> 100, 'r'=>0, 't'=>'date', 'f'=>'data.CreatedAt', 'n'=>'CreatedAt'];
        // $fields[] = ['w'=> 0, 'r'=>0, 't'=>'text', 'n'=>'data.FileName'];
        $fields[] = ['w'=> 70, 'r'=>0, 't'=>'text', 'f'=>'data.Type', 'n'=>'Type'];
        $fields[] = ['w'=> 100, 'r'=>0, 't'=>'date', 'f'=>'data.DateExpiry', 'n'=>'DateExpiry'];
        $fields[] = ['w'=> 100, 'r'=>0, 't'=>'date', 'f'=>'data.DateValidFrom', 'n'=>'DateValidFrom'];
        $fields[] = ['w'=> 100, 'r'=>0, 't'=>'double', 'f'=>'data.CostPrice', 'n'=>'CostPrice'];
        return $fields;
    }
    public function detailconfig(Request $request) {
        $fields = $this->detailfields();
        $fields = $this->crudController->jsonConfig($fields);
        // if (gettype($row) == 'array') $row = json_decode(json_encode($row), FALSE);
        // dd($fields);
        
        $fields[0]['cellRenderer'] = 'actionCell';
        $fields[0]['topButton'] = [
            [
                'name' => 'Amendment Based on Selection',
                'icon' => 'EditIcon',
                'type' => 'global_form',
                'showModal' => false,
                'post' => 'stocketicket/amendment',
                'afterRequest' => 'apply',
                'form' => [
                    [ 
                        'fieldToSave' => 'Oid',
                        'type' => 'selectedrows' 
                    ],
                    [ 
                        'fieldToSave' => 'DateValidFrom',
                        'type' => 'inputdate' 
                    ],
                    [ 
                        'fieldToSave' => 'DateExpiry',
                        'type' => 'inputdate' 
                    ],
                    [ 
                        'fieldToSave' => 'CostPrice',
                        'validationParams' => 'decimal',
                        'type' => 'inputtext' 
                    ],
                ]
            ]
        ];
        $fields[3]['checkboxSelection'] = 'true';
        $fields[3]['headerCheckboxSelection'] = 'true';
        return $fields;
    }
    public function detaillist(Request $request) {
        $fields = $this->detailfields();
        $company = Auth::user()->Company;
        $data = DB::table('poseticket as data') //jointable
            ->leftJoin('company AS Company', 'Company.Oid', '=', 'data.Company')
            ->leftJoin('mstitem AS Item', 'Item.Oid', '=', 'data.Item')
            ->leftJoin('mstitemcontent AS ItemContent', 'ItemContent.Oid', '=', 'Item.ItemContent')
            ->leftJoin('trdpurchaseinvoice AS PurchaseInvoice', 'PurchaseInvoice.Oid', '=', 'data.PurchaseInvoice')
            ->whereNull('data.PointOfSale')
            ->orderBy('data.DateExpiry')->orderBy('data.Code');
        if ($request->has('Item')) {
            if ($request->input('Item') == 'null') $data = $data->whereNull('data.Item'); 
            else $data->whereRaw("data.Item = '".$request->input('Item')."'");
        }
        if ($request->has('DateValidFrom')) {
            if ($request->input('DateValidFrom') == 'null') $data = $data->whereNull('data.DateValidFrom'); 
            else $data = $data->whereRaw("data.DateValidFrom = '".$request->input('DateValidFrom')."'");
        }
        if ($request->has('DateExpiry')) {
            if ($request->input('DateExpiry') == 'null') $data = $data->whereNull('data.DateExpiry'); 
            else $data = $data->whereRaw("data.DateExpiry = '".$request->input('DateExpiry')."'");
        }
        if ($request->has('CostPrice')) {
            if ($request->input('CostPrice') == 'null') $data = $data->whereNull("data.CostPrice"); 
            else $data = $data->whereRaw("data.CostPrice = ".$request->input('CostPrice'));
        }
        $data = $this->crudController->jsonList($data, $fields, $request, 'poseticket','data.Oid');
        return $data;
        // foreach($data as $row) {
        //     if ($request->has('Item')) if ($request->input('Item') != 'null') $data = $row->Name = $row->Initial ? $row->Initial : $row->Subtitle;
        //    $data =  else $row->Name = $row->ItemContent.' '.($row->Initial ? $row->Initial : $row->Code);
        // }
        return $this->crudController->jsonListReturn($data, $fields);
    }
    
    public function stockpopupPresearch(Request $request) {
        return [
            [
                'fieldToSave' => 'DateValidFrom',
                'overrideLabel' => 'Valid After',
                'type' => 'inputdate',
                'column' => '1/5',
                'default' =>  '2010-01-01',
            ],
            [
                'fieldToSave' => 'DateExpiry',
                'overrideLabel' => 'Expiry Before',
                'type' => 'inputdate',
                'column' => '1/5',
                'default' => '2050-01-01',
            ],
            [
                'fieldToSave' => 'CostPrice',
                'type' => 'inputtext',
                'column' => '1/5',
            ],
            [
                'type' => 'action',
                'column' => '1/6'
            ]
        ];
    }
    private function stockpopupFields() {
        $fields = []; //f = 'FIELD, t = TITLE
        $fields[] = ['w'=> 0, 'r'=>0, 't'=>'text', 'n'=>'Oid', 'f'=>'data.Oid'];
        $fields[] = ['w'=> 200, 'r'=>0, 't'=>'text', 'n'=>'Code', 'f'=>'data.Code'];
        $fields[] = ['w'=> 100, 'r'=>0, 't'=>'date', 'f'=>'data.CreatedAt', 'n'=>'CreatedAt'];
        $fields[] = ['w'=> 70, 'r'=>0, 't'=>'text', 'f'=>'data.Type', 'n'=>'Type'];
        $fields[] = ['w'=> 100, 'r'=>0, 't'=>'date', 'f'=>'data.DateExpiry', 'n'=>'DateExpiry'];
        $fields[] = ['w'=> 100, 'r'=>0, 't'=>'date', 'f'=>'data.DateValidFrom', 'n'=>'DateValidFrom'];
        $fields[] = ['w'=> 100, 'r'=>0, 't'=>'double', 'f'=>'data.CostPrice', 'n'=>'CostPrice'];
        return $fields;
    }
    public function stockpopupConfig(Request $request) {
        $fields = $this->stockpopupFields();
        $fields = $this->crudController->jsonConfig($fields);
        $fields[0]['cellRenderer'] = 'actionCell';
        $fields[0]['topButton2'] = [
            [
                'name' => 'Add',
                'icon' => 'EditIcon',
                'type' => 'select',
                'afterRequest' => 'back',
                'post' => 'traveltransaction/eticketmanualallocate?oid={Item}',
                // 'body' => [
                //     'item' => 'item',
                //     'DateValidFrom' => 'DateValidFrom',
                //     'DateExpiry' => 'DateExpiry',
                // ]
            ],
            [
                'name' => 'Add All',
                'icon' => 'EditIcon',
                'type' => 'selectall',
                'afterRequest' => 'back',
                'post' => 'traveltransaction/eticketmanualallocate?oid={Item}'
            ],
            [
                'name' => 'Back',
                'icon' => 'EditIcon',
                'type' => 'back'
            ]
        ];
        $fields[2]['checkboxSelection'] = 'true';
        $fields[2]['headerCheckboxSelection'] = 'true';
        return $fields;
    }
    public function stockpopupList(Request $request) {
        $fields = $this->stockpopupFields();
        $company = Auth::user()->Company;
        $data = DB::table('poseticket as data') //jointable
            ->leftJoin('company AS Company', 'Company.Oid', '=', 'data.Company')
            ->leftJoin('mstitem AS Item', 'Item.Oid', '=', 'data.Item')
            ->leftJoin('mstitemcontent AS ItemContent', 'ItemContent.Oid', '=', 'Item.ItemContent')
            ->leftJoin('trdpurchaseinvoice AS PurchaseInvoice', 'PurchaseInvoice.Oid', '=', 'data.PurchaseInvoice')
            ->whereNull('data.PointOfSale');
            // ->orderBy('data.Code');
        if ($request->has('Item')) {
            if ($request->input('Item') == 'null') $data = $data->whereNull('data.Item'); 
            else {
                $tmp = TravelTransactionDetail::findOrFail($request->input('Item'));
                $data = $data->where("data.Item", $tmp->Item);
            }
        }
        if ($request->has('DateValidFrom')) {
            if ($request->input('DateValidFrom') == 'null' && $request->input('DateValidFrom') != '') $data = $data->whereNull('data.DateValidFrom'); 
            else $data = $data->whereRaw("data.DateValidFrom >= '".$request->input('DateValidFrom')."'");
        }
        if ($request->has('DateExpiry')) {
            if ($request->input('DateExpiry') == 'null' && $request->input('DateExpiry') != '') $data = $data->whereRaw("data.DateExpiry <= '".$request->input('DateExpiry')."'");
        }
        if ($request->has('CostPrice')) {
            if ($request->input('CostPrice') != 'null' && $request->input('CostPrice') != '') $data = $data->whereRaw("data.CostPrice = ".$request->input('CostPrice'));
        }
        $data = $this->crudController->jsonList($data, $fields, $request, 'poseticket','data.Oid');
        return $this->crudController->jsonListReturn($data, $fields);
    }

    public function index(Request $request)
    {        
        try {           
            $user = Auth::user();
            $type = $request->input('type') ?: 'combo';
            $data = POSETicketUpload::whereNull('GCRecord');
            if ($user->BusinessPartner) $data = $data->where('BusinessPartner', $user->BusinessPartner);
            $data = $data->get();
            return $data;
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_NOT_FOUND
            );
        }
    }
    
    public function show(POSETicketUpload $data)
    {
        try {            
            $data = POSETicketUpload::with('ETickets')
                ->with(['ItemObj' => function ($query) {$query->addSelect('Oid','Code','Name');}, ])
                ->with(['BusinessPartnerObj' => function ($query) {$query->addSelect('Oid','Code','Name');}, ])
                ->with(['ETickets.PointOfSaleObj' => function ($query) {$query->addSelect('Oid','Code','Date');}, ])
            ->findOrFail($data->Oid);
            return $data;
            // return (new POSEticketUploadResource($data))->type('detail');
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_NOT_FOUND
            );
        }
    }

    public function insert(Request $request)
    {
        try {            
            $data = new POSETicketUpload();
            DB::transaction(function () use ($request,&$data) {
                $data->Item = $request->Item;
                $data->CostPrice = $request->CostPrice;
                $data->Note = $request->Note;
                $data->save();
            });

            return $data;
            return response()->json(
                $data, Response::HTTP_CREATED
            );
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
    }

    public function save(Request $request, $Oid = null)
    {        
        $request = json_decode(json_encode(object_to_array(json_decode($request->getContent()))))   ; //WILLIAM ZEF
        // $dataArray = object_to_array($request);
        
        // $messsages = array(
        //     'Item.required'=>__('_.Item').__('error.required'),
        //     'Item.exists'=>__('_.Item').__('error.exists'),
        // );
        // $rules = array(
        //     'Item' => 'required|exists:mstitem,Oid',
        // );

        // $validator = Validator::make($dataArray, $rules,$messsages);

        // if ($validator->fails()) {
        //     return response()->json(
        //         $validator->messages(),
        //         Response::HTTP_UNPROCESSABLE_ENTITY
        //     );
        // }

        try {            
            if (!$Oid) $data = new POSETicketUpload();
            else $data = POSETicketUpload::findOrFail($Oid);
            DB::transaction(function () use ($request, &$data) {
                $disabled = ['Oid','ETickets','GCRecord','OptimisticLock','CreatedAt','UpdatedAt','CreatedAtUTC','UpdatedAtUTC','CreatedBy','UpdatedBy'];
                foreach ($request as $field => $key) {
                    if (in_array($field, $disabled)) continue;
                    $data->{$field} = $request->{$field};
                }
                $data->save();            
                if ($data->Code == '<<Auto>>') $data->Code = $this->autoNumberService->generate($data, 'poseticketupload');

                if ($data->ETickets()->count() != 0) {
                    foreach ($data->ETickets as $rowdb) {
                        $found = false;               
                        foreach ($request->ETickets as $rowapi) {
                            if (isset($rowapi->Oid)) {
                                if ($rowdb->Oid == $rowapi->Oid) $found = true;
                            }
                        }
                        if (!$found) {
                            $detail = ETicket::findOrFail($rowdb->Oid);
                            $detail->delete();
                        }
                    }
                }
                if($request->ETickets) {
                    $details = [];  
                    $disabled = ['Oid','POSETicketUpload','GCRecord','OptimisticLock','CreatedAt','UpdatedAt','PointOfSale','PointOfSaleObj','CreatedAtUTC','UpdatedAtUTC','CreatedBy','UpdatedBy'];
                    foreach ($request->ETickets as $row) {
                        if (isset($row->Oid)) {
                            $detail = ETicket::findOrFail($row->Oid);
                            foreach ($row as $field => $key) {
                                if (in_array($field, $disabled)) continue;
                                $detail->{$field} = $row->{$field};
                            }
                            $detail->save();
                        } else {
                            $arr = [];
                            foreach ($row as $field => $key) {
                                if (in_array($field, $disabled)) continue;                            
                                $arr = array_merge($arr, [
                                    $field => $row->{$field},
                                ]);
                            }
                            $details[] = new ETicket($arr);
                        }
                    }
                    $data->ETickets()->saveMany($details);
                    $data->load('ETickets');
                    $data->fresh();
                }
                if(!$data) throw new \Exception('Data is failed to be saved');
            });

            return $data;
            return response()->json(
                $data, Response::HTTP_CREATED
            );
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
    }

    public function destroy(POSETicketUpload $data)
    {
        try {            
            DB::transaction(function () use ($data) {
                $data->ETickets()->delete();
                $data->delete();
            });
            return response()->json(
                null, Response::HTTP_NO_CONTENT
            );
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
    }


    public function deleteEticket($Oid)
    {        
        $poseticketupload = ETicket::findOrFail($Oid)->ETicketUpload;
        try {            
            DB::transaction(function () use ( $Oid, &$poseticketupload) {                
                $data = ETicket::findOrFail($Oid);
                $deleted = $data->delete();
                if($deleted && $poseticketupload != null){
                    $dataPOSEticketUpload = POSETicketUpload::with('ETickets')->findOrFail($poseticketupload);
                    $dataPOSEticketUpload->Count = $dataPOSEticketUpload->ETickets()->count();
                    $dataPOSEticketUpload->save();
                }
            });

            return response()->json(
                null, Response::HTTP_NO_CONTENT
            );
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
    }

    public function upload(Request $request, $Oid = null)
    {
        try {            
            logger(1);
            $input = json_decode(json_encode(object_to_array(json_decode($request->getContent()))))   ; //WILLIAM ZEF
            
            DB::transaction(function () use ( $input, $request, &$data, $Oid) {
                
                // $request->file('POSEticketFile')->storeAs(
                //     '', $data->FileName);

                $files = $request->file('POSEticketFile');
                logger($files);
                $poseticket = POSETicketUpload::findOrFail($Oid);
                $item = $poseticket->Item;
                $cost = $poseticket->CostPrice;
                // itemUpload
                foreach ($files as $key => $value) {
                    logger(1);
                    $eticket = $this->posETicketService->create($value, [ 
                        'ETicketUpload' => $Oid,
                        'Item' => $item, 
                        'CostPrice' => $cost,
                        'DateExpiry' => null,
                        // 'ETicketUpload' => $request->input('ETicketUpload')
                    ]);
                    logger(2);
                    $result[] = $eticket->Oid;
                }
                // foreach ($files as $key => $value) {
                //     $data = new ETicket();
                //     $data->ETicketUpload = $Oid;
                //     $data->Item = $item; //$input->Item;
                //     $data->CostPrice = $cost; //$input->CostPrice;
                //     $data->save();

                //     $pos = $data->PointOfSaleObj;
                //     $name = $data->Oid;
                //     if (isset($pos)) $name .= "_{$pos->Oid}_{$pos->Code}";
                //     $name .= "_".str_random(16);
                //     $data->FileName = $name;
                //     $data->save();                    
                //     $value->storeAs(
                //         'private/pos/etickets', $data->FileName.'.pdf'
                //     );
                // }
            });

            // $data = (new POSEticketUploadResource($data))->type('detail');
            $data = POSETicketUpload::with('ETickets')->findOrFail($Oid);
            $data->Count = $data->ETickets()->count();
            $data->save();
            return response()->json(
                $data, Response::HTTP_CREATED
            );
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_NOT_FOUND
            );
        }
    }

    public function post(POSETicketUpload $data)
    {
        try {
            $this->posEticketUploadPostService->post($data->Oid);
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
    }

    public function unpost(POSETicketUpload $data)
    {
        try {
            $this->posEticketUploadPostService->unpost($data->Oid);
            
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
    }

    public function amendment(Request $request)
    {
        try {
            $data;
            DB::transaction(function () use ($request, &$data) {
                $request = json_decode(json_encode(object_to_array(json_decode($request->getContent()))))   ; //WILLIAM ZEF
                
                if (!isset($request->ETicketList)) $data = ETicket::whereIn('Oid',$request->Oid)->get(); // tick
                else {
                    $etickets = []; // textarea multirow
                    // foreach (preg_split("/((\r?\n)|(\r\n?))/", $req->eticketlist) as $line) {
                    foreach (preg_split("/((\r?\n)|(\r\n?))/", $request->ETicketList) as $line) {
                        $etickets = array_merge($etickets, [$line]);
                    }
                    $data = ETicket::whereIn('Oid',$etickets)->get();      
                }
                
                foreach($data as $row) {
                    if (isset($request->DateValidFrom)) $row->DateValidFrom = $request->DateValidFrom;
                    if (isset($request->DateExpiry)) $row->DateExpiry = $request->DateExpiry;
                    if (isset($request->CostPrice)) $row->CostPrice = $request->CostPrice;
                    $row->save();

                    $detail = new POSEticketLog();
                    $detail->POSEticket = $row->Oid;
                    $detail->CostPrice = $row->CostPrice;
                    $detail->DateValidFrom = Carbon::parse($row->DateValidFrom)->format('Y-m-d');
                    $detail->DateExpiry = Carbon::parse($row->DateExpiry)->format('Y-m-d');
                    $detail->Description = 'Updated cost & expiry';
                    $detail->save();
                }
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

    public function unlink(Request $request)
    {
        try {
            DB::transaction(function () use ($request) {
                $Etickets = $request->query('eticket');

                if($Etickets){
                    $etickets = '';
                    foreach ($Etickets as $key => $value) {
                        $etickets = ($etickets ? $etickets."," : "")."'".$value."'";
                    }
                }

                $query = "SELECT e.*, ic.APIType AS ItemAPIType
                    FROM poseticket e
                    LEFT OUTER JOIN pospointofsale p ON e.PointOfSale = p.Oid
                    LEFT OUTER JOIN traveltransaction t ON p.Oid = t.Oid
                    LEFT OUTER JOIN trvtransactiondetail d ON d.TravelTransaction = t.Oid
                    LEFT OUTER JOIN mstitem i ON d.Item = i.Oid
                    LEFT OUTER JOIN mstitemcontent ic ON i.ItemContent = ic.Oid
                    WHERE e.Oid IN ($etickets)";
                $data = DB::select($query);
                
                foreach($data as $row) {
                    if($row->ItemAPIType == 'manual_gen' || $row->ItemAPIType == 'manual_up'){
                        $query = "DELETE FROM poseticket WHERE Oid = '{$row->Oid}'";
                        DB::delete($query);
                    }else{
                        $query = "UPDATE poseticket e SET e.PointOfSale = NULL, e.TravelTransactionDetail = NULL WHERE e.Oid = '{$row->Oid}'";
                        DB::update($query);

                        $detail = new POSEticketLog();
                        $detail->POSEticket = $row->Oid;
                        $detail->Description = 'unlink';
                        $detail->save();
                    }

                    
                }
            });

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

    public function removeEticket(Request $request, $Oid)
    {
        $token = $request->bearerToken();
        try {
            DB::transaction(function () use ($Oid, $token) {
                $company = Auth::user()->Company;
                $query = "SELECT e.*, ic.APIType AS ItemAPIType, d.APIType AS TransactionAPIType
                    FROM poseticket e
                    LEFT OUTER JOIN trvtransactiondetail d ON e.TravelTransactionDetail = d.Oid
                    LEFT OUTER JOIN mstitem i ON d.Item = i.Oid
                    LEFT OUTER JOIN mstitemcontent ic ON i.ItemContent = ic.Oid
                WHERE e.Oid = '{$Oid}'";
                $data = DB::select($query);
                
                foreach($data as $row) {
                    if($row->Item) {
                        if($row->TravelTransactionDetail) $apitype = $row->TransactionAPIType;
                        else $apitype = $row->ItemAPIType;

                        if($apitype == 'auto_stock') throw new \Exception('it could not be deleted, must use withdraw');
                        else {
                            $query = "DELETE FROM poseticketlog WHERE POSETicket = '{$row->Oid}'";
                            DB::delete($query);
                            $this->travelAPIService->setToken($token);
                            $this->travelAPIService->deleteapi("/core/api/eticket/delete/".$row->Oid."?system=".$company."&update=false");
                        }
                    }else{
                        $query = "DELETE FROM poseticketlog WHERE POSETicket = '{$row->Oid}'";
                        DB::delete($query);
                        $this->travelAPIService->setToken($token);
                        $this->travelAPIService->deleteapi("/core/api/eticket/delete/".$row->Oid."?system=".$company."&update=false");
                    }
                }
            });

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

    public function export(Request $request)
    {
        $company = Auth::user()->Company;
        $query = "SELECT  i.Oid AS Item, i.Code, i.Name, COUNT(*) AS Stock, et.CostPrice,DATE_FORMAT(et.DateExpiry, '%Y-%m-%d') AS DateExpiry
            FROM poseticket et 
            INNER JOIN mstitem i ON i.Oid = et.Item
            WHERE et.PointOfSale IS NULL AND et.Company ='{$company}'
            GROUP BY et.CostPrice,et.DateExpiry, i.Name, i.Code, i.Oid
            HAVING COUNT(*) > 0
            ORDER BY i.Name";
        $data = DB::select($query);
       
        return $this->excelExportService->export($data);
    }

}