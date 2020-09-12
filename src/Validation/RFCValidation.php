<?php

namespace Egulias\EmailValidator\Validation;

use Egulias\EmailValidator\EmailLexer;
use Egulias\EmailValidator\EmailParser;
use Egulias\EmailValidator\Exception\InvalidEmail as ExceptionInvalidEmail;
use Egulias\EmailValidator\Result\InvalidEmail;
use Egulias\EmailValidator\Result\Reason\ExceptionFound;

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

    public function isValid($email, EmailLexer $emailLexer) : bool
    {
        $this->parser = new EmailParser($emailLexer);
        try {
            $result = $this->parser->parse((string)$email);
            if ($result->isInvalid()) {
                $this->error = $result;
                return false;
            }
        } catch (\Exception $invalid) {
            $this->error = new InvalidEmail(new ExceptionFound(), '');
            return false;
        }

        $this->warnings = $this->parser->getWarnings();
        return true;
    }

    public function getError() : ?InvalidEmail
    {
        return $this->error;
    }

    public function getWarnings() : array
    {
        return $this->warnings;
    }
}
