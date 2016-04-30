<?php

namespace Egulias\EmailValidator\Validation;

use Egulias\EmailValidator\EmailLexer;

class NoRFCWarningsValidation extends RFCValidation
{
    public function isValid($email, EmailLexer $emailLexer)
    {
        return parent::isValid($email, $emailLexer) && empty($this->getWarnings());
    }
}
