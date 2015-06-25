<?php

namespace Egulias\EmailValidator\Exception;

use Egulias\EmailValidator\InvalidEmail;

class DotAtStart extends InvalidEmail
{
    const CODE = 141;

    public function __construct($part)
    {
        parent::__construct("Found DOT at start in " . $part, self::CODE);
    }
}
