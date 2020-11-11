<?php

namespace App\Core\Security\Exceptions;

class InvalidResetCodeException extends \App\Core\Base\Exceptions\UserFriendlyException
{
    protected $back = true;
}