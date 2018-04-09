<?php

namespace Egulias\EmailValidator\Validation\Error;

use Egulias\EmailValidator\Exception\InvalidEmail;

class LocalEmail extends InvalidEmail
{
    const CODE = 996;
    const REASON = "The domain part is a local domain";
}
