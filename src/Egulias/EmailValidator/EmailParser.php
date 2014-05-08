<?php

namespace Egulias\EmailValidator;

/**
 * EmailParser
 *
 * @author Eduardo Gulias Davis <me@egulias.com>
 */
class EmailParser
{

    protected $warnings = array();
    protected $domainPart = '';
    protected $lexer;

    public function __construct(EmailLexer $lexer)
    {
        $this->lexer = $lexer;
    }

    /**
     * @param $str
     * @return array
     * @throws \InvalidArgumentException
     */
    public function parse($str)
    {
        $this->lexer->setInput($str);
        $this->lexer->moveNext();
        $this->lexer->moveNext();
        if ($this->lexer->token['type'] === EmailLexer::S_AT) {
            throw new \InvalidArgumentException('ERR_NOLOCALPART');
        }
        $this->parseLocalPart();
        $this->parseDomainPart();
        $parts = explode('@', $str);

        if (strlen($parts[0] . '@' . $this->domainPart) > 254) {
            $this->warnings[] = EmailValidator::RFC5322_TOOLONG;
        }

        return array('local' => $parts[0], 'domain' => $this->domainPart);
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

    public function getParsedDomainPart()
    {
        return $this->domainPart;
    }

    /**
     * parseDomainPart
     *
     */
    private function parseDomainPart()
    {
        $this->lexer->moveNext();

        if ($this->lexer->token['type'] === EmailLexer::S_DOT) {
            throw new \InvalidArgumentException('ERR_DOT_START');
        }

        if ($this->lexer->token['type'] === EmailLexer::S_EMPTY) {
            throw new \InvalidArgumentException('ERR_NODOMAIN');
        }

        if ($this->lexer->token['type'] === EmailLexer::S_OPENPARENTHESIS) {
            $this->warnings[] = EmailValidator::DEPREC_COMMENT;
            $this->parseComments();
        }

        $domain = $this->doParseDomainPart();

        $prev = $this->lexer->getPrevious();
        $length = strlen($prev['value']);

        if ($prev['type'] === EmailLexer::S_DOT) {
            throw new \InvalidArgumentException('ERR_DOT_END');
        }
        if ($prev['type'] === EmailLexer::S_HYPHEN) {
            throw new \InvalidArgumentException('ERR_DOMAINHYPHENEND');
        }
        if ($length > 254) {
            $this->warnings[] = EmailValidator::RFC5322_DOMAIN_TOOLONG;
        }
        if ($prev['type'] === EmailLexer::S_CR) {
            throw new \InvalidArgumentException('ERR_FWS_CRLF_END');
        }
        $this->domainPart = $domain;
    }

    private function parseDomainLiteral()
    {
        if ($this->lexer->isNextToken(EmailLexer::S_COLON)) {
            $this->warnings[] = EmailValidator::RFC5322_IPV6_COLONSTRT;
        }
        if ($this->lexer->isNextToken(EmailLexer::S_IPV6TAG)) {
            $lexer = clone $this->lexer;
            $lexer->moveNext();
            if ($lexer->isNextToken(EmailLexer::S_DOUBLECOLON)) {
                $this->warnings[] = EmailValidator::RFC5322_IPV6_COLONSTRT;
            }
        }

        return $this->doParseDomainLiteral();
    }

    /**
     * validateQuotedPair
     */
    private function validateQuotedPair()
    {
        if (!($this->lexer->token['type'] === EmailLexer::INVALID
            || $this->lexer->token['type'] === EmailLexer::C_DEL)) {
            throw new \InvalidArgumentException('ERR_EXPECTING_QPAIR');
        }

        $this->warnings[] = EmailValidator::DEPREC_QP;
    }

    private function parseLocalPart()
    {
        $closingQuote = false;
        while ($this->lexer->token['type'] !== EmailLexer::S_AT && $this->lexer->token) {

            if ($this->lexer->token['type'] === EmailLexer::S_DOT && !$this->lexer->getPrevious()) {
                throw new \InvalidArgumentException('ERR_DOT_START');
            }

            $closingQuote = $this->checkDQUOTE($closingQuote);

            if ($this->lexer->token['type'] === EmailLexer::S_OPENPARENTHESIS) {
                $this->parseComments();
            }

            $this->checkConsecutiveDots();

            if ($this->lexer->token['type'] === EmailLexer::S_DOT && $this->lexer->isNextToken(EmailLexer::S_AT)) {
                throw new \InvalidArgumentException('ERR_DOT_END');
            }

            $this->warnEscaping();

            if ($this->lexer->isNextTokenAny(
                array(
                    EmailLexer::INVALID, EmailLexer::S_LOWERTHAN, EmailLexer::S_GREATERTHAN
                )
            )
            ) {
                throw new \InvalidArgumentException('ERR_EXPECTING_ATEXT');
            }

            if ($this->isFWS()) {
                $this->parseFWS();
            }

            $this->lexer->moveNext();
        }

        $prev = $this->lexer->getPrevious();
        if (strlen($prev['value']) > EmailValidator::RFC5322_LOCAL_TOOLONG) {
            $this->warnings[] = EmailValidator::RFC5322_LOCAL_TOOLONG;
        }
    }

    /**
     * @return string the the comment
     * @throws \InvalidArgumentException
     */
    private function parseComments()
    {
        $this->warnings[] = EmailValidator::CFWS_COMMENT;
        while (!$this->lexer->isNextToken(EmailLexer::S_CLOSEPARENTHESIS)) {
            try {
                $this->lexer->find(EmailLexer::S_CLOSEPARENTHESIS);
            } catch (\RuntimeException $e) {
                throw new \InvalidArgumentException('ERR_UNCLOSEDCOMMENT');
            }

            $this->warnEscaping();
            $this->lexer->moveNext();
        }

        $this->lexer->moveNext();
        if ($this->lexer->isNextToken(EmailLexer::GENERIC) || EmailLexer::S_EMPTY === $this->lexer->peek()) {
            throw new \InvalidArgumentException('ERR_EXPECTING_ATEXT');
        }

        if ($this->lexer->isNextToken(EmailLexer::S_AT)) {
            $this->warnings[] = EmailValidator::DEPREC_CFWS_NEAR_AT;
        }
    }

    private function parseFWS()
    {
        $previous = $this->lexer->getPrevious();

        $this->checkCRLFInFWS();

        if ($this->lexer->token['type'] === EmailLexer::S_CR) {
            throw new \InvalidArgumentException("ERR_CR_NO_LF");
        }

        if ($this->lexer->isNextToken(EmailLexer::GENERIC) && $previous['type']  !== EmailLexer::S_AT) {
            throw new \InvalidArgumentException("ERR_ATEXT_AFTER_CFWS");
        }

        if ($this->lexer->token['type'] === EmailLexer::S_LF || $this->lexer->token['type'] === EmailLexer::C_NUL) {
            throw new \InvalidArgumentException('ERR_EXPECTING_CTEXT');
        }

        if ($this->lexer->isNextToken(EmailLexer::S_AT) || $previous['type']  === EmailLexer::S_AT) {
            $this->warnings[] = EmailValidator::DEPREC_CFWS_NEAR_AT;
        } else {
            $this->warnings[] = EmailValidator::CFWS_FWS;
        }
    }

    private function checkConsecutiveDots()
    {
        if ($this->lexer->token['type'] === EmailLexer::S_DOT && $this->lexer->isNextToken(EmailLexer::S_DOT)) {
            throw new \InvalidArgumentException('ERR_CONSECUTIVEDOTS');
        }
    }

    private function isFWS()
    {
        if ($this->lexer->token['type'] === EmailLexer::S_SP ||
            $this->lexer->token['type'] === EmailLexer::S_HTAB ||
            $this->lexer->token['type'] === EmailLexer::S_CR ||
            $this->lexer->token['type'] === EmailLexer::S_LF ||
            $this->lexer->token['type'] === EmailLexer::CRLF
        ) {
            return true;
        }

        return false;
    }

    private function warnEscaping()
    {
        if ($this->lexer->token['type'] !== EmailLexer::S_BACKSLASH) {
            return false;
        }

        if ($this->lexer->isNextToken(EmailLexer::GENERIC)) {
            throw new \InvalidArgumentException('ERR_EXPECTING_ATEXT');
        }

        if (!$this->lexer->isNextTokenAny(array(EmailLexer::S_SP, EmailLexer::S_HTAB, EmailLexer::C_DEL))) {
            return false;
        }

        $this->warnings[] = EmailValidator::DEPREC_QP;
        return true;

    }

    private function checkDQUOTE($hasClosingQuote)
    {
        if ($this->lexer->token['type'] !== EmailLexer::S_DQUOTE) {
            return $hasClosingQuote;
        }
        if ($hasClosingQuote) {
            return $hasClosingQuote;
        }
        $previous = $this->lexer->getPrevious();
        if ($this->lexer->isNextToken(EmailLexer::GENERIC) && $previous['type'] === EmailLexer::GENERIC) {
            throw new \InvalidArgumentException('ERR_EXPECTING_ATEXT');
        }
        $this->warnings[] = EmailValidator::RFC5321_QUOTEDSTRING;
        try {
            $this->lexer->find(EmailLexer::S_DQUOTE);
            $hasClosingQuote = true;
        } catch (\Exception $e) {
            throw new \InvalidArgumentException('ERR_UNCLOSEDQUOTEDSTR');
        }

        return $hasClosingQuote;
    }

    private function checkCRLFInFWS()
    {
        if ($this->lexer->token['type'] !== EmailLexer::CRLF) {
            return;
        }
        if ($this->lexer->isNextToken(EmailLexer::CRLF)) {
            throw new \InvalidArgumentException("ERR_FWS_CRLF_X2");
        }
        if (!$this->lexer->isNextTokenAny(array(EmailLexer::S_SP, EmailLexer::S_HTAB))) {
            throw new \InvalidArgumentException("ERR_FWS_CRLF_END");
        }
    }

    private function doParseDomainPart()
    {
        $domain = '';
        do {
            $prev = $this->lexer->getPrevious();

            if ($this->lexer->token['type'] === EmailLexer::S_AT) {
                throw new \InvalidArgumentException('ERR_CONSECUTIVEATS');
            }
            if ($this->lexer->token['type'] === EmailLexer::S_OPENQBRACKET && $prev['type'] !== EmailLexer::S_AT) {
                throw new \InvalidArgumentException('ERR_EXPECTING_ATEXT');
            }
            if ($this->lexer->token['type'] === EmailLexer::S_OPENPARENTHESIS) {
                $this->parseComments();
                $this->lexer->moveNext();
            }

            $this->checkConsecutiveDots();

            if ($this->lexer->token['type'] === EmailLexer::S_HYPHEN && $this->lexer->isNextToken(EmailLexer::S_DOT)) {
                throw new \InvalidArgumentException('ERR_DOMAINHYPHENEND');
            }

            if ($this->lexer->token['type'] === EmailLexer::S_OPENBRACKET) {
                try {
                    $this->lexer->find(EmailLexer::S_CLOSEBRACKET);
                } catch (\RuntimeException $e) {
                    throw new \InvalidArgumentException('ERR_EXPECTING_DOMLIT_CLOSE');
                }
                $this->parseDomainLiteral();
            }

            if ($this->lexer->token['type'] === EmailLexer::S_BACKSLASH
                && $this->lexer->isNextToken(EmailLexer::GENERIC)) {
                throw new \InvalidArgumentException('ERR_EXPECTING_ATEXT');
            }

            if ($this->lexer->token['type'] === EmailLexer::S_DOT &&
                $prev['type'] === EmailLexer::GENERIC &&
                strlen($prev['value']) > 63
            ) {
                $this->warnings[] = EmailValidator::RFC5322_LABEL_TOOLONG;
            }

            if ($this->isFWS()) {
                $this->parseFWS();
            }
            $domain .= $this->lexer->token['value'];
            $this->lexer->moveNext();
        } while ($this->lexer->token);

        return $domain;
    }

    private function doParseDomainLiteral()
    {
        $IPv6TAG = false;
        $addressLiteral = '';
        do {
            if ($this->lexer->token['type'] === EmailLexer::C_NUL) {
                throw new \InvalidArgumentException('ERR_EXPECTING_DTEXT');
            } elseif ($this->lexer->token['type'] === EmailLexer::INVALID ||
                $this->lexer->token['type'] === EmailLexer::C_DEL   ||
                $this->lexer->token['type'] === EmailLexer::S_LF
            ) {
                $this->warnings[] = EmailValidator::RFC5322_DOMLIT_OBSDTEXT;
            }
            if ($this->lexer->isNextTokenAny(array(EmailLexer::S_OPENQBRACKET, EmailLexer::S_OPENBRACKET))) {
                throw new \InvalidArgumentException('ERR_EXPECTING_DTEXT');
            }
            if ($this->lexer->isNextTokenAny(array(EmailLexer::S_HTAB, EmailLexer::S_SP))) {
                $this->warnings[] = EmailValidator::CFWS_FWS;
                $this->parseFWS();
            }
            if ($this->lexer->isNextToken(EmailLexer::S_CR)) {
                throw new \InvalidArgumentException("ERR_CR_NO_LF");
            }
            if ($this->lexer->token['type'] === EmailLexer::S_BACKSLASH) {
                $this->warnings[] = EmailValidator::RFC5322_DOMLIT_OBSDTEXT;
                $addressLiteral .= $this->lexer->token['value'];
                $this->lexer->moveNext();
                $this->validateQuotedPair();
            }
            if ($this->lexer->token['type'] === EmailLexer::S_IPV6TAG) {
                $IPv6TAG = true;
            }
            if ($this->lexer->token['type'] === EmailLexer::S_CLOSEQBRACKET) {
                break;
            }
            if ($this->lexer->token['type'] === EmailLexer::S_SP ||
                $this->lexer->token['type'] === EmailLexer::S_HTAB ||
                $this->lexer->token['type'] === EmailLexer::CRLF
            ) {
                $this->parseFWS();
            }
            $addressLiteral .= $this->lexer->token['value'];

        } while ($this->lexer->moveNext());

        $addressLiteral = str_replace('[', '', $addressLiteral);
        $addressLiteral = $this->checkIPV4Tag($addressLiteral);

        if (false === $addressLiteral) {
            return $addressLiteral;
        }

        if (!$IPv6TAG) {
            $this->warnings[] = EmailValidator::RFC5322_DOMAINLITERAL;
            return $addressLiteral;
        }

        $this->warnings[] = EmailValidator::RFC5321_ADDRESSLITERAL;

        $this->checkIPV6Tag($addressLiteral);

        return $addressLiteral;
    }

    private function checkIPV4Tag($addressLiteral)
    {
        $matchesIP  = array();

        // Extract IPv4 part from the end of the address-literal (if there is one)
        if (preg_match(
                '/\\b(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)$/',
                $addressLiteral,
                $matchesIP
            ) > 0
        ) {
            $index = strrpos($addressLiteral, $matchesIP[0]);
            if ($index === 0) {
                $this->warnings[] = EmailValidator::RFC5321_ADDRESSLITERAL;
                return false;
            }
            // Convert IPv4 part to IPv6 format for further testing
            $addressLiteral = substr($addressLiteral, 0, $index) . '0:0';
        }

        return $addressLiteral;
    }

    public function checkIPV6Tag($addressLiteral, $maxGroups = 8)
    {
        $prev = $this->lexer->getPrevious();
        if ($prev['type'] === EmailLexer::S_COLON) {
            $this->warnings[] = EmailValidator::RFC5322_IPV6_COLONEND;
        }

        $IPv6       = substr($addressLiteral, 5);
        //Daniel Marschall's new IPv6 testing strategy
        $matchesIP  = explode(':', $IPv6);
        $groupCount = count($matchesIP);
        $colons     = strpos($IPv6, '::');

        if (count(preg_grep('/^[0-9A-Fa-f]{0,4}$/', $matchesIP, PREG_GREP_INVERT)) !== 0) {
            $this->warnings[] = EmailValidator::RFC5322_IPV6_BADCHAR;
        }

        if ($colons === false) {
            // We need exactly the right number of groups
            if ($groupCount !== $maxGroups) {
                $this->warnings[] = EmailValidator::RFC5322_IPV6_GRPCOUNT;
            }
            return;
        }

        if ($colons !== strrpos($IPv6, '::')) {
            $this->warnings[] = EmailValidator::RFC5322_IPV6_2X2XCOLON;
            return;
        }

        if ($colons === 0 || $colons === (strlen($IPv6) - 2)) {
            // RFC 4291 allows :: at the start or end of an address
            //with 7 other groups in addition
            ++$maxGroups;
        }

        if ($groupCount > $maxGroups) {
            $this->warnings[] = EmailValidator::RFC5322_IPV6_MAXGRPS;
        } elseif ($groupCount === $maxGroups) {
            $this->warnings[] = EmailValidator::RFC5321_IPV6DEPRECATED;
        }
    }
}
