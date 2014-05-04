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
        $domain = '';
        $this->lexer->moveNext();
        if ($this->lexer->token['type'] === EmailLexer::S_AT) {
            throw new \InvalidArgumentException('ERR_CONSECUTIVEATS');
        }

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
            if ($this->lexer->token['type'] === EmailLexer::S_OPENQBRACKET) {
                try {
                    $this->lexer->find(EmailLexer::S_CLOSEQBRACKET);
                } catch (\RuntimeException $e) {
                    throw new \InvalidArgumentException('ERR_EXPECTING_DOMLIT_CLOSE');
                }
                $this->parseDomainLiteral();
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

            if ($this->lexer->token['type'] === EmailLexer::S_SP ||
                $this->lexer->token['type'] === EmailLexer::S_HTAB ||
                $this->lexer->token['type'] === EmailLexer::S_CR ||
                $this->lexer->token['type'] === EmailLexer::S_LF ||
                $this->lexer->token['type'] === EmailLexer::CRLF
            ) {
                $this->parseFWS();
            }
            $domain .= $this->lexer->token['value'];
            $this->lexer->moveNext();
        } while ($this->lexer->token);

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
        $IPv6TAG = false;
        $addressLiteral = '';
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
            if ($this->lexer->token['type'] === EmailLexer::S_CLOSEQBRACKET) {
                break;
            }
            if ($this->lexer->token['type'] === EmailLexer::S_SP ||
                $this->lexer->token['type'] === EmailLexer::S_HTAB ||
                $this->lexer->token['type'] === EmailLexer::CRLF
            ) {
                $this->parseFWS();
            }
            if ($this->lexer->token['type'] === EmailLexer::S_IPV6TAG) {
                $IPv6TAG = true;
            }
            $addressLiteral .= $this->lexer->token['value'];

        } while ($this->lexer->moveNext());
        // Revision 2.7: Daniel Marschall's new IPv6 testing strategy
        $prev = $this->lexer->getPrevious();
        if ($prev['type'] === EmailLexer::S_COLON) {
            // Address ends with a single colon
            $this->warnings[] = EmailValidator::RFC5322_IPV6_COLONEND;
        }

        $addressLiteral = str_replace('[', '', $addressLiteral);

        $maxGroups = 8;
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
                return;
            }
            // Convert IPv4 part to IPv6 format for further testing
            $addressLiteral = substr($addressLiteral, 0, $index) . '0:0';
        }

        if (!$IPv6TAG) {
            $this->warnings[] = EmailValidator::RFC5322_DOMAINLITERAL;
            return;
        }

        $this->warnings[] = EmailValidator::RFC5321_ADDRESSLITERAL;

        $IPv6       = substr($addressLiteral, 5);
        //Daniel Marschall's new IPv6 testing strategy
        $matchesIP  = explode(':', $IPv6);
        $groupCount = count($matchesIP);
        $colons     = strpos($IPv6, '::');

        if (count(preg_grep('/^[0-9A-Fa-f]{0,4}$/', $matchesIP, PREG_GREP_INVERT)) !== 0) {
            // Check for unmatched characters
            $this->warnings[] = EmailValidator::RFC5322_IPV6_BADCHAR;
        }

        if ($colons === false) {
            // We need exactly the right number of groups
            if ($groupCount !== $maxGroups) {
                $this->warnings[] = EmailValidator::RFC5322_IPV6_GRPCOUNT;
            }
        } else {
            if ($colons !== strrpos($IPv6, '::')) {
                $this->warnings[] = EmailValidator::RFC5322_IPV6_2X2XCOLON;
            } else {
                if ($colons === 0 || $colons === (strlen($IPv6) - 2)) {
                    // RFC 4291 allows :: at the start or end of an address
                    //with 7 other groups in addition
                    ++$maxGroups;
                }

                if ($groupCount > $maxGroups) {
                    $this->warnings[] = EmailValidator::RFC5322_IPV6_MAXGRPS;
                } elseif ($groupCount === $maxGroups) {
                    // Eliding a single "::"
                    $this->warnings[] = EmailValidator::RFC5321_IPV6DEPRECATED;
                }
            }
        }


        return $addressLiteral;
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
        // SP & HTAB are allowed
        $this->warnings[] = EmailValidator::DEPREC_QP;
    }

    private function parseLocalPart()
    {
        $closingQuote = false;
        while ($this->lexer->token['type'] !== EmailLexer::S_AT && $this->lexer->token) {

            $previous = $this->lexer->getPrevious();
            if ($this->lexer->token['type'] === EmailLexer::S_DOT && !$this->lexer->getPrevious()) {
                throw new \InvalidArgumentException('ERR_DOT_START');
            }
            if ($this->lexer->token['type'] === EmailLexer::S_DQUOTE) {
                if (!$closingQuote) {
                    if ($this->lexer->isNextToken(EmailLexer::GENERIC) && $previous['type'] === EmailLexer::GENERIC) {
                        throw new \InvalidArgumentException('ERR_EXPECTING_ATEXT');
                    }
                    $this->warnings[] = EmailValidator::RFC5321_QUOTEDSTRING;
                    try {
                        $this->lexer->find(EmailLexer::S_DQUOTE);
                        $closingQuote = true;
                    } catch (\Exception $e) {
                        throw new \InvalidArgumentException('ERR_UNCLOSEDQUOTEDSTR');
                    }
                }
            }
            if ($this->lexer->token['type'] === EmailLexer::S_OPENPARENTHESIS) {
                $this->parseComments();
            }

            $this->checkConsecutiveDots();

            if ($this->lexer->token['type'] === EmailLexer::S_DOT && $this->lexer->isNextToken(EmailLexer::S_AT)) {
                throw new \InvalidArgumentException('ERR_DOT_END');
            }

            if ($this->lexer->token['type'] === EmailLexer::S_BACKSLASH) {
                if ($this->lexer->isNextTokenAny(array(EmailLexer::S_SP, EmailLexer::S_HTAB, EmailLexer::C_DEL))) {
                    $this->warnings[] = EmailValidator::DEPREC_QP;
                }
                if ($this->lexer->isNextToken(EmailLexer::GENERIC)) {
                    throw new \InvalidArgumentException('ERR_EXPECTING_ATEXT');
                }
            }

            if ($this->lexer->isNextTokenAny(
                array(
                    EmailLexer::INVALID, EmailLexer::S_LOWERTHAN, EmailLexer::S_GREATERTHAN
                )
            )
            ) {
                throw new \InvalidArgumentException('ERR_EXPECTING_ATEXT');
            }

            if ($this->lexer->token['type'] === EmailLexer::S_SP ||
                $this->lexer->token['type'] === EmailLexer::S_HTAB ||
                $this->lexer->token['type'] === EmailLexer::S_CR ||
                $this->lexer->token['type'] === EmailLexer::S_LF ||
                $this->lexer->token['type'] === EmailLexer::CRLF
            ) {
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

            //scaping in a comment
            if ($this->lexer->token['type'] === EmailLexer::S_BACKSLASH) {
                if ($this->lexer->isNextTokenAny(array(EmailLexer::S_SP, EmailLexer::S_HTAB, EmailLexer::C_DEL))) {
                    $this->warnings[] = EmailValidator::DEPREC_QP;
                }
            }
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

    /**
     * parseFWS
     *
     * @throw InvalidArgumentException
     */
    private function parseFWS()
    {
        $previous = $this->lexer->getPrevious();

        if ($this->lexer->token['type'] === EmailLexer::CRLF && $this->lexer->isNextToken(EmailLexer::CRLF)) {
            throw new \InvalidArgumentException("ERR_FWS_CRLF_X2");
        }
        if ($this->lexer->token['type'] === EmailLexer::S_CR) {
            throw new \InvalidArgumentException("ERR_CR_NO_LF");
        }
        if (!$this->lexer->isNextTokenAny(array(EmailLexer::S_SP, EmailLexer::S_HTAB)) &&
            $this->lexer->token['type'] === EmailLexer::CRLF ) {
                throw new \InvalidArgumentException("ERR_FWS_CRLF_END");
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
}
