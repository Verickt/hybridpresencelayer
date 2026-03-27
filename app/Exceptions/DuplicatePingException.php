<?php

namespace App\Exceptions;

use RuntimeException;

class DuplicatePingException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('You have already pinged this person.');
    }
}
