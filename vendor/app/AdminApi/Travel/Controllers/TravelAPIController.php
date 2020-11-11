<?php

namespace App\AdminApi\Travel\Controllers;

use Validator;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Laravel\Http\Controllers\Controller;
use App\Core\Master\Entities\Item;
use App\Core\Security\Entities\User;
use App\Core\Master\Entities\Email;
use App\Core\Accounting\Entities\CashBank;
use App\Core\Internal\Entities\ItemType;
use App\Core\Base\Services\TravelAPIService;
use App\Core\POS\Entities\PointOfSale;

class TravelAPIController extends Controller
{
    private $travelAPIService;
    public function __construct(TravelAPIService $travelAPIService)
    {
        $this->travelAPIService = $travelAPIService;
    }

    public function autocomplete(Request $request)
    {
        $getparam = $this->getParam($request);  
        $param = $this->apiParam($getparam);
        return $this->travelAPIService->getapi("/api/travel/v1/adminapi/item/price?".$param);

    }

    public function getItemContentAutocomplete(Request $request)
    {
        $getparam = $this->getParam($request);  
        $param = $this->apiParam($getparam);
        return $this->travelAPIService->getapi("/api/travel/v1/adminapi/itemcontent?".$param);

    }

    public function getItem(Request $request)
    {
        $getparam = $this->getParam($request);  
        $param = $this->apiParam($getparam);
        return $this->travelAPIService->getapi("/api/travel/v1/adminapi/item?".$param);

    }

    public function uploadEticket(Request $request)
    {
        $getparam = $this->getParam($request);  
        $param = $this->apiParam($getparam);
        // $data = ['POSEticketFile' => $request->file('POSEticketFile')];
        return $this->travelAPIService->postapi("/core/api/upload/eticket?".$param, $request);
    }

    public function uploadEticketFTP(Request $request, $id)
    {
        $getparam = $this->getParam($request);  
        $param = $this->apiParam($getparam);
        return $this->travelAPIService->postapi("/core/api/uploadpurchaseticketftp/".$id."?".$param);
    }

    public function generateByQty(Request $request, $id)
    {
        $getparam = $this->getParam($request);  
        $param = $this->apiParam($getparam);
        return $this->travelAPIService->postapi("/core/api/traveltransactiondetail/".$id."/generateqty?".$param);
    }

    public function generateByMerchant(Request $request, $id)
    {
        $getparam = $this->getParam($request);  
        $param = $this->apiParam($getparam);
        return $this->travelAPIService->postapi("/core/api/traveltransactiondetail/".$id."/generatemerchant?".$param);
    }

    public function sendEticketToUser(Request $request, $id)
    {
        $getparam = $this->getParam($request);  
        $param = $this->apiParam($getparam);
        return $this->travelAPIService->postapi("/core/api/traveltransactiondetail/".$id."/emailuser?".$param);
    }

    public function sendEticketToVendor(Request $request, $id)
    {
        $getparam = $this->getParam($request);  
        $param = $this->apiParam($getparam);
        return $this->travelAPIService->postapi("/core/api/traveltransactiondetail/".$id."/emailvendor?".$param);
    }

    public function setToPaid(Request $request, $id)
    {
        $getparam = $this->getParam($request);  
        $param = $this->apiParam($getparam);
        $result[] =  $this->travelAPIService->postapi("/api/travel/v1/booking/".$id."/paid?".$param);

        return $result;
    }

    public function setToComplete(Request $request, $id)
    {
        $getparam = $this->getParam($request);  
        $param = $this->apiParam($getparam);
        $result[] = $this->travelAPIService->postapi("/api/travel/v1/booking/".$id."/complete?".$param);

        return $result;
    }

    public function sendEticket(Request $request, $id)
    {
        $getparam = $this->getParam($request);  
        $param = $this->apiParam($getparam);
        return $this->travelAPIService->postapi("/core/api/pos/".$id."/eticket/send?".$param);
    }

    public function resendEticket(Request $request, $id)
    {
        $getparam = $this->getParam($request);  
        $param = $this->apiParam($getparam);
        return $this->travelAPIService->postapi("/core/api/pos/".$id."/eticket/resend?".$param);
    }

    public function deleteEticket(Request $request, $id)
    {
        $getparam = $this->getParam($request);  
        $param = $this->apiParam($getparam);
        return $this->travelAPIService->deleteapi("/core/api/eticket/delete/".$id."?".$param);
    }

    public function paymentPreReport(Request $request, $id)
    {
        $getparam = $this->getParam($request);  
        $param = $this->apiParam($getparam);
        return $this->travelAPIService->postapi("/api/travel/v1/adminapi/cashbank/payment/prereport/".$id."?".$param);
    }

    public function sendToVendorPaymentPreReport(Request $request)
    {
        $cashbank = CashBank::findOrFail($request->input('cashbank'));
        $cashbankEmail = $cashbank->BusinessPartnerObj->Email;
        if(!$cashbankEmail) return 'Email Is Empty';
        $input = json_decode(json_encode(object_to_array(json_decode($request->getContent()))))   ; //WILLIAM ZEF
        $email = new Email;
        $email->EmailTo = $input->EmailTo;
        $email->EmailCc = $input->EmailCc;
        $email->EmailBcc = $input->EmailBcc;
        $email->Subject = $input->Subject;
        $email->Body = $input->Body;
        $email->CashBank = $cashbank->Oid;
        $email->save();

        $getparam = $this->getParam($request);  
        $param = $this->apiParam($getparam);
        $res = $this->travelAPIService->postapi("/api/travel/v1/adminapi/cashbank/email/vendor/".$email->Oid."?".$param);
        return $email;
    }

    public function linkFromStock(Request $request, $id)
    {
        $getparam = $this->getParam($request);  
        $param = $this->apiParam($getparam);
        return $this->travelAPIService->postapi("/api/travel/v1/adminapi/link/stock/".$id."?".$param);
    }

    private function getParam($param) {
        return [
            'token'=> $param->bearerToken(),
            'term'=> $param->input('term'),
            'type'=> $param->input('type'),
            'itemtype'=> $param->input('itemtype'),
            'itemtypecode'=> $param->input('itemtypecode'),
            'detail'=> $param->input('detail'),
            'user'=> $param->input('user'),
            'datefrom'=> $param->input('datefrom'),
            'dateuntil'=> $param->input('dateuntil'),
            'pos'=> $param->input('pos'),
            'purchaseinvoice'=> $param->input('purchaseinvoice'),
            'itemcontent'=> $param->input('itemcontent'),
            'display'=> $param->input('display'),
            'businesspartner'=> $param->input('businesspartner'),
            'item'=> $param->input('item'),
        ];   
    }

    private function apiParam($param = []) {
        $this->travelAPIService->setToken($param['token']);

        if(!empty($param['user'])){
            $user = User::findOrFail($param['user']);
        }else $user = Auth::user();

        if(!empty($param['pos'])) {
            $pos = PointOfSale::findOrFail($param['pos']);
            $company = $pos->Company;
        }else{
            $company = $user->Company;
        }


        $criteria = '';
        if (!empty($param['term'])) $criteria = $criteria."&term=".$param['term'];
        if (!empty($param['type'])) $criteria = $criteria."&type=".$param['type'];
        if (!empty($param['itemtype'])) $criteria = $criteria."&itemtype=".$param['itemtype'];
        if (!empty($param['itemtypecode'])) $criteria = $criteria."&itemtypecode=".$param['itemtypecode'];
        if (!empty($param['detail'])) $criteria = $criteria."&detail=".$param['detail'];
        if (!empty($param['datefrom'])) $criteria = $criteria."&datefrom=".$param['datefrom'];
        if (!empty($param['dateuntil'])) $criteria = $criteria."&dateuntil=".$param['dateuntil'];
        if (!empty($param['pos'])) $criteria = $criteria."&pos=".$param['pos'];
        if (!empty($param['purchaseinvoice'])) $criteria = $criteria."&purchaseinvoice=".$param['purchaseinvoice'];
        if (!empty($param['itemcontent'])) $criteria = $criteria."&itemcontent=".$param['itemcontent'];
        if (!empty($param['display'])) $criteria = $criteria."&display=".$param['display'];
        if (!empty($param['businesspartner'])) $criteria = $criteria."&businesspartner=".$param['businesspartner'];
        if (!empty($param['item'])) $criteria = $criteria."&item=".$param['item'];
        return "system=".$company."&user=".$user->Oid.$criteria;
    }
}
