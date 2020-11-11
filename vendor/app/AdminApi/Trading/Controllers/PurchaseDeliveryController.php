<?php

namespace App\AdminApi\Trading\Controllers;

use Validator;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Laravel\Http\Controllers\Controller;
use App\Core\Trading\Entities\PurchaseOrder;
use App\Core\Trading\Entities\PurchaseOrderDetail;
use App\Core\Trading\Entities\PurchaseDelivery;
use App\Core\Trading\Entities\PurchaseDeliveryDetail;
use App\Core\Trading\Entities\PurchaseInvoice;
use App\Core\Trading\Entities\PurchaseInvoiceDetail;
use App\Core\Internal\Services\AutoNumberService;
use App\Core\Security\Services\RoleModuleService;
use App\Core\Pub\Entities\PublicPost;
use App\Core\Pub\Entities\PublicComment;
use App\Core\Pub\Entities\PublicApproval;
use App\Core\Pub\Entities\PublicFile;
use App\Core\Master\Entities\Image;
use App\Core\Internal\Entities\Status;
use App\AdminApi\Development\Controllers\CRUDDevelopmentController;

class PurchaseDeliveryController extends Controller
{
    protected $roleService;
    private $crudController;
    public function __construct(
        RoleModuleService $roleService
    ) {
        $this->module = 'trdpurchasedelivery';
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
        return null;
    }

    public function list(Request $request)
    {
        try {
            $user = Auth::user();
            $data = DB::table($this->module.' as data');

            //SECURITY FILTER COMPANY
            if ($user->CompanyAccess) {
                $data = $data->leftJoin('company AS CompanySecurity', 'CompanySecurity.Oid', '=', 'data.Company');
                $tmp = json_decode($user->CompanyAccess);
                $data = $data->whereIn('CompanySecurity.Code', $tmp);
            }

            $data = $this->crudController->list($this->module, $data, $request);
            $role = $this->roleService->list('PurchaseDelivery'); //rolepermission
            foreach ($data->data as $row) {
                $tmp = PurchaseDelivery::findOrFail($row->Oid);
                $row->Action = $this->action($tmp);
                $row->Role = $this->generateRole($row, $role);
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
        $logger = false;
        $data = $this->crudController->detail($this->module, $Oid);
        $data->Action = $this->action($data);
        $group = null;
        $arrResult = [];
        $arrGroup = [];

        $query = "SELECT d.Oid, d.Quantity, d.Price, d.PurchaseOrderDetail PurchaseOrderDetailName, d.Note, 
            i.Name ItemName, c.Name CostCenterName, ig.Name ItemGroupName
            FROM trdpurchasedeliverydetail d 
            LEFT OUTER JOIN mstitem i ON i.Oid = d.Item
            LEFT OUTER JOIN mstitemgroup ig ON ig.Oid = i.ItemGroup
            LEFT OUTER JOIN mstcostcenter c ON c.Oid = d.CostCenter
            WHERE d.PurchaseDelivery = '{$data->Oid}'
            ORDER BY ig.Name";
        $details = DB::select($query);
        $i = 0;
        foreach ($details as $row) {
            if ($logger) logger($i." ".$row->ItemGroupName." ".$row->ItemName);
            if ($group == null) {
                if ($logger) logger('pertama');
                $group = $row->ItemGroupName;
                if ($logger) logger($group);
            }
            if ($logger) logger($row->ItemGroupName != $group." ".$row->ItemGroupName." ".$group);
            if ($row->ItemGroupName != $group) {
                if ($logger) logger("masuk");
                $arrResult[] = [
                    'Oid' => null,
                    'ItemName' => $group,
                    'Quantity' => 100,
                    'Group' => true,
                    'Details' => $arrGroup,
                ];
                $arrGroup = [];
                $group = $row->ItemGroupName;
            }
            $row->IsActive = true;
            $arrGroup[] = $row;
        }
        unset($data->Details);
        $data->Details = $arrResult;
        return $data;
    }

    public function show(PurchaseDelivery $data)
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

                $string = "";
                $totalAmount= 0;
                if (isset($data->Details)) {
                    foreach ($data->Details as $detail) {
                        if ($detail->PurchaseOrderDetail) {
                            $string = ($string ? $string . "," : null) . "'" . $detail->PurchaseOrderDetail . "'";
                        }
                        $totalAmount += ($detail->Quantity ?: 0) * ($detail->Price ?: 0);
                    }
                }
                $data->TotalAmount = $totalAmount + $data->AdditionalAmount - $data->DiscountAmount;
                $data->save();
                $this->updateQtyPurchaseOrder($string);
                if (!$data) {
                    throw new \Exception('Data is failed to be saved');
                }
            });

            $role = $this->roleService->list('PurchaseDelivery'); //rolepermission
            $data = $this->showSub($data->Oid);
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

    public function destroy(PurchaseDelivery $data)
    {
        try {
            //pengecekan
            $tmp = PurchaseDeliveryDetail::where('PurchaseDelivery', $data->Oid)->pluck('Oid');

            $check = PurchaseOrderDetail::whereIn('PurchaseDeliveryDetail', $tmp)->get();
            if ($check->count() > 0) {
                throw new \Exception("Purchase Delivery has already Purchase Order!");
            }
            $check = PurchaseInvoiceDetail::whereIn('PurchaseDeliveryDetail', $tmp)->get();
            if ($check->count() > 0) {
                throw new \Exception("Purchase Delivery has already Purchase Invoice!");
            }

            DB::transaction(function () use ($data) {
                //delete
                $delete = PublicApproval::where('PublicPost', $data->Oid)->get();
                foreach ($delete as $row) {
                    $row->delete();
                }
                
                $delete = Image::where('PublicPost', $data->Oid)->get();
                foreach ($delete as $row) {
                    $row->delete();
                }
                
                $delete = PublicComment::where('PublicPost', $data->Oid)->get();
                foreach ($delete as $row) {
                    $row->delete();
                }

                $delete = PublicFile::where('PublicPost', $data->Oid)->get();
                foreach ($delete as $row) {
                    $row->delete();
                }
                
                $delete = PublicPost::where('Oid', $data->Oid)->get();
                foreach ($delete as $row) {
                    $row->delete();
                }

                $delete = PurchaseDeliveryDetail::where('PurchaseDelivery', $data->Oid)->get();
                foreach ($delete as $row) {
                    $row->delete();
                }

                $data->delete();
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

    private function updateQtyPurchaseOrder($string)
    {
        // $strOid = '';
        // foreach ($data->Details as $rowdb) {
        //     $strOid = $strOid . ($strOid ? ", " : "") . "'" . $rowdb->PurchaseOrderDetail . "'";
        // }
        $query = "UPDATE trdpurchaseorderdetail pod
            LEFT OUTER JOIN (
                SELECT pdd.PurchaseOrderDetail, SUM(IFNULL(pdd.Quantity,0)) AS Quantity 
                FROM trdpurchasedeliverydetail pdd 
                WHERE pdd.PurchaseOrderDetail IN (" . $string . ") 
                AND pdd.GCRecord IS NULL GROUP BY pdd.PurchaseOrderDetail
            ) pdd ON pdd.PurchaseOrderDetail = pod.Oid
            SET pod.QuantityDelivered = IFNULL(pdd.Quantity,0)
            WHERE pod.Oid IN (" . $string . ")";
        if ($string != '') {
            DB::Update($query);
        }
    }

    public function action(PurchaseDelivery $data)
    {
        $url = 'purchasedelivery';
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
        $actionConvertToPurchaseInvoice = [
            'name' => 'Convert to PurchaseInvoice',
            'icon' => 'ZapIcon',
            'type' => 'confirm',
            'post' => $url.'/{Oid}/convert',
        ];
        $actionprintprereportpd = [
            'name' => 'Print PreReport',
            'icon' => 'PrinterIcon',
            'type' => 'open_report',
            'hide' => true,
            'get' => 'prereport/'.$url.'/{Oid}',
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
        $seperator = [
            'name' => 'Seperator',
            'type' => 'seperator',
        ];
        $return = [];
        switch ($data->Status ? $data->StatusObj->Code : "entry") {
            case "":
                $return[] = $actionPosted;
                // $return[] = $actionDelete;
                break;
            case "entry":
                $return[] = $actionPosted;
                $return[] = $actionCancelled;
                $return[] = $seperator;
                $return[] = $actionDelete;
                break;
            case "posted":
                $return[] = $actionEntry;
                $return[] = $actionConvertToPurchaseInvoice;
                $return[] = $actionprintprereportpd;
                $return[] = $actionViewJournal;
                $return[] = $actionViewStock;
                break;
        }
        $return = actionCheckCompany($this->module, $return);
        return $return;
    }

    public function statusEntry(PurchaseDelivery $data)
    {
        try {
            DB::transaction(function () use ($data) {
                $data->Status = Status::where('Code', 'Entry')->first()->Oid;
                $data->save();
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

    public function statusUnpost(PurchaseDelivery $data)
    {
        try {
            DB::transaction(function () use ($data) {
                $data->Status = Status::where('Code', 'Entry')->first()->Oid;
                $data->save();
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

    public function statusPost(PurchaseDelivery $data)
    {
        try {
            DB::transaction(function () use ($data) {
                $data->Status = Status::where('Code', 'Posted')->first()->Oid;
                $data->save();
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

    public function cancelled(PurchaseDelivery $data)
    {
        try {
            DB::transaction(function () use ($data) {
                $data->Status = Status::cancelled()->first()->Oid;
                $data->save();
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

    public function partialOrder(Request $request)
    {
        $query = "SELECT pod.Oid, po.Code Code, CONCAT(i.Name,' - ',i.Code) AS Name,
            c.Oid AS Currency, (IFNULL(pod.Quantity,0) - IFNULL(pod.QuantityDelivered,0)) AS Quantity, pod.Price
            FROM trdpurchaseorder po
            LEFT OUTER JOIN trdpurchaseorderdetail pod ON pod.PurchaseOrder = po.Oid
            LEFT OUTER JOIN mstitem i ON pod.Item = i.Oid
            LEFT OUTER JOIN mstbusinesspartner bp ON po.BusinessPartner = bp.Oid
            LEFT OUTER JOIN mstcurrency c ON po.Currency = c.Oid
            LEFT OUTER JOIN sysstatus s ON po.Status = s.Oid
            WHERE (IFNULL(pod.Quantity,0) - IFNULL(pod.QuantityDelivered,0)) > 0
            AND IFNULL(pod.QuantityInvoiced,0) < 1
            AND po.GCRecord IS NULL
            AND po.Oid NOT IN ({$request->input('exception')})
            AND po.Company = '{$request->input('company')}'
            AND po.BusinessPartner = '{$request->input('businesspartner')}'
            AND DATE_FORMAT(po.Date, '%Y-%m-%d') <= '{$request->input('date')}'
            AND s.Code = 'posted'";
        $data = DB::select($query);

        return response()->json(
            $data,
            Response::HTTP_OK
        );
    }

    public function partialOrderAdd(Request $request)
    {
        try {
            $result = [];
            DB::transaction(function () use ($request, &$result) {
                $purchaseDelivery = PurchaseDelivery::findOrFail($request->input('oid'));
                $request = json_decode(json_encode(object_to_array(json_decode($request->getContent()))));
                $string = "";
                foreach ($request as $row) {
                    $string = ($string ? $string . "," : null) . "'" . $row . "'";
                }
                // $param = collect($request)->pluck('Oid');
                $query = "SELECT pod.*, (IFNULL(pod.Quantity,0) - IFNULL(pod.QuantityDelivered,0)) AS OutstandingQuantity,
                    i.Name AS ItemName
                    FROM trdpurchaseorder po
                    LEFT OUTER JOIN trdpurchaseorderdetail pod ON pod.PurchaseOrder = po.Oid
                    LEFT OUTER JOIN sysstatus s ON po.Status = s.Oid
                    LEFT OUTER JOIN mstitem i ON i.Oid = pod.Item
                    WHERE (IFNULL(pod.Quantity,0) - IFNULL(pod.QuantityDelivered,0)) > 0
                    AND po.GCRecord IS NULL AND pod.Oid IN (" . $string . ")
                    ";
                $data  = DB::select($query);
                $sequence = (PurchaseDeliveryDetail::where('PurchaseDelivery', $data->Oid)->max('Sequence') ?: 0) + 1;
                foreach ($data as $row) {
                    $seq = PurchaseDeliveryDetail::where('PurchaseDelivery', $purchaseDelivery)->max('Oid');
                    $detail = new PurchaseDeliveryDetail();
                    $detail->PurchaseDelivery = $purchaseDelivery->Oid;
                    $detail->PurchaseOrderDetail = $row->Oid;
                    $detail->Company = $row->Company;
                    $detail->Sequence = $sequence;
                    $sequence = $sequence + 1;
                    $detail->Item = $row->Item;
                    $detail->Quantity = $row->OutstandingQuantity; //ini
                    $detail->QuantityBase = $row->OutstandingQuantity; //ini
                    $detail->ItemUnit = $row->ItemUnit;
                    $detail->Price = $row->Price;
                    $detail->DiscountAmount = $row->DiscountAmount;
                    $detail->DiscountPercentage = $row->DiscountPercentage;
                    $detail->SubtotalAmount = $row->OutstandingQuantity & $row->Price;
                    $detail->TotalBase = $detail->SubtotalAmount - $row->DiscountAmount;
                    $detail->Note = $row->Note;
                    $detail->CostCenter = $row->CostCenter;
                    $detail->save();
                    
                    $detail->ItemName = $row->ItemName;

                    $tmp = PurchaseOrderDetail::findOrFail($row->Oid);
                    $tmp->QuantityDelivered = $tmp->Quantity;
                    $tmp->save();

                    $result[] = $detail;
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



    public function convertToPurchaseInvoice(PurchaseDelivery $data)
    {
        $tmp = PurchaseDeliveryDetail::where('PurchaseDelivery', $data->Oid)->pluck('Oid');
        $check = PurchaseInvoiceDetail::whereIn('PurchaseDeliveryDetail', $tmp)->get();
        if ($check->count() > 0) {
            throw new \Exception("Purchase Delivery has already Purchase Invoice");
        }
        
        $purchaseInvoice = new PurchaseInvoice();
        try {
            DB::transaction(function () use ($purchaseInvoice, &$data) {
                $purchaseInvoice->PurchaseDelivery = $data->Oid;
                $purchaseInvoice->Company = $data->Company;
                $purchaseInvoice->Code = '<<Auto>>';
                $purchaseInvoice->Date = Carbon::now();
                $purchaseInvoice->Account = $data->Account;
                $purchaseInvoice->Currency = $data->Currency;
                $purchaseInvoice->BusinessPartner = $data->BusinessPartner;
                $purchaseInvoice->Quantity = $data->Quantity;
                $purchaseInvoice->Employee = $data->Employee;
                $purchaseInvoice->Warehouse = $data->Warehouse;
                $purchaseInvoice->Rate = $data->Rate;
                $purchaseInvoice->DiscountAmount = $data->DiscountAmount;
                $purchaseInvoice->SubtotalAmount = $data->SubtotalAmount;
                $purchaseInvoice->TotalAmount = $data->TotalAmount;
                $purchaseInvoice->Note = $data->Note;
                $purchaseInvoice->Status = Status::entry()->first()->Oid;
                $purchaseInvoice->save();
                $purchaseInvoice->Code = $this->autoNumberService->generate($purchaseInvoice, 'trdpurchaseinvoice');
                $purchaseInvoice->save();

                $data->IsConvert = true;
                $data->save();

                $details = [];
                foreach ($data->Details as $row) {
                    $totalAmount = $row->Quantity * $row->Price;
                    $detail = new PurchaseInvoiceDetail();
                    $detail->Company = $purchaseInvoice->Company;
                    $detail->PurchaseInvoice = $purchaseInvoice->Oid;
                    $detail->PurchaseDeliveryDetail = $row->Oid;
                    $detail->Sequence = $row->Sequence;
                    $detail->Item = $row->Item;
                    $detail->Quantity = $row->Quantity;
                    $detail->Price = $row->Price;
                    $detail->TotalAmount = $row->TotalAmount;
                    $detail->Note = $row->Note;
                    $detail->CostCenter = $row->CostCenter;
                    $detail->save();

                    $row->QuantityInvoiced = $row->Quantity;
                    $row->save();
                }

                $purchaseInvoice->SubtotalAmount = $totalAmount;
                $purchaseInvoice->TotalAmount = $purchaseInvoice->SubtotalAmount + $purchaseInvoice->AdditionalAmount - $purchaseInvoice->DiscountAmount;
                $purchaseInvoice->save();
                if (!$purchaseInvoice) {
                    throw new \Exception('Data is failed to be saved');
                }
            });
            return response()->json(
                $purchaseInvoice,
                Response::HTTP_CREATED
            );
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
    }

    private function generateRole($data, $role = null, $action = null)
    {
        if ($data instanceof PurchaseDelivery) {
            $status = $data->StatusObj->Code;
        } else {
            $status = Status::entry();
        }
        if (!$role) {
            $role = $this->roleService->list('PurchaseDelivery');
        }
        if (!$action) {
            $action = $this->roleService->action('PurchaseDelivery');
        }
        return [
            'IsRead' => $role->IsRead,
            'IsAdd' => $role->IsAdd,
            'IsEdit' => $this->roleService->isAllowDelete($status, $role->IsEdit),
            'IsDelete' => 0, //$this->roleService->isAllowDelete($row->StatusObj, $role->IsDelete),
            'Cancel' => $this->roleService->isAllowCancel($status, $action->Cancel),
            'Entry' => $this->roleService->isAllowEntry($status, $action->Entry),
            'Post' => $this->roleService->isAllowPost($status, $action->Posted),
            'ViewJournal' => $this->roleService->isPosted($status, 1),
            'ViewStock' => $this->roleService->isPosted($status, 1),
            'Print' => $this->roleService->isPosted($status, 1),
        ];
    }
}
