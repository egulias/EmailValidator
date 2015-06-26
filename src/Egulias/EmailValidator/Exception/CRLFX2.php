<?php

namespace Egulias\EmailValidator\Exception;

use Egulias\EmailValidator\InvalidEmail;

class CRLFX2 extends InvalidEmail
{
    const CODE = 148
    const REASON = "Folding whitespace CR LF found twice";
}
