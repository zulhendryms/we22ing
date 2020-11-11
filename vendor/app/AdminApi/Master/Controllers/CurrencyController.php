<?php

namespace App\AdminApi\Master\Controllers;

use Validator;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Laravel\Http\Controllers\Controller;
use App\Core\Master\Entities\Currency;
use App\Core\Master\Entities\CurrencyRateDate;

class CurrencyController extends Controller
{
    public function rate(Request $request)
    {
        try {       
            $company = Auth::user()->CompanyObj;
            $cur = $request->has('currency') ? $request->input('currency') : $company->Currency;
            $cur =  Currency::findOrFail($cur);        
            return response()->json(
                $cur->getRate($request->input('date'))->MidRate ?: 0,
                Response::HTTP_OK
            );
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_NOT_FOUND
            );
        }   
    }    
    
    public function convert(Request $request)
    {
        try {            
            $cur = Currency::findOrFail($request->input('currencyfrom'));
            $val =  $cur->convertRate($request->input('currencyto'),$request->input('amount'),$request->input('date')) ?: $request->input('amount');
            return response()->json(
                $val,
                Response::HTTP_OK
            );
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_NOT_FOUND
            );
        }   
    }

    
    public function rateInsert(Request $request)
    {
        try {
            $data;
            DB::transaction(function () use ($request, &$data) {
                $user = Auth::user();
                $request = json_decode(json_encode(object_to_array(json_decode($request->getContent()))));
                $data = CurrencyRateDate::where('Date', $request->Date)->first();
                if (!$data) {
                    $data = new CurrencyRateDate();
                    $data->Date = $request->Date;
                    $data->save();
                }
                $query = "INSERT INTO mstcurrencyrate (Oid, Company, CurrencyRateDate, Currency, Date, BuyRate, SellRate, MidRate)
                    SELECT UUID(), '{$user->Company}', '" . $data->Oid . "', cr.Currency, '" . $data->Date . "', cr.BuyRate, cr.SellRate, (cr.BuyRate+cr.SellRate) / 2
                    FROM mstcurrencyrate cr
                    LEFT OUTER JOIN mstcurrencyrate crd ON cr.Currency = crd.Currency AND crd.Date = '" . $data->Date . "'
                    WHERE cr.CurrencyRateDate = 
                    (SELECT CurrencyRateDate FROM mstcurrencyrate crd WHERE crd.Date <= '" . $data->Date . "' AND CurrencyRateDate IS NOT NULL ORDER BY crd.Date DESC LIMIT 1) AND
                    crd.Oid IS NULL AND cr.Company='{$user->Company}';";
                DB::insert($query);
                $query = "INSERT INTO mstcurrencyrate (Oid, Company, CurrencyRateDate, Currency, Date, BuyRate, SellRate, MidRate)
                    SELECT UUID(), '{$user->Company}', '" . $data->Oid . "', c.Oid, '" . $data->Date . "', 1,1,1
                    FROM mstcurrency c 
                    LEFT OUTER JOIN mstcurrencyrate rt ON c.Oid = rt.Currency AND rt.CurrencyRateDate='" . $data->Oid . "'
                    WHERE rt.Oid IS NULL AND c.IsActive = 1 AND c.Company='{$user->Company}'";
                DB::insert($query);
                $data = CurrencyRateDate::with('Details')->where('Oid', $data->Oid)->first();

                if ($data->Details()->count() == 0) {
                    DB::insert($query);
                }
            });

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
}
