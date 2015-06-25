<?php

namespace Egulias\EmailValidator\Exception;

use Egulias\EmailValidator\InvalidEmail;

class ConsecutiveDot extends InvalidEmail
{
    const CODE = 132;
    const REASON = "Consecutive DOT";
}
