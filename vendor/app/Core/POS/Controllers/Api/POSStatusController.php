<?php

namespace App\Core\POS\Controllers\Api;

use Illuminate\Http\Request;
use App\Laravel\Http\Controllers\Controller;
use App\Core\POS\Entities\PointOfSale;
use App\Core\POS\Services\POSStatusService;

class POSStatusController extends Controller 
{

    /** @var POSStatusService $statusService */
    protected $statusService;

    /**
     * @param POSStatusService $statusService
     * @return void
     */
    public function __construct(POSStatusService $statusService)
    {
        $this->statusService = $statusService;
    }

    /**
     * @param Request $request
     * @param string $id
     */
    public function paid(Request $request, $id)
    {
        $pos = PointOfSale::findOrFail($id);
        $this->statusService->setPaid($pos);
    }

    public function cancel(Request $request, $id)
    {
        $pos = PointOfSale::findOrFail($id);
        $this->statusService->setCancelled($pos);
    }

    public function complete(Request $request, $id)
    {
        $pos = PointOfSale::findOrFail($id);
        $this->statusService->setCompleted($pos);
    }
}