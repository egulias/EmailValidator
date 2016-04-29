<?php

namespace Egulias\EmailValidator;

use Egulias\EmailValidator\Warning\NoDNSMXRecord;
use Egulias\EmailValidator\Warning\NoDNSRecord;
use Egulias\EmailValidator\Warning\DomainLiteral;
use Egulias\EmailValidator\Warning\TLD;
use Egulias\EmailValidator\Validation\EmailValidation;

class EmailValidator
{
    const ERR_DEPREC_REACHED     = 151;

    /**
     * @var EmailLexer
     */
    private $lexer;
    protected $warnings;
    protected $error;
    protected $threshold = 255;

    public function __construct()
    {
        $this->lexer = new EmailLexer();
    }

    /**
     * @param                 $email
     * @param EmailValidation $emailValidation
     * @return bool
     */
    public function isValid($email, EmailValidation $emailValidation)
    {
        $isValid = $emailValidation->isValid($email, $this->lexer);
        $this->warnings = $emailValidation->getWarnings();
        $this->error = $emailValidation->getError();
        return $isValid;

//        if ($this->hasWarnings() && ((int) max($this->warnings) > $this->threshold)) {
//            $this->error = self::ERR_DEPREC_REACHED;
//
//            return false;
//        }

    }

    /**
     * @return boolean
     */
    public function hasWarnings()
    {
        return !empty($this->warnings);
    }

    /**
     * @return array
     */
    public function getWarnings()
    {
        return $this->warnings;
    }

    /**
     * @return string
     */
    public function getError()
    {
        return $this->error;
    }

    protected function checkDNS()
    {
        $checked = true;
        $MXresult = checkdnsrr(trim($this->parser->getParsedDomainPart()), 'MX');

        if (!$MXresult) {
            $this->warnings[NoDNSMXRecord::CODE] = new NoDNSMXRecord();
            $Aresult = checkdnsrr(trim($this->parser->getParsedDomainPart()), 'A');
            if (!$Aresult) {
                $this->warnings[NoDNSRecord::CODE] = new NoDNSRecord();
                $checked = false;
                $this->addTLDWarnings();
            }
        }
        return $checked;
    }

    protected function addTLDWarnings()
    {
        if (!isset($this->warnings[NoDNSMXRecord::CODE]) &&
            !isset($this->warnings[NoDNSRecord::CODE]) &&
            isset($this->warnings[DomainLiteral::CODE])
        ) {
            $this->warnings[TLD::CODE] = new TLD();
        }
    }
}
