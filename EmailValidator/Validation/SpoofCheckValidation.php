<?php

namespace Egulias\EmailValidator\Validation;

use Egulias\EmailValidator\EmailLexer;
use Egulias\EmailValidator\Exception\InvalidEmail;
use Egulias\EmailValidator\Validation\Error\SpoofEmail;
use \Spoofchecker;

class SpoofCheckValidation implements EmailValidation
{
    /**
     * @var InvalidEmail
     */
    private $error;

    public function isValid($email, EmailLexer $emailLexer)
    {
        $checker = new Spoofchecker();
        $checker->setChecks(Spoofchecker::SINGLE_SCRIPT);

        if ($checker->isSuspicious($email)) {
            $this->error = new SpoofEmail();
        }

        return $this->error === null;
    }

    public function getError()
    {
        return $this->error;
    }

    public function getWarnings()
    {
        return [];
    }
}
