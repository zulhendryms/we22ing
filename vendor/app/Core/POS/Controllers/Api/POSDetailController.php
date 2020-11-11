<?php

namespace App\Core\POS\Controllers\Api;

use App\Laravel\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Core\POS\Entities\PointOfSale;
use App\Core\POS\Services\POSService;

class POSDetailController extends Controller 
{
    /** @var POSService $posService */
    protected $posService;

    /**
     * @param POSService $posService
     * @return void
     */
    public function __construct(POSService $posService)
    {
        $this->posService = $posService;
    }

    /**
     * @param Request $request
     * @param string $id
     */
    public function store(Request $request, $id)
    {
        $pos = PointOfSale::findOrFail($id);
        $this->posService->createDetail($pos, $request->all());
    }
}