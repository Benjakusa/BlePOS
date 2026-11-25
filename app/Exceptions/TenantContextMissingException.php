<?php

namespace App\Exceptions;

use RuntimeException;

class TenantContextMissingException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('A tenant context is required to query tenant-owned records.');
    }
}
