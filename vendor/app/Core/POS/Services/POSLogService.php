<?php

namespace App\Core\POS\Services;

use App\Core\Internal\Services\AuditService;
use App\Core\POS\Entities\PointOfSale;


class POSLogService 
{
    /** @var AuditService $auditService */
    protected $auditService;

    /**
     * @param AuditService $auditService
     */
    public function __construct(AuditService $auditService)
    {
        $this->auditService = $auditService;
    }

    /**
     * Create created log
     * 
     * @param PointOfSale $pos
     * @return void
     */
    public function createCreatedLog(PointOfSale $pos)
    {
        $this->auditService->create($pos, [
            'Module' => 'ObjectCreated',
            'Description' => 'Newly Create, Entry',
            'User' => $pos->UserObj
        ]);
    }

    /**
     * Create status changed log
     * 
     * @param PointOfSale $pos
     * @return void
     */
    public function createStatusChangedLog(PointOfSale $pos)
    {
        $status = $pos->StatusObj;
        $this->auditService->create($pos, [
            'Module' => 'StatusChanged',
            'Description' => 'Change status to '.ucfirst($status->Code),
            'User' => $pos->UserObj,
            'Message' => ucfirst($status->Code)
        ]);
    }

    /**
     * Create status changed log
     * 
     * @param PointOfSale $pos
     * @param string $desc
     * @param string $data
     * @return void
     */
    public function createCallApiLog(PointOfSale $pos, $desc = null, $data = null)
    {
        $status = $pos->StatusObj;
        $this->auditService->create($pos, [
            'Module' => 'CallAPI',
            'Description' => $desc,
            'User' => $pos->UserObj,
            'Message' => $data
        ]);
    }

    /**
     * Create status changed log
     * 
     * @param PointOfSale $pos
     * @return void
     */
    public function createHideByUserLog(PointOfSale $pos)
    {
        $this->auditService->create($pos, [
            'Module' => 'Hide',
            'Description' => 'Hide by user',
            'User' => $pos->UserObj,
            'Message' => true
        ]);
    }

}