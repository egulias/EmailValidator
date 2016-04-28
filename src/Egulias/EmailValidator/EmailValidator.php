<?php

namespace Egulias\EmailValidator;

use Egulias\EmailValidator\Warning\NoDNSRecord;
use Egulias\EmailValidator\Warning\DomainLiteral;
use Egulias\EmailValidator\Warning\TLD;

/**
 * EmailValidator
 *
 * @author Eduardo Gulias Davis <me@egulias.com>
 */
class EmailValidator
{
    const ERR_DEPREC_REACHED     = 151;
    const DNSWARN_NO_MX_RECORD    = 5;
    const DNSWARN_NO_RECORD       = 6;

    protected $parser;
    protected $warnings;
    protected $error;
    protected $threshold = 255;

    public function __construct()
    {
        $this->parser = new EmailParser(new EmailLexer());
    }

    public function isValid($email, $checkDNS = false, $strict = false)
    {
        try {
            $this->parser->parse((string)$email);
            $this->warnings = $this->parser->getWarnings();
        } catch (InvalidEmail $invalid) {
            $this->error = $invalid->getCode();
            return false;
        } catch (\Exception $e) {
            $rClass = new \ReflectionClass($this);
            $this->error = $rClass->getConstant($e->getMessage());
            return false;
        }

        $dns = true;
        if ($checkDNS) {
            $dns = $this->checkDNS();
        }

//        if ($this->hasWarnings() && ((int) max($this->warnings) > $this->threshold)) {
//            $this->error = self::ERR_DEPREC_REACHED;
//
//            return false;
//        }

        return !$strict || (!$this->hasWarnings() && $dns);
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

    /**
     * @param int $threshold
     *
     * @return EmailValidator
     */
    public function setThreshold($threshold)
    {
        $this->threshold = (int) $threshold;

        return $this;
    }

    /**
     * @return int
     */
    public function getThreshold()
    {
        return $this->threshold;
    }

    protected function checkDNS()
    {
        $checked = true;

        $result = checkdnsrr(trim($this->parser->getParsedDomainPart()), 'MX');

        if (!$result) {
            $this->warnings[NoDNSRecord::CODE] = new NoDNSRecord();
            $checked = false;
            $this->addTLDWarnings();
        }

        return $checked;
    }

    protected function addTLDWarnings()
    {
        if (!isset($this->warnings[NoDNSRecord::CODE]) &&
            !isset($this->warnings[self::DNSWARN_NO_MX_RECORD]) &&
            isset($this->warnings[DomainLiteral::CODE])
        ) {
            $this->warnings[TLD::CODE] = new TLD();
        }
    }
}
