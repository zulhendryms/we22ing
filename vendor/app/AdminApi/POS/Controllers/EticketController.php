<?php

namespace App\AdminApi\POS\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

use App\Laravel\Http\Controllers\Controller;
use App\Core\POS\Entities\ETicket; 
use App\Core\Master\Entities\BusinessPartner;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Validator;

class EticketController extends Controller
{

    public function index(Request $request)
    {
        try {
            $user = Auth::user();
            $businesspartner = BusinessPartner::with(['BusinessPartnerGroupObj'])->findOrFail($user->BusinessPartner);
            // login = 3
            // 1. company  -- Company->BusinessPartner
            // 2. customer / agent -> role
            // 3. supplier -> role

            $criteria = '';
            if ($request->input('status') == 1) {
                $redeem = 0;
                $criteria = $criteria." AND pe.DateRedeem IS NOT NULL";
            } else {
                $redeem = 1;
                $criteria = $criteria." AND pe.DateRedeem IS NULL";
            }

            if ($user->BusinessPartner == $user->CompanyObj->BusinessPartner) { // STAF
                $query ="SELECT pe.Oid,pe.Code,pe.DateRedeem,pe.IsInvoice,pos.Code AS PosCode, pos.Date, pos.ContactName,pos.Customer,t.Name AS SalesName,bp.Name AS BusinessPartnerName
                    FROM poseticket pe
                    LEFT OUTER JOIN pospointofsale pos ON pe.PointOfSale = pos.Oid
                    LEFT OUTER JOIN mstbusinesspartner bp ON pe.BusinessPartner = bp.Oid 
                    LEFT OUTER JOIN trvtransactiondetail t ON pos.Oid = t.TravelTransaction
                    WHERE pe.GCRecord IS NULL AND bp.Oid='{$request->query('businesspartner')}' AND (pe.IsInvoice IS FALSE OR pe.IsInvoice IS NULL) {$criteria}";
            } elseif ($businesspartner->BusinessPartnerGroupObj->Name == 'Customer' || $businesspartner->BusinessPartnerGroupObj->Name == 'Agent') {
                $query ="SELECT pe.Oid,pe.Code,pe.DateRedeem,pe.IsInvoice,pos.Code AS PosCode, pos.Date, pos.ContactName,pos.Customer,t.Name AS SalesName,bp.Name AS BusinessPartnerName
                    FROM poseticket pe
                    LEFT OUTER JOIN pospointofsale pos ON pe.PointOfSale = pos.Oid
                    LEFT OUTER JOIN mstbusinesspartner bp ON pe.BusinessPartner = bp.Oid 
                    LEFT OUTER JOIN trvtransactiondetail t ON pos.Oid = t.TravelTransaction
                    WHERE pe.GCRecord IS NULL AND bp.Oid='{$request->query('businesspartner')}' AND (pe.IsInvoice IS FALSE OR pe.IsInvoice IS NULL) AND pos.Customer='{$user->BusinessPartner}' {$criteria}";
            } else {
                $query ="SELECT pe.Oid,pe.Code,pe.DateRedeem,pe.IsInvoice,pos.Code AS PosCode, pos.Date, pos.ContactName,pos.Customer,t.Name AS SalesName,bp.Name AS BusinessPartnerName
                    FROM poseticket pe
                    LEFT OUTER JOIN pospointofsale pos ON pe.PointOfSale = pos.Oid
                    LEFT OUTER JOIN mstbusinesspartner bp ON pe.BusinessPartner = bp.Oid 
                    LEFT OUTER JOIN trvtransactiondetail t ON pos.Oid = t.TravelTransaction
                    WHERE pe.GCRecord IS NULL AND bp.Oid='{$request->query('businesspartner')}' AND (pe.IsInvoice IS FALSE OR pe.IsInvoice IS NULL) AND bp.Oid='{$user->BusinessPartner}'";
            }
            $data = DB::select($query);
            
            $result = [];
            foreach ($data as $row) {
                $result[] = [
                    'Oid' => $row->Oid,
                    'Code' => $row->Code,
                    'DateRedeem' => $row->DateRedeem,
                    'ETicketNumber' =>$row->Code,
                    'BookingCode' =>$row->PosCode,
                    'OrderDate' =>$row->Date,
                    'ContactName' =>$row->ContactName,
                    'SalesName' =>$row->SalesName,
                    'BusinessPartnerName' =>$row->BusinessPartnerName,
                    'Role' => [
                        'IsRedeem' => $redeem
                    ]
                ];
            }
            return $result;
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_NOT_FOUND
            );
        }
    }
    
    public function show(ETicket $data)
    {
        try {           
            $data = ETicket::with(['PointOfSalesObj'])->findOrFail($data->Oid);   
            return $data;
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_NOT_FOUND
            );
        }
    }

    public function redeem($Oid = null)
    {
        try {            
            DB::transaction(function () use ($Oid) {
                $query = "UPDATE poseticket pe
                SET pe.DateRedeem = NOW()
                WHERE pe.Oid = '{$Oid}'";
                DB::update($query); 
            });
            return null;
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
    }
}
