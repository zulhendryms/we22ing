<?php

namespace App\Core\Travel\Controllers\Web;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Laravel\Http\Controllers\Controller;
use Barryvdh\DomPDF\Facade as PDF;
use App\Core\POS\Entities\PointOfSale;

class TravelItineraryController extends Controller
{
    public function show(Request $request, $id)
    {
        $pos = PointOfSale::findOrFail($id);
        // $pdf = PDF::loadView(
        //     'Core\Travel::itinerary.index'
            // , compact('pos') 
        // );
        $travelTransaction = $pos->TravelTransactionObj;
        $details = $travelTransaction->Details()->orderByRaw('DateFrom ASC')->get();
        $hotels = $details->filter(function ($value) {
            return !is_null($value->Item) && $value->ItemObj->ItemGroupObj->ItemTypeObj->Code == 'Hotel';
        });
        $transports = $details->filter(function ($value) {
            return !is_null($value->Item) && $value->ItemObj->ItemGroupObj->ItemTypeObj->Code == 'Transport';
        });
        $transports = $details->filter(function ($value) {
            return !is_null($value->Item) && $value->ItemObj->ItemGroupObj->ItemTypeObj->Code == 'Transport';
        });
        $restaurants = $details->filter(function ($value) {
            return !is_null($value->Item) && $value->ItemObj->ItemGroupObj->ItemTypeObj->Code == 'Travel' && $value->ItemObj->ItemGroupObj->Code == 'Rest';
        });
        $activities = $details->filter(function ($value) {
            return !is_null($value->Item) && $value->ItemObj->ItemGroupObj->ItemTypeObj->Code == 'Travel' && $value->ItemObj->ItemGroupObj->Code != 'Rest' || $value->Type == 0;
        });
        $data = compact(
            'pos', 'travelTransaction', 'details', 'hotels', 'transports', 'restaurants', 'activities'
        );
        // return view('Core\Travel::itinerary.index', $data);
        $pdf = PDF::loadView(
            'Core\Travel::itinerary.index', $data
        );
        return $pdf->stream();
    }
}