<?php

namespace Egulias\EmailValidator\Exception;

use Egulias\EmailValidator\InvalidEmail;

class ExpectingDTEXT extends InvalidEmail
{
    const CODE = 129;
    const REASON = "Expected DTEXT";
}
