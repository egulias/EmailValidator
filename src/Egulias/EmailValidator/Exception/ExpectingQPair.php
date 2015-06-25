<?php

namespace Egulias\EmailValidator\Exception;

use Egulias\EmailValidator\InvalidEmail;

class ExpectedQPair extends InvalidEmail
{
    const CODE = 136;
    const REASON = "Expecting QPAIR";
}