<?php

namespace Egulias\EmailValidator\Exception;

use Egulias\EmailValidator\InvalidEmail;

class UnopenedComment extends InvalidEmail
{
    const CODE = 152;
    const REASON = "No opening comment token found";
}
