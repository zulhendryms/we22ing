<?php

namespace App\Core\Pub\Services;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Core\Pub\Entities\PublicApproval;
use App\Core\Internal\Events\EventSendNotificationSocketOneSignal;

class PublicApprovalService 
{
    public function actions($data, $module) {
        $user = Auth::user();
        
        $actionApprove = [
            'name' => 'Approve',
            'icon' => 'CheckCircleIcon',
            'type' => 'global_form',
            'form' => [
              [ 'fieldToSave' => 'Note',
                'type' => 'inputarea' ],
            ],
            'showModal' => false,
            'post' => 'approval/{Oid}/approve',
            'afterRequest' => 'apply'
        ];
        $actionReject = [
            'name' => 'Reject',
            'icon' => 'actionReject',
            'type' => 'global_form',
            'form' => [
              [ 'fieldToSave' => 'Note',
                'type' => 'inputarea' ],
            ],
            'showModal' => false,
            'post' => 'approval/{Oid}/reject',
            'afterRequest' => 'apply'
        ];
        $approval = PublicApproval::where($module, $data->Oid)->where('User',$user->Oid)->whereNull('ApprovalDate')->first();
        $approvalPrevious = PublicApproval::where($module, $data->Oid)->where('Sequence',$approval->Sequence - 1)->whereNotNull('ApprovalDate')->first();
        $return = [];
        if ($approval) {
            if ($approval->Sequence == 1 || $approvalPrevious) {
                $return[] = $actionApprove;
                $return[] = $actionReject;
                return $return;
            } 
        }
        return [];
    }
    
    public function approve($param) {
        $user = Auth::user();  
        $data = $param['data'];
        
        //VALIDATION
        $approval = PublicApproval::where($param['module'], $data->Oid)->where('User',$user->Oid)->whereNull('ApprovalDate')->first();
        $approvalPrevious = PublicApproval::where($param['module'], $data->Oid)->where('Sequence',$approval->Sequence - 1)->whereNotNull('ApprovalDate')->first();
        $approvalNext = PublicApproval::where($param['module'], $data->Oid)->where('Sequence',$approval->Sequence + 1)->first();
        if (!$approval) return 'ERROR';
        if ($approval->Sequence > 1 && !$approvalPrevious) return 'ERROR';
        
        //CREATING LOG
        // $detail = new PurchaseOrderLog();
        // $detail->Company = $data->Company;
        // $detail->PurchaseOrder = $data->Oid;
        // $detail->Date = now()->addHours(company_timezone())->toDateTimeString();
        // $detail->User = $user->Oid;
        // $detail->Type = 'Approval';
        // $detail->NextUser = $nextUser;
        // $detail->save();
        
        //NOTIFICATION NEXT USER
        if ($approvalNext) {
            $param = [
                'User' => $approvalNext->User,
                'Company' => $data->Company,
                'PurchaseOrder' => $data->Oid,
                'Code' => $data->Code,
                'Title' => 'Requires Approval',
                'Message' => $data->Code . ', approved: ' . $user->Name,
                'Action' => 'purchaseorder/form?item=' . $data->Oid,
                'Type' => 'Approval'
            ];
            event(new EventSendNotificationSocketOneSignal($param));
        }

        $approval->ApprovalDate = now()->addHours(company_timezone())->toDateTimeString();
        if (isset($param['note'])) $approval->Note = $param['note'];
        $approval->save();
        
        $message = $data->Code . ', approved: ' . $approvalPrevious->UserObj->Name.(isset($param['note']) ? '\r\n'.$param['note'] : null);
        if (!$approvalNext) {
            if ($data->Purchaser) {
                $param = [
                    'User' => $data->Purchaser,
                    'Company' => $data->Company,
                    'PurchaseOrder' => $data->Oid,
                    'Code' => $data->Code,
                    'Title' => $data->Code.', approved by ' . $approvalPrevious->UserObj->Name,
                    'Message' => $message,
                    'Action' => 'purchaseorder/form?item=' . $data->Oid,
                    'Type' => 'Approval'
                ];
                event(new EventSendNotificationSocketOneSignal($param));
            }
        }
        return $approvalNext;
    }

}