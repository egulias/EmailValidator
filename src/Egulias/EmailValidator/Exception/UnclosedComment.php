<?php

namespace Egulias\EmailValidator\Exception;

use Egulias\EmailValidator\InvalidEmail;

class UnclosedComment extends InvalidEmail
{
    const CODE = 146;
    const REASON = "No colosing comment token found";
}
