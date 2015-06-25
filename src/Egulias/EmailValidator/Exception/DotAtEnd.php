<?php

namespace Egulias\EmailValidator\Exception;

use Egulias\EmailValidator\InvalidEmail;

class DotAtEnd extends InvalidEmail
{
    const CODE = 142;
    const REASON = "Dot at the end";
}
