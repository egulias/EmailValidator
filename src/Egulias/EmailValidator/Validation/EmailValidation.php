<?php

namespace Egulias\EmailValidator\Validation;

use Egulias\EmailValidator\EmailLexer;

interface EmailValidation
{
    public function isValid($email, EmailLexer $emailLexer);

    /**
     * @return InvalidArgumentException or InvalidEmail
     */
    public function getError();

    /**
     * @return array of Warning
     */
    public function getWarnings();
}
