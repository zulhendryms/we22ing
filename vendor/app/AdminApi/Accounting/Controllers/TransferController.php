<?php

namespace App\AdminApi\Accounting\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

use App\Laravel\Http\Controllers\Controller; 

use App\Core\Accounting\Entities\CashBank;
use App\Core\Accounting\Entities\CashBankDetail;
use App\Core\Accounting\Entities\Account;
use App\Core\Internal\Entities\Status;
use Validator;

class TransferController extends Controller
{
    public function save(Request $request, $Oid = null)
    {
        $request = json_decode(json_encode(object_to_array(json_decode($request->getContent()))));
        $dataArray = object_to_array($request);
        
        $messsages = array(
            'Code.required'=>__('_.Code').__('error.required'),
            'Code.max'=>__('_.Code').__('error.max'),
            'Date.required'=>__('_.Date').__('error.required'),
            'Date.date'=>__('_.Date').__('error.date'),
            'Status.required'=>__('_.Status').__('error.required'),
            'Status.exists'=>__('_.Status').__('error.exists'),
        );
        $rules = array(
            'Code' => 'required|max:255',
            'Date' => 'required|date',
            'Status' => 'required|exists:sysstatus,Oid',
        );

        $validator = Validator::make($dataArray, $rules,$messsages);

        if ($validator->fails()) {
            return response()->json(
                $validator->messages(),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
        // return response()->json(
        //     $request, Response::HTTP_OK
        // );
        // if ($request->AdditionalAmount < 0 ) throw new \Exception("Additional amount cannot below 0");
        // if ($request->DiscountAmount < 0 ) throw new \Exception("Discount amount cannot below 0");
        // if ($request->PrepaidAmount < 0 ) throw new \Exception("Prepaid amount cannot below 0");
        // if ($request->RateAmount < 0 ) throw new \Exception("Rate amount cannot below 0");
        // if ($request->TransferAmount < 0 ) throw new \Exception("Transfer amount cannot below 0");        
        // checkperiod
        try {            
            DB::transaction(function () use ($request, $Oid, &$data) {
            if (!$Oid) $data = new CashBank();
            else $data = CashBank::findOrFail($Oid);

            $data->Type = 4; //transfer
            // $data->Company = Auth::user()->Company;
            $data->Code = $request->Code == '<<Auto>>' ? now()->format('ymdHis').'-'.str_random(3) : $request->Code;
            $data->Date = $request->Date;
            // $data->Description = $request->Description;
            $data->Note = $request->Note;
            $data->Status = $request->Status ?: Status::entry()->first()->Oid;

            // FROM //
            $account = Account::with('CurrencyObj')->findOrFail($request->Account);
            $cur = $account->CurrencyObj;
            $data->Account = $account->Oid;
            $data->Rate = $request->Rate ?: $account->CurrencyObj->getRate($data->Date)->MidRate;
            $data->AdditionalAmount = $request->AdditionalAmount;
            $data->Currency = $account->Currency;
            
            // TO //
            $accountTo = Account::with('CurrencyObj')->findOrFail($request->TransferAccount);
            $data->TransferAccount = $accountTo->Oid;
            $data->TransferCurrency = $accountTo->Currency;
            $data->TransferRateBase = $request->TransferRateBase ?: $accountTo->CurrencyObj->getRate($data->Date)->MidRate;
            $data->TransferAmount = $request->TransferAmount;
            $data->TransferRate = $request->TransferRate;
            
            $data->TotalAmount = $data->AdditionalAmount;
            $data->TotalBase = $cur->toBaseAmount($data->TotalAmount, $data->Rate);
                $data->save();
                $data->fresh();
            });

            return $data;
            return response()->json(
                $data, Response::HTTP_CREATED
            )->header('Location', route('AdminApi\CashBank::show', ['data' => $data->Oid]));
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_NOT_FOUND
            );
        }
    }
}
            