<?php

namespace Egulias\EmailValidator\Exception;

use Egulias\EmailValidator\InvalidEmail;

class CRNoLF extends InvalidEmail
{
    const CODE = 150;
    const REASON = "Missing LF after CR";
}
