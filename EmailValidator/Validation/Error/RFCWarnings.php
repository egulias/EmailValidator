<?php

namespace Egulias\EmailValidator\Validation\Error;

use Egulias\EmailValidator\Exception\InvalidEmail;

/**
 * @author Issei Murasawa <issei.m7@gmail.com>
 */
class RFCWarnings extends InvalidEmail
{
    const code = 997;
    const REASON = 'The email has no error with RFC, but some warnings.';
}
