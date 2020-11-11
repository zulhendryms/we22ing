<?php

namespace App\AdminApi\POS\Controllers;

use App\Laravel\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Validator;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Core\Master\Entities\PaymentMethod;
use App\Core\POS\Entities\PointOfSale;
use App\Core\Internal\Entities\Country;
use App\Core\POS\Services\POSService;
use App\Core\POS\Services\POSStatusService;
use App\Core\Travel\Services\TravelPassengerService;
use App\Core\Base\Exceptions\UserFriendlyException;

class PaymentController extends Controller 
{
    /** @var POSService $posService */
    protected $posService;
    /** @var POSStatusService $posStatusService */
    protected $posStatusService;
    /** @var TravelPassengerService $passengerService */
    protected $passengerService;

    public function __construct(
        POSService $posService,
        POSStatusService $posStatusService,
        TravelPassengerService $passengerService
    )
    {
        $this->posService = $posService;
        $this->posStatusService = $posStatusService;
        $this->passengerService = $passengerService;
    }

    public function show(Request $request, $id)
    {
        try {            
            $pos = PointOfSale::findOrFail($id);

            $this->posStatusService->checkExpiry($pos);
            if ($pos->StatusObj->IsExpired) return redirect()->route('Travel\User::histories');
            throw_if($pos->StatusObj->IsPaid, new UserFriendlyException("This transaction is already paid"));

            $paymentMethods = PaymentMethod::where('IsActive', true)->get();
            $countries = Country::whereNotNull('PhoneCode')->where('PhoneCode', '!=', '')->orderBy('Name')->get();

            // Passengers
            // $passengers = $request->user()->Passengers()->select('Name', 'Title', 'DateOfExpiry', 'DateOfBirth', 'PlaceOfBirth', 'PlaceOfIssue', 'PassportNumber', 'Nationality');
            // $adults = (clone $passengers)->adult()->get();
            // $childs = (clone $passengers)->child()->get();
            // $infants = (clone $passengers)->infant()->get();

            return view('Travel\Payment::index', compact('paymentMethods', 'pos', 'countries'
                // ,'adults', 'childs', 'infants'
                )
            );
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_NOT_FOUND
            );
        }
    }

    public function pay(Request $request, $id)
    {
        $req = json_decode(json_encode(object_to_array(json_decode($request->getContent()))))   ; //WILLIAM ZEF
        $dataArray = object_to_array($req);
        $messsages = array(
            'ContactName.required'=> 'ContactName.required',
            'ContactEmail.required'=> 'ContactEmail.required',
            'ContactPhoneCode.required'=> 'ContactPhoneCode.required',
            'ContactPhone.required'=> 'ContactPhone.required',
            'PaymentMethod.exists'=> 'PaymentMethod.exists',
            'PaymentMethod.required'=>'PaymentMethod.required',
        );
        $rules = array(
            'ContactName' => 'required',
            'ContactEmail' => 'required',
            'ContactPhoneCode' => 'required',
            'ContactPhone' => 'required',
            'PaymentMethod' => 'required|exists:mstpaymentmethod,Oid',
        );

        $validator = Validator::make($dataArray, $rules,$messsages);

        if ($validator->fails()) {
            return response()->json(
                $validator->messages(),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        try {    
            $pos = PointOfSale::findOrFail($id);
            $param = $request->all();

            $this->posStatusService->checkExpiry($pos);
            if ($pos->StatusObj->IsExpired) return response()->json('Transaction is already expired', Response::HTTP_NOT_FOUND);
            if ($pos->StatusObj->IsPaid) return response()->json('Transaction is already paid', Response::HTTP_NOT_FOUND);

            DB::transaction(function () use ($param, $pos) {
                $pos->Source = "Web-B2B";
                $pos->ContactName = $param['ContactName'];
                $pos->ContactEmail = $param['ContactEmail'];
                $pos->ContactPhone = $param['ContactPhoneCode'].$param['ContactPhone'];
                $pos->save();
                if (isset($param['TransactionDate'])) $pos->TravelTransactionObj->TransactionDate = $param['TransactionDate'];
                if (isset($param['TransactionNote1'])) $pos->TravelTransactionObj->TransactionNote1 = $param['TransactionNote1'];
                if (isset($param['TransactionNote2'])) $pos->TravelTransactionObj->TransactionNote2 = $param['TransactionNote2'];
                $pos->TravelTransactionObj->save();
                if (isset($param['Items'])) {
                    foreach ($param['Items'] as $k => $v) {
                        // $detail = $pos->TravelTransactionDetails()->findOrFail($k);
                        $detail = $pos->TravelTransactionDetails()->findOrFail($v['Oid']);
                        if (isset($v['Passengers'])) {
                            foreach ($v['Passengers'] as $p) {
                                $this->passengerService->create($detail, array_merge($p, [
                                    // 'DateOfBirth' => Carbon::createFromFormat('m/d/Y', trim($p['DateOfBirth']))->toDateString(),
                                    // 'DateOfExpiry' => Carbon::createFromFormat('m/d/Y', trim($p['DateOfExpiry']))->toDateString(),
                                ]));
                            }
                        }
                    }
                }
                $paymentMethod = PaymentMethod::where('Code','balance')->first()->Oid;
                $this->posService->setPaymentMethod($pos, $paymentMethod);
                // $this->posService->setPaymentMethod($pos, $param['PaymentMethod']);
            });
            logger('last');
            logger($pos);
            return response()->json(
                $pos,
                Response::HTTP_OK
            );
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_NOT_FOUND
            );
        }
    }
}