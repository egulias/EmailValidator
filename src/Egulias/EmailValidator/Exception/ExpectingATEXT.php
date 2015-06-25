<?php

namespace Egulias\EmailValidator\Exception;

use Egulias\EmailValidator\InvalidEmail;

class ExpectingATEXT extends InvalidEmail
{
    const CODE = 137;
    const REASON = "Expecting ATEXT";
}
