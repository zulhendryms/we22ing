<?php

namespace App\Core\Travel\Controllers\Web;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Laravel\Http\Controllers\Controller;
use Barryvdh\DomPDF\Facade as PDF;
use App\Core\POS\Entities\PointOfSale;

class TravelETicketController extends Controller
{
    public function show(Request $request, $id)
    {
        $pos = PointOfSale::findOrFail($id);
        $pdf = PDF::loadView(
            'Core\Travel::pdf.eticket_parent', compact('pos') 
        );
        return $pdf->stream();
    }
}