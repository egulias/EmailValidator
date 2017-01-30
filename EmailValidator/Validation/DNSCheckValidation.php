<?php

namespace Egulias\EmailValidator\Validation;

use Egulias\EmailValidator\EmailLexer;
use Egulias\EmailValidator\Exception\InvalidEmail;
use Egulias\EmailValidator\Warning\NoDNSMXRecord;
use Egulias\EmailValidator\Exception\NoDNSRecord;

class DNSCheckValidation implements EmailValidation
{
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
        // use the input to check DNS if we cannot extract something similar to a domain
        $host = $email;

        // Arguable pattern to extract the domain. Not aiming to validate the domain nor the email
        if (false !== $lastAtPos = strrpos($email, '@')) {
            $host = substr($email, $lastAtPos + 1);
        }

        return $this->checkDNS($host);
    }

    public function getError()
    {
        return $this->error;
    }

    public function getWarnings()
    {
        return $this->warnings;
    }

    protected function checkDNS($host)
    {
        $host = rtrim($host, '.') . '.';

        $Aresult = true;
        $MXresult = checkdnsrr($host, 'MX');

        if (!$MXresult) {
            $this->warnings[NoDNSMXRecord::CODE] = new NoDNSMXRecord();
            $Aresult = checkdnsrr($host, 'A') || checkdnsrr($host, 'AAAA');
            if (!$Aresult) {
                $this->error = new NoDNSRecord();
            }
        }
        return $MXresult || $Aresult;
    }
}
