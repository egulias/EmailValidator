<?php

namespace Egulias\EmailValidator\Parser;

use Egulias\EmailValidator\EmailLexer;
use Egulias\EmailValidator\EmailValidator;

abstract class Parser
{
    protected $warnings = array();
    protected $lexer;

    public function __construct(EmailLexer $lexer)
    {
        $this->lexer = $lexer;
    }

    public function getWarnings()
    {
        return $this->warnings;
    }

    abstract function parse($str);

    /**
     * validateQuotedPair
     */
    protected function validateQuotedPair()
    {
        if (!($this->lexer->token['type'] === EmailLexer::INVALID
            || $this->lexer->token['type'] === EmailLexer::C_DEL)) {
            throw new \InvalidArgumentException('ERR_EXPECTING_QPAIR');
        }

        $this->warnings[] = EmailValidator::DEPREC_QP;
    }

    /**
     * @return string the the comment
     * @throws \InvalidArgumentException
     */
    protected function parseComments()
    {
        $this->isUnclosedComment();
        $this->warnings[] = EmailValidator::CFWS_COMMENT;
        while (!$this->lexer->isNextToken(EmailLexer::S_CLOSEPARENTHESIS)) {
            $this->warnEscaping();
            $this->lexer->moveNext();
        }

        $this->lexer->moveNext();
        if ($this->lexer->isNextTokenAny(array(EmailLexer::GENERIC, EmailLexer::S_EMPTY))) {
            throw new \InvalidArgumentException('ERR_EXPECTING_ATEXT');
        }

        if ($this->lexer->isNextToken(EmailLexer::S_AT)) {
            $this->warnings[] = EmailValidator::DEPREC_CFWS_NEAR_AT;
        }
    }

    protected function isUnclosedComment()
    {
        try {
            $this->lexer->find(EmailLexer::S_CLOSEPARENTHESIS);
            return true;
        } catch (\RuntimeException $e) {
            throw new \InvalidArgumentException('ERR_UNCLOSEDCOMMENT');
        }
    }

    protected function parseFWS()
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

    protected function checkConsecutiveDots()
    {
        if ($this->lexer->token['type'] === EmailLexer::S_DOT && $this->lexer->isNextToken(EmailLexer::S_DOT)) {
            throw new \InvalidArgumentException('ERR_CONSECUTIVEDOTS');
        }
    }

    protected function isFWS()
    {
        if ($this->escaped()) {
            return false;
        }

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

    protected function escaped()
    {
        $previous = $this->lexer->getPrevious();

        if ($previous['type'] === EmailLexer::S_BACKSLASH
            &&
            ($this->lexer->token['type'] === EmailLexer::S_SP ||
            $this->lexer->token['type'] === EmailLexer::S_HTAB)
        ) {
            return true;
        }

        return false;
    }

    protected function warnEscaping()
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

    protected function checkDQUOTE($hasClosingQuote)
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

    protected function checkCRLFInFWS()
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
}