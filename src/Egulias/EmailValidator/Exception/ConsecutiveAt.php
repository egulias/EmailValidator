<?php

namespace Egulias\EmailValidator\Exception;

use Egulias\EmailValidator\InvalidEmail;

class ConsecutiveAt extends InvalidEmail
{
    const CODE = 128;

    public function __construct()
    {
        parent::__construct("Consecutive AT", self::CODE);
    }
}
