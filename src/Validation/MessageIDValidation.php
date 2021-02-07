<?php

namespace Egulias\EmailValidator\Validation;

use Egulias\EmailValidator\EmailLexer;
use Egulias\EmailValidator\Result\InvalidEmail;

class MessageIDValidation implements EmailValidation
{
    public function isValid(string $email, EmailLexer $emailLexer): bool
    {
        return true;
    }

    public function getWarnings(): array
    {
        return [];
    }

    public function getError(): ?InvalidEmail
    {
        return null;
    }
}
