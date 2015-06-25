<?php

namespace Egulias\EmailValidator\Exception;

use Egulias\EmailValidator\InvalidEmail;

class ConsecutiveAt extends InvalidEmail
{
    const CODE = 128;
    const REASON = "Consecutive AT";
}
