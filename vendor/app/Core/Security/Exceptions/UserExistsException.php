<?php

namespace App\Core\Security\Exceptions;

class UserExistsException extends \App\Core\Base\Exceptions\UserFriendlyException
{
    protected $back = true;
}