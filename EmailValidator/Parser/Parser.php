<?php

namespace Egulias\EmailValidator\Parser;

use Egulias\EmailValidator\EmailLexer;
use Egulias\EmailValidator\Exception\CRLFAtTheEnd;
use Egulias\EmailValidator\Exception\CRLFX2;
use Egulias\EmailValidator\Exception\ExpectingQPair;
use Egulias\EmailValidator\Exception\ExpectingATEXT;
use Egulias\EmailValidator\Exception\UnclosedComment;
use Egulias\EmailValidator\Result\InvalidEmail;
use Egulias\EmailValidator\Result\Reason\ConsecutiveAt;
use Egulias\EmailValidator\Result\Reason\ConsecutiveDot;
use Egulias\EmailValidator\Result\Reason\ExpectingATEXT as ReasonExpectingATEXT;
use Egulias\EmailValidator\Result\Result;
use Egulias\EmailValidator\Result\ValidEmail;
use Egulias\EmailValidator\Warning\CFWSNearAt;
use Egulias\EmailValidator\Warning\Comment;
use Egulias\EmailValidator\Warning\QuotedPart;

abstract class Parser
{
    /**
     * @var \Egulias\EmailValidator\Warning\Warning[]
     */
    protected $warnings = [];

    /**
     * @var EmailLexer
     */
    protected $lexer;

    /**
     * @var int
     */
    protected $openedParenthesis = 0;

    public function __construct(EmailLexer $lexer)
    {
        $this->lexer = $lexer;
    }

    /**
     * @return \Egulias\EmailValidator\Warning\Warning[]
     */
    public function getWarnings()
    {
        return $this->warnings;
    }

    /**
     * @param string $str
     */
    abstract public function parse($str);

    /** @return int */
    public function getOpenedParenthesis()
    {
        return $this->openedParenthesis;
    }

    /**
     * validateQuotedPair
     */
    protected function validateQuotedPair()
    {
        if (!($this->lexer->token['type'] === EmailLexer::INVALID
            || $this->lexer->token['type'] === EmailLexer::C_DEL)) {
            throw new ExpectingQPair();
        }

        $this->warnings[QuotedPart::CODE] =
            new QuotedPart($this->lexer->getPrevious()['type'], $this->lexer->token['type']);
    }

    protected function parseComments()
    {
        $this->openedParenthesis = 1;
        $this->isUnclosedComment();
        $this->warnings[Comment::CODE] = new Comment();
        while (!$this->lexer->isNextToken(EmailLexer::S_CLOSEPARENTHESIS)) {
            if ($this->lexer->isNextToken(EmailLexer::S_OPENPARENTHESIS)) {
                $this->openedParenthesis++;
            }
            $this->warnEscaping();
            $this->lexer->moveNext();
        }

        $this->lexer->moveNext();
        if ($this->lexer->isNextTokenAny(array(EmailLexer::GENERIC, EmailLexer::S_EMPTY))) {
            throw new ExpectingATEXT();
        }

        if ($this->lexer->isNextToken(EmailLexer::S_AT)) {
            $this->warnings[CFWSNearAt::CODE] = new CFWSNearAt();
        }
    }

    /**
     * @return bool
     */
    protected function isUnclosedComment()
    {
        try {
            $this->lexer->find(EmailLexer::S_CLOSEPARENTHESIS);
            return true;
        } catch (\RuntimeException $e) {
            throw new UnclosedComment();
        }
    }

    protected function parseFWS()
    {
        $foldingWS = new FoldingWhiteSpace($this->lexer);
        $resultFWS = $foldingWS->parse('remove');
        //if ($resultFWS->isValid()) {
            $this->warnings = array_merge($this->warnings, $foldingWS->getWarnings());
        //}
        return $resultFWS;
    }

    protected function checkConsecutiveDots()
    {
        if ($this->lexer->token['type'] === EmailLexer::S_DOT && $this->lexer->isNextToken(EmailLexer::S_DOT)) {
            return new InvalidEmail(new ConsecutiveDot(), $this->lexer->token['value']);
        }
    }

    /**
     * @return bool
     */
    protected function isFWS()
    {
        if ($this->escaped()) {
            return false;
        }

        return $this->lexer->token['type'] === EmailLexer::S_SP ||
            $this->lexer->token['type'] === EmailLexer::S_HTAB ||
            $this->lexer->token['type'] === EmailLexer::S_CR ||
            $this->lexer->token['type'] === EmailLexer::S_LF ||
            $this->lexer->token['type'] === EmailLexer::CRLF;
    }

    /**
     * @return bool
     */
    protected function escaped()
    {
        $previous = $this->lexer->getPrevious();

        return $previous && $previous['type'] === EmailLexer::S_BACKSLASH
            &&
            $this->lexer->token['type'] !== EmailLexer::GENERIC;
    }

    /**
     * @return bool
     */
    protected function warnEscaping() : bool
    {
        //Backslash found
        if ($this->lexer->token['type'] !== EmailLexer::S_BACKSLASH) {
            return false;
        }

        if ($this->lexer->isNextToken(EmailLexer::GENERIC)) {
            throw new ExpectingATEXT();
        }

        if (!$this->lexer->isNextTokenAny(array(EmailLexer::S_SP, EmailLexer::S_HTAB, EmailLexer::C_DEL))) {
            return false;
        }

        $this->warnings[QuotedPart::CODE] =
            new QuotedPart($this->lexer->getPrevious()['type'], $this->lexer->token['type']);
        return true;

    }

    protected function validateEscaping() : Result
    {
        //Backslash found
        if ($this->lexer->token['type'] !== EmailLexer::S_BACKSLASH) {
            return new ValidEmail();
        }

        if ($this->lexer->isNextToken(EmailLexer::GENERIC)) {
            return new InvalidEmail(new ReasonExpectingATEXT('Found ATOM after escaping'), $this->lexer->token['value']);
        }

        if (!$this->lexer->isNextTokenAny(array(EmailLexer::S_SP, EmailLexer::S_HTAB, EmailLexer::C_DEL))) {
            return new ValidEmail();
        }

        $this->warnings[QuotedPart::CODE] =
            new QuotedPart($this->lexer->getPrevious()['type'], $this->lexer->token['type']);

        return new ValidEmail();

    }

    protected function checkCRLFInFWS()
    {
        if ($this->lexer->token['type'] !== EmailLexer::CRLF) {
            return;
        }

        if (!$this->lexer->isNextTokenAny(array(EmailLexer::S_SP, EmailLexer::S_HTAB))) {
            throw new CRLFX2();
        }

        if (!$this->lexer->isNextTokenAny(array(EmailLexer::S_SP, EmailLexer::S_HTAB))) {
            throw new CRLFAtTheEnd();
        }
    }
}
