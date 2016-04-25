<?php

namespace Egulias\EmailValidator;

use Egulias\EmailValidator\Warning\NoDNSRecord;
use Egulias\EmailValidator\Warning\TLD;

/**
 * EmailValidator
 *
 * @author Eduardo Gulias Davis <me@egulias.com>
 */
class EmailValidator
{
    const ERR_DEPREC_REACHED     = 151;
    const ERR_UNOPENEDCOMMENT    = 152;
    const RFC5321_TLDNUMERIC      = 10;
    const RFC5321_QUOTEDSTRING    = 11;
    const RFC5321_ADDRESSLITERAL  = 12;
    const RFC5321_IPV6DEPRECATED  = 13;
    const CFWS_COMMENT            = 17;
    const CFWS_FWS                = 18;
    const DEPREC_LOCALPART        = 33;
    const DEPREC_FWS              = 34;
    const DEPREC_QTEXT            = 35;
    const DEPREC_QP               = 36;
    const DEPREC_COMMENT          = 37;
    const DEPREC_CTEXT            = 38;
    const DEPREC_CFWS_NEAR_AT     = 49;
    const RFC5322_LOCAL_TOOLONG   = 64;
    const RFC5322_LABEL_TOOLONG   = 63;
    const RFC5322_DOMAIN          = 65;
    const RFC5322_TOOLONG         = 66;
    const RFC5322_DOMAIN_TOOLONG  = 255;
    const RFC5322_DOMAINLITERAL   = 70;
    const RFC5322_DOMLIT_OBSDTEXT = 71;
    const RFC5322_IPV6_GRPCOUNT   = 72;
    const RFC5322_IPV6_COLONSTRT  = 76;
    const RFC5322_IPV6_COLONEND   = 77;
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
        if (!isset($this->warnings[self::DNSWARN_NO_RECORD]) &&
            !isset($this->warnings[self::DNSWARN_NO_MX_RECORD]) &&
            isset($this->warnings[self::RFC5322_DOMAINLITERAL])
        ) {
            $this->warnings[TLD::CODE] = new TLD();
        }
    }
}
