<?php

namespace App\AdminApi\Development\Controllers;

use Validator;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Laravel\Http\Controllers\Controller;
use App\Core\Security\Services\RoleModuleService;
use App\Core\Base\Exceptions\UserFriendlyException;

use App\Core\Trading\Entities\PurchaseOrder;
use App\Core\Trading\Entities\PurchaseDelivery;
use App\Core\Internal\Entities\Status;
use App\AdminApi\Development\Controllers\CRUDDevelopmentController;

class CRUDLinkController extends Controller
{
    private $crudController;
    public function __construct() {
        $this->crudController = new CRUDDevelopmentController();
    }

    private function convertParamToString($param) {
        if (gettype($param) == 'array' || gettype($param) == 'object') {
            $string = null;
            foreach($param as $p) $string = ($string ? $string."," : "") . "'".$p."'";
            return $string;
        } elseif (gettype($param) == 'string') return "'".$param."'";
    }

    public function PurchaseDeliveryCalculateOutstanding($data) //PurchaseDeliveryDetail
    {
        $string = $this->convertParamToString($data);
        if (!$string) return null;
        $query = "UPDATE trdpurchasedeliverydetail data
            LEFT OUTER JOIN (
                SELECT d.PurchaseDeliveryDetail, SUM(IFNULL(d.Quantity,0)) AS Quantity 
                FROM trdpurchaseinvoicedetail d 
                LEFT OUTER JOIN trdpurchaseinvoice p ON p.Oid = d.PurchaseInvoice
                LEFT OUTER JOIN sysstatus s ON s.Oid = p.Status
                WHERE d.PurchaseDeliveryDetail IN (".$string.")
                AND s.Code NOT IN ('cancel')
                AND d.GCRecord IS NULL GROUP BY d.PurchaseDeliveryDetail
            ) datadtl ON datadtl.PurchaseDeliveryDetail = data.Oid
            SET data.QuantityInvoiced = IFNULL(datadtl.Quantity,0)
            WHERE data.Oid IN (".$string.")";
        DB::Update($query);
        $query = "UPDATE trdpurchaseorder data
            LEFT OUTER JOIN (
                SELECT p.Oid AS PurchaseOrder, SUM(IFNULL(d.Quantity,0)) = SUM(IFNULL(d.QuantityDelivered,0)) + SUM(IFNULL(d.QuantityInvoiced,0)) AS IsFull
                FROM trdpurchaseorder p LEFT OUTER JOIN trdpurchaseorderdetail d ON p.Oid = d.PurchaseOrder
                WHERE p.Oid IN (SELECT PurchaseOrder FROM trdpurchaseorderdetail WHERE Oid IN (".$string.") GROUP BY PurchaseOrder)
                GROUP BY p.Oid
            ) datadtl ON datadtl.PurchaseOrder = data.Oid  
            LEFT OUTER JOIN sysstatus sp ON sp.Code = 'posted'
            LEFT OUTER JOIN sysstatus sc ON sc.Code = 'completed'
            SET data.Status = CASE WHEN datadtl.IsFull THEN sc.Oid ELSE sp.Oid END
            WHERE data.Oid IN (SELECT PurchaseOrder FROM trdpurchaseorderdetail WHERE Oid IN (".$string.") GROUP BY PurchaseOrder)";
        DB::Update($query);
    }

    // $query = "UPDATE trdpurchaseorder data
    //     LEFT OUTER JOIN (
    //         SELECT p.Oid AS PurchaseOrder, SUM(IFNULL(d.Quantity,0)) = SUM(IFNULL(d.QuantityDelivered,0)) + SUM(IFNULL(d.QuantityInvoiced,0)) AS IsFull
    //         FROM trdpurchaseorder p LEFT OUTER JOIN trdpurchaseorderdetail d ON p.Oid = d.PurchaseOrder
    //         WHERE p.Oid IN (SELECT PurchaseOrder FROM trdpurchaseorderdetail WHERE Oid IN (".$string.") GROUP BY PurchaseOrder)
    //         GROUP BY p.Oid
    //     ) datadtl ON datadtl.PurchaseOrder = data.Oid  
    //     LEFT OUTER JOIN sysstatus sp ON sp.Code = 'posted'
    //     LEFT OUTER JOIN sysstatus sc ON sc.Code = 'completed'
    //     SET data.Status = CASE WHEN datadtl.IsFull THEN sc.Oid ELSE sp.Oid END
    //     WHERE data.Oid IN (SELECT PurchaseOrder FROM trdpurchaseorderdetail WHERE Oid IN (".$string.") GROUP BY PurchaseOrder)";
    public function PurchaseOrderCalculateOutstanding($data) //PurchaseOrderDetail
    {
        $string = $this->convertParamToString($data);
        if (!$string) return null;
        $query = "UPDATE trdpurchaseorderdetail data
            LEFT OUTER JOIN (
                SELECT d.PurchaseOrderDetail, SUM(IFNULL(d.Quantity,0)) AS Quantity 
                FROM trdpurchaseinvoicedetail d 
                LEFT OUTER JOIN trdpurchaseinvoice p ON p.Oid = d.PurchaseInvoice
                LEFT OUTER JOIN sysstatus s ON s.Oid = p.Status
                WHERE d.PurchaseOrderDetail IN (".$string.")
                AND s.Code NOT IN ('cancel')
                AND d.GCRecord IS NULL GROUP BY d.PurchaseOrderDetail
            ) datadtl ON datadtl.PurchaseOrderDetail = data.Oid
            SET data.QuantityInvoiced = IFNULL(datadtl.Quantity,0)
            WHERE data.Oid IN (".$string.")";
        DB::update($query);
        $query = "SELECT p.Oid, SUM(IFNULL(d.Quantity,0)) = SUM(IFNULL(d.QuantityDelivered,0)) + SUM(IFNULL(d.QuantityInvoiced,0)) AS IsFull
                FROM trdpurchaseorder p LEFT OUTER JOIN trdpurchaseorderdetail d ON p.Oid = d.PurchaseOrder
                WHERE p.Oid IN (SELECT PurchaseOrder FROM trdpurchaseorderdetail WHERE Oid IN (".$string.") GROUP BY PurchaseOrder)
                GROUP BY p.Oid";
        $data = DB::select($query);
        $statusPosted = Status::where('Code','posted')->first()->Oid;
        $statusComplete = Status::where('Code','complete')->first()->Oid;
        foreach($data as $row) {
            $tmp = PurchaseOrder::findOrFail($row->Oid);
            $tmp->Status = $row->IsFull == 1 ? $statusComplete : $statusPosted;
            $tmp->save();
        }
    }    

    public function PurchaseInvoiceCalculateOutstanding($data) //PurchaseInvoice
    {
        $string = $this->convertParamToString($data);
        if (!$string) return null;
        $query = "UPDATE trdpurchaseinvoice pinv
            LEFT OUTER JOIN (
                SELECT d.PurchaseInvoice, SUM(IFNULL(d.AmountInvoice,0)) AS PaidAmount 
                FROM acccashbankdetail d 
                LEFT OUTER JOIN acccashbank p ON p.Oid = d.CashBank
                LEFT OUTER JOIN sysstatus s ON s.Oid = p.Status
                WHERE d.PurchaseInvoice IN (".$string.") 
                AND s.Code NOT IN ('cancel')
                AND d.GCRecord IS NULL GROUP BY d.PurchaseInvoice
            ) cbd ON cbd.PurchaseInvoice = pinv.Oid
            LEFT OUTER JOIN sysstatus sp ON sp.Code = 'posted'
            LEFT OUTER JOIN sysstatus sc ON sc.Code = 'completed'
            SET pinv.PaidAmount = IFNULL(cbd.PaidAmount,0),
            pinv.Status = CASE WHEN pinv.TotalAmount = IFNULL(cbd.PaidAmount,0) 
            THEN sc.Oid ELSE sp.Oid END
            WHERE pinv.Oid IN (".$string.")";
        DB::Update($query);
    }

    public function SalesInvoiceCalculateOutstanding($data)
    {
        $string = $this->convertParamToString($data);
        if (!$string) return null;
        $query = "UPDATE trdsalesinvoice pinv
            LEFT OUTER JOIN (
                SELECT d.SalesInvoice, SUM(IFNULL(d.AmountInvoice,0)) AS PaidAmount 
                FROM acccashbankdetail d 
                LEFT OUTER JOIN acccashbank p ON p.Oid = d.CashBank
                LEFT OUTER JOIN sysstatus s ON s.Oid = p.Status
                WHERE d.SalesInvoice IN (".$string.") 
                AND s.Code NOT IN ('cancel')
                AND d.GCRecord IS NULL GROUP BY d.SalesInvoice
            ) cbd ON cbd.SalesInvoice = pinv.Oid
            LEFT OUTER JOIN sysstatus sp ON sp.Code = 'posted'
            LEFT OUTER JOIN sysstatus sc ON sc.Code = 'completed'
            SET pinv.PaidAmount = IFNULL(cbd.PaidAmount,0),
            pinv.Status = CASE WHEN pinv.TotalAmount = IFNULL(cbd.PaidAmount,0) 
            THEN sc.Oid ELSE sp.Oid END
            WHERE pinv.Oid IN (".$string.")";
        logger($query);
        DB::Update($query);
    }

    public function TravelTransactionCalculateOutstandingReceipt($data)
    {
        if (!$data) return null;
        $string = $this->convertParamToString($data);
        $query = "UPDATE trvtransactiondetail d
            LEFT OUTER JOIN (
                SELECT d.TravelTransactionDetail, SUM(IFNULL(d.AmountInvoice,0)) AS PaidAmount 
                FROM acccashbankdetail d 
                LEFT OUTER JOIN acccashbank p ON p.Oid = d.CashBank
                LEFT OUTER JOIN sysstatus s ON s.Oid = p.Status
                WHERE d.TravelTransactionDetail IN (".$string.") 
                AND s.Code NOT IN ('cancel')
                AND d.GCRecord IS NULL GROUP BY d.TravelTransactionDetail
            ) cbd ON cbd.TravelTransactionDetail = d.Oid
            SET d.PaidAmount = IFNULL(cbd.PaidAmount,0)
            WHERE d.Oid IN (".$string.")";
        DB::Update($query);
    }
    
}
