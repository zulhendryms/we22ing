<?php

namespace App\Core\Travel\Exceptions;

use App\Core\Base\Exceptions\UserFriendlyException;

class MaximumOrderException extends UserFriendlyException
{
    public function __construct($value, \Exception $previous = null)
    {
        parent::__construct("Maximum order is ".$value, null, $previous);
    }
}