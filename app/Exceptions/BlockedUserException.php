<?php

namespace App\Exceptions;

use RuntimeException;

class BlockedUserException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('This user has blocked you.');
    }
}
