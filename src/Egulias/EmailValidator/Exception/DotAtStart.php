<?php

namespace Egulias\EmailValidator\Exception;

use Egulias\EmailValidator\InvalidEmail;

class DotAtStart extends InvalidEmail
{
    const CODE = 141;
    const REASON = "Found DOT at start";
}
