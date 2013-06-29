<?php

namespace Egulias\EmailValidator;

/**
 * EmailValidator
 *
 * @author Eduardo Gulias Davis <me@egulias.com>
 */
class EmailValidator
{
    // Address is invalid for any purpose
    const ERR_CONSECUTIVEATS     = 128;
    const ERR_EXPECTING_DTEXT    = 129;
    const ERR_NOLOCALPART        = 130;
    const ERR_NODOMAIN           = 131;
    const ERR_CONSECUTIVEDOTS    = 132;
    const ERR_ATEXT_AFTER_CFWS   = 133;
    const ERR_ATEXT_AFTER_QS     = 134;
    const ERR_ATEXT_AFTER_DOMLIT = 135;
    const ERR_EXPECTING_QPAIR    = 136;
    const ERR_EXPECTING_ATEXT    = 137;
    const ERR_EXPECTING_QTEXT    = 138;
    const ERR_EXPECTING_CTEXT    = 139;
    const ERR_BACKSLASHEND       = 140;
    const ERR_DOT_START          = 141;
    const ERR_DOT_END            = 142;
    const ERR_DOMAINHYPHENSTART  = 143;
    const ERR_DOMAINHYPHENEND    = 144;
    const ERR_UNCLOSEDQUOTEDSTR  = 145;
    const ERR_UNCLOSEDCOMMENT    = 146;
    const ERR_UNCLOSEDDOMLIT     = 147;
    const ERR_FWS_CRLF_X2        = 148;
    const ERR_FWS_CRLF_END       = 149;
    const ERR_CR_NO_LF           = 150;
    const ERR_DEPREC_REACHED     = 151;

    const RFC5321_TLD             = 9;
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
    const RFC5322_IPV6_2X2XCOLON  = 73;
    const RFC5322_IPV6_BADCHAR    = 74;
    const RFC5322_IPV6_MAXGRPS    = 75;
    const RFC5322_IPV6_COLONSTRT  = 76;
    const RFC5322_IPV6_COLONEND   = 77;
    const DNSWARN_NO_MX_RECORD    = 5;
    const DNSWARN_NO_RECORD       = 6;

    protected $parser;
    protected $warnings = array();
    protected $error;
    protected $threshold = 255;
    protected $emailParts = array();

    public function __construct()
    {
        $this->parser = new EmailParser(new EmailLexer());
    }

    public function isValid($email, $checkDNS = false, $strict = false)
    {
        try {
            $this->parser->parse((string)$email);
            $this->emailParts = explode('@', $email);
            $this->warnings = $this->parser->getWarnings();
        } catch (\Exception $e) {
            $rClass = new \ReflectionClass($this);
            $this->error = $rClass->getConstant($e->getMessage());
            return false;
        }

        if ($checkDNS) {
            $dns = $this->checkDNS();
        }
        if ($this->hasWarnings() && ((int) max($this->warnings) > $this->threshold)) {
            $this->error = self::ERR_DEPREC_REACHED;

            return false;
        }

        return ($strict) ? (!$this->hasWarnings() && $dns) : true;
    }

    /**
     * hasWarnings
     *
     * @return boolean
     */
    public function hasWarnings()
    {
        return !empty($this->warnings);
    }

    /**
     * getWarnings
     *
     * @return array
     */
    public function getWarnings()
    {
        return $this->warnings;
    }

    /**
     * getError
     *
     * @return string
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * setThreshols
     *
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
     * getThreshold
     *
     * @return int
     */
    public function getThreshold()
    {
        return $this->threshold;
    }

    protected function checkDNS()
    {
        $checked = false;
        if (!function_exists('dns_get_record') && (
            in_array(self::DNSWARN_NO_RECORD, $this->warnings) &&
            in_array(self::DNSWARN_NO_MX_RECORD, $this->warnings)
        )) {
            return $checked;
        }

            // http://tools.ietf.org/html/rfc5321#section-2.3.5
            //   Names that can
            //   be resolved to MX RRs or address (i.e., A or AAAA) RRs (as discussed
            //   in Section 5) are permitted, as are CNAME RRs whose targets can be
            //   resolved, in turn, to MX or address RRs.
            //
            // http://tools.ietf.org/html/rfc5321#section-5.1
            //   The lookup first attempts to locate an MX record associated with the
            //   name.  If a CNAME record is found, the resulting name is processed as
            //   if it were the initial name. ... If an empty list of MXs is returned,
            //   the address is treated as if it was associated with an implicit MX
            //   RR, with a preference of 0, pointing to that host.
            //
            // is_email() author's note: We will regard the existence of a CNAME to be
            // sufficient evidence of the domain's existence. For performance reasons
            // we will not repeat the DNS lookup for the CNAME's target, but we will
            // raise a warning because we didn't immediately find an MX record.

        $result = checkdnsrr(trim($this->parser->getParsedDomainPart()), 'MX');
        $checked = true;
        if (!$result) {
            // Domain can't be found in DNS
            $this->warnings[] = self::DNSWARN_NO_RECORD;
            $checked = false;
        }

        // Check for TLD addresses
        // -----------------------
        // TLD addresses are specifically allowed in RFC 5321 but they are
        // unusual to say the least. We will allocate a separate
        // status to these addresses on the basis that they are more likely
        // to be typos than genuine addresses (unless we've already
        // established that the domain does have an MX record)
        //
        // http://tools.ietf.org/html/rfc5321#section-2.3.5
        //   In the case
        //   of a top-level domain used by itself in an email address, a single
        //   string is used without any dots.  This makes the requirement,
        //   described in more detail below, that only fully-qualified domain
        //   names appear in SMTP transactions on the public Internet,
        //   particularly important where top-level domains are involved.
        //
        // TLD format
        // ----------
        // The format of TLDs has changed a number of times. The standards
        // used by IANA have been largely ignored by ICANN, leading to
        // confusion over the standards being followed. These are not defined
        // anywhere, except as a general component of a DNS host name (a label).
        // However, this could potentially lead to 123.123.123.123 being a
        // valid DNS name (rather than an IP address) and thereby creating
        // an ambiguity. The most authoritative statement on TLD formats that
        // the author can find is in a (rejected!) erratum to RFC 1123
        // submitted by John Klensin, the author of RFC 5321:
        //
        // http://www.rfc-editor.org/errata_search.php?rfc=1123&eid=1353
        //   However, a valid host name can never have the dotted-decimal
        //   form #.#.#.#, since this change does not permit the highest-level
        //   component label to start with a digit even if it is not all-numeric.
        if (!$checked && (!in_array(self::DNSWARN_NO_RECORD, $this->warnings) &&
            !in_array(self::DNSWARN_NO_MX_RECORD, $this->warnings))
        ) {

            if (in_array($this->warnings, RFC5322_DOMAINLITERAL)) {
                $this->warnings[] = self::RFC5321_TLD;
            }

            //@TODO review how to test TLD
            //if (isset($this->atomList[self::COMPONENT_DOMAIN][$this->elementCount][0]) &&
            //    is_numeric($this->atomList[self::COMPONENT_DOMAIN][$this->elementCount][0])
            //) {
            //    $this->warnings[] = self::RFC5321_TLDNUMERIC;
            //}
        }

        return $checked;
    }
}
