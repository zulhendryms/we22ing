<?php

namespace App\Core\Deal\Services;

use App\Core\POS\Services\POSService;
use Illuminate\Support\Facades\DB;
use App\Core\Master\Entities\Item;
use App\Core\POS\Entities\PointOfSale;
use App\Core\Internal\Entities\PointOfSaleType;

class DealTransactionService 
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
     * Create a deal transaction
     * 
     * @param array $params
     */
    public function create($params)
    {
        $dealTransaction = null;
        DB::transaction(function () use (&$dealTransaction, $params) {
            $pos = $this->posService->create($this->createPOSParams($params));
            $dealTransaction = $pos->DealTransactionObj()
            ->create($this->createDealTransactionParams($pos, $params));
        });
        return $dealTransaction;
    }

    protected function getParamDifferences()
    {
        return [
            'TransactionDate',
            'TransactionTime',
            'TransactionNote1',
            'TransactionNote2',
            'TransactionPassport',
            'POSItemService'
        ];
    }

    protected function createPOSParams($params)
    {
        $item = Item::findOrFail($params['POSItemService']);
        return array_merge(
            array_diff_key($params, array_flip($this->getParamDifferences())),
            [
                'ObjectType' => 94,
                'Supplier' => $item->PurchaseBusinessPartner,
                'APIType' => $item->APIType,
                'PointOfSaleType' => PointOfSaleType::where('Code', 'deal')->value('Oid')
            ]
        );
    }

    protected function createDealTransactionParams(PointOfSale $pos, $params)
    {
        $item = Item::findOrFail($params['POSItemService']);
        $details = $params['Details'] ?? [];
        return array_merge(
            array_intersect_key($params, array_flip($this->getParamDifferences())),
            [
                'City' => $item->City,
                'Amount' => $pos->TotalAmount,
                'AmountDisplay' => $pos->TotalAmountDisplay
            ]
        );
    }
}