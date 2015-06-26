<?php

namespace Egulias\EmailValidator\Exception;

use Egulias\EmailValidator\InvalidEmail;

class UnclosedQuotedString extends InvalidEmail
{
    const CODE = 145;
    const REASON = "Unclosed quoted string";

}