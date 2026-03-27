<?php

namespace App\Exceptions;

use RuntimeException;

class RateLimitExceededException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('You have reached the maximum number of pings per hour.');
    }
}
