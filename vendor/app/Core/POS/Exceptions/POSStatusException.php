<?php

namespace App\Core\POS\Exceptions;

use App\Core\Base\Exceptions\UserFriendlyException;
use App\Core\POS\Entities\PointOfSale;

class POSStatusException extends UserFriendlyException
{
    private $pos;
    private $status;
    
    public function __construct(PointOfSale $pos, $message = null, \Exception $previous = null)
    {
        $this->pos = $pos;
        $this->status = $pos->StatusObj;
        parent::__construct($message, $previous);
        
    }

    public function getPOS() 
    {
        return $this->pos;
    }

    public function getStatus() 
    {
        return $this->status;
    }
}