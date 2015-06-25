<?php

namespace Egulias\EmailValidator\Exception;

use Egulias\EmailValidator\InvalidEmail;

class DomainHyphened extends InvalidEmail
{
    const CODE = 144;
    const REASON = "Hyphen found in domain";
}
