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
        $pattern = "/^[a-z'0-9]+([._-][a-z'0-9]+)*@([a-z0-9]+([._-][a-z0-9]+)+)+$/";
        if (preg_match($pattern, $email, $result)) {
            $host = $this->extractHost($result);
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

    private function extractHost(array $result)
    {
        foreach ($result as $match) {
            $onlyDomainPattern = "/^([a-z0-9]+([._-][a-z0-9]+))+$/";
            if (preg_match($onlyDomainPattern, $match, $domainResult)) {
                return $domainResult[0];
            }
        }
    }

    protected function checkDNS($host)
    {
        $Aresult = true;
        $MXresult = checkdnsrr($host, 'MX');
        
        if (!$MXresult) {
            $this->warnings[NoDNSMXRecord::CODE] = new NoDNSMXRecord();
            $Aresult = checkdnsrr($host, 'A');
            if (!$Aresult) {
                $this->error = new NoDNSRecord();
            }
        }
        return $MXresult || $Aresult;
    }
}
