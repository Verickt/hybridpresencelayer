<?php

namespace App\Exceptions;

use RuntimeException;

class CooldownException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('This person has not responded to your previous pings.');
    }
}
