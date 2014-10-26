<?php


namespace Egulias\EmailValidator\Parser;

use Egulias\EmailValidator\EmailLexer;
use Egulias\EmailValidator\Parser\Parser;
use Egulias\EmailValidator\EmailValidator;

class DomainPart extends Parser
{
    const DOMAIN_MAX_LENGTH = 254;
    protected $domainPart = '';

    public function parse($domainPart)
    {
        $this->lexer->moveNext();

        if ($this->lexer->token['type'] === EmailLexer::S_DOT) {
            throw new \InvalidArgumentException('ERR_DOT_START');
        }

        if ($this->lexer->token['type'] === EmailLexer::S_EMPTY) {
            throw new \InvalidArgumentException('ERR_NODOMAIN');
        }
        if ($this->lexer->token['type'] === EmailLexer::S_HYPHEN) {
            throw new \InvalidArgumentException('ERR_DOMAINHYPHENEND');
        }

        if ($this->lexer->token['type'] === EmailLexer::S_OPENPARENTHESIS) {
            $this->warnings[] = EmailValidator::DEPREC_COMMENT;
            $this->parseDomainComments();
        }

        $domain = $this->doParseDomainPart();

        $prev = $this->lexer->getPrevious();
        $length = strlen($domain);

        if ($prev['type'] === EmailLexer::S_DOT) {
            throw new \InvalidArgumentException('ERR_DOT_END');
        }
        if ($prev['type'] === EmailLexer::S_HYPHEN) {
            throw new \InvalidArgumentException('ERR_DOMAINHYPHENEND');
        }
        if ($length > self::DOMAIN_MAX_LENGTH) {
            $this->warnings[] = EmailValidator::RFC5322_DOMAIN_TOOLONG;
        }
        if ($prev['type'] === EmailLexer::S_CR) {
            throw new \InvalidArgumentException('ERR_FWS_CRLF_END');
        }
        $this->domainPart = $domain;
    }

    public function getDomainPart()
    {
        return $this->domainPart;
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

    protected function doParseDomainPart()
    {
        $domain = '';
        do {
            $prev = $this->lexer->getPrevious();

            if ($this->lexer->token['type'] === EmailLexer::S_SLASH) {
                throw new \InvalidArgumentException('ERR_DOMAIN_CHAR_NOT_ALLOWED');
            }

            if ($this->lexer->token['type'] === EmailLexer::S_OPENPARENTHESIS) {
                $this->parseComments();
                $this->lexer->moveNext();
            }

            $this->checkConsecutiveDots();
            $this->checkDomainPartExceptions($prev);

            if ($this->hasBrackets()) {
                $this->parseDomainLiteral();
            }

            $this->checkLabelLength($prev);

            if ($this->isFWS()) {
                $this->parseFWS();
            }

            $domain .= $this->lexer->token['value'];
            $this->lexer->moveNext();
        } while ($this->lexer->token);

        return $domain;
    }

    protected function parseDomainLiteral()
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

    protected function doParseDomainLiteral()
    {
        $IPv6TAG = false;
        $addressLiteral = '';
        do {
            if ($this->lexer->token['type'] === EmailLexer::C_NUL) {
                throw new \InvalidArgumentException('ERR_EXPECTING_DTEXT');
            }

            if ($this->lexer->token['type'] === EmailLexer::INVALID ||
                $this->lexer->token['type'] === EmailLexer::C_DEL   ||
                $this->lexer->token['type'] === EmailLexer::S_LF
            ) {
                $this->warnings[] = EmailValidator::RFC5322_DOMLIT_OBSDTEXT;
            }

            if ($this->lexer->isNextTokenAny(array(EmailLexer::S_OPENQBRACKET, EmailLexer::S_OPENBRACKET))) {
                throw new \InvalidArgumentException('ERR_EXPECTING_DTEXT');
            }

            if ($this->lexer->isNextTokenAny(
                array(EmailLexer::S_HTAB, EmailLexer::S_SP, $this->lexer->token['type'] === EmailLexer::CRLF)
            )) {
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

    /**
     * @param string $addressLiteral
     */
    protected function checkIPV4Tag($addressLiteral)
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

    protected function checkDomainPartExceptions($prev)
    {
        if ($this->lexer->token['type'] === EmailLexer::S_COMMA) {
            throw new \InvalidArgumentException('ERR_COMMA_IN_DOMAIN');
        }

        if ($this->lexer->token['type'] === EmailLexer::S_AT) {
            throw new \InvalidArgumentException('ERR_CONSECUTIVEATS');
        }

        if ($this->lexer->token['type'] === EmailLexer::S_OPENQBRACKET && $prev['type'] !== EmailLexer::S_AT) {
            throw new \InvalidArgumentException('ERR_EXPECTING_ATEXT');
        }

        if ($this->lexer->token['type'] === EmailLexer::S_HYPHEN && $this->lexer->isNextToken(EmailLexer::S_DOT)) {
            throw new \InvalidArgumentException('ERR_DOMAINHYPHENEND');
        }

        if ($this->lexer->token['type'] === EmailLexer::S_BACKSLASH
            && $this->lexer->isNextToken(EmailLexer::GENERIC)) {
            throw new \InvalidArgumentException('ERR_EXPECTING_ATEXT');
        }
    }

    protected function hasBrackets()
    {
        if ($this->lexer->token['type'] !== EmailLexer::S_OPENBRACKET) {
            return false;
        }

        try {
            $this->lexer->find(EmailLexer::S_CLOSEBRACKET);
        } catch (\RuntimeException $e) {
            throw new \InvalidArgumentException('ERR_EXPECTING_DOMLIT_CLOSE');
        }

        return true;
    }

    protected function checkLabelLength($prev)
    {
        if ($this->lexer->token['type'] === EmailLexer::S_DOT &&
            $prev['type'] === EmailLexer::GENERIC &&
            strlen($prev['value']) > 63
        ) {
            $this->warnings[] = EmailValidator::RFC5322_LABEL_TOOLONG;
        }
    }

    protected function parseDomainComments()
    {
        $this->isUnclosedComment();
        while (!$this->lexer->isNextToken(EmailLexer::S_CLOSEPARENTHESIS)) {
            $this->warnEscaping();
            $this->lexer->moveNext();
        }

        $this->lexer->moveNext();
        if ($this->lexer->isNextToken(EmailLexer::S_DOT)) {
            throw new \InvalidArgumentException('ERR_EXPECTING_ATEXT');
        }
    }
}
