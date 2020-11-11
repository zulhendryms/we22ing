<?php

namespace App\Core\Travel\Exceptions;

use App\Core\Base\Exceptions\UserFriendlyException;

class MinimumOrderException extends UserFriendlyException
{
    public function __construct($value, \Exception $previous = null)
    {
        parent::__construct("Minimum order is ".$value, null, $previous);
    }
}