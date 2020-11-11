<?php

namespace App\Core\POS\Controllers\Api;

use App\Laravel\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Core\POS\Entities\PointOfSale;
use App\Core\POS\Services\POSService;
use App\Core\POS\Services\POSStatusService;

class POSController extends Controller 
{
    protected $posService;
    protected $posStatusService;

    public function __construct(
        POSService $posService,
        POSStatusService $posStatusService
        )
    {
        $this->posService = $posService;
        $this->posStatusService = $posStatusService;
    }

    public function calculate(Request $request, $id)
    {
        $pos = PointOfSale::findOrFail($id);
        $this->posService->calculateAmount($pos);
    }
}