<?php

namespace App\Core\POS\Exceptions;

use App\Core\Base\Exceptions\UserFriendlyException;

class NoETicketException extends UserFriendlyException
{
    public function __construct($detail, \Exception $previous = null)
    {
        $item = $detail->ItemObj;
        parent::__construct($item->Name." has no E-Ticket", null, $previous);
    }
}