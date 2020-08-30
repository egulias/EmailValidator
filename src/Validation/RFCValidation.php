<?php

namespace Egulias\EmailValidator\Validation;

use Egulias\EmailValidator\EmailLexer;
use Egulias\EmailValidator\EmailParser;
use Egulias\EmailValidator\Exception\InvalidEmail as ExceptionInvalidEmail;
use Egulias\EmailValidator\Result\InvalidEmail;

class RFCValidation implements EmailValidation
{
    /**
     * @var EmailParser|null
     */
    private $parser;

    /**
     * @var array
     */
    private $warnings = [];

    /**
     * @var InvalidEmail
     */
    private $error;

    public function isValid($email, EmailLexer $emailLexer)
    {
        $this->parser = new EmailParser($emailLexer);
        try {
            $result = $this->parser->parse((string)$email);
            if ($result->isInvalid()) {
                $this->error = $result;
                return false;
            }
        } catch (ExceptionInvalidEmail $invalid) {
            $this->error = $invalid;
            return false;
        }

        $this->warnings = $this->parser->getWarnings();
        return true;
    }

    public function getError() : ?InvalidEmail
    {
        return $this->error;
    }

    public function getWarnings()
    {
        return $this->warnings;
    }
}
