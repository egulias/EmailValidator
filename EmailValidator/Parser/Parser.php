<?php

namespace Egulias\EmailValidator\Parser;

use Egulias\EmailValidator\EmailLexer;
use Egulias\EmailValidator\Exception\CRLFAtTheEnd;
use Egulias\EmailValidator\Exception\CRLFX2;
use Egulias\EmailValidator\Exception\ExpectingATEXT;
use Egulias\EmailValidator\Result\InvalidEmail;
use Egulias\EmailValidator\Result\Reason\ConsecutiveDot;
use Egulias\EmailValidator\Result\Reason\ExpectingATEXT as ReasonExpectingATEXT;
use Egulias\EmailValidator\Result\Result;
use Egulias\EmailValidator\Result\ValidEmail;
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

    protected function parseFWS() : Result
    {
        $foldingWS = new FoldingWhiteSpace($this->lexer);
        $resultFWS = $foldingWS->parse('remove');
        $this->warnings = array_merge($this->warnings, $foldingWS->getWarnings());
        return $resultFWS;
    }

    protected function checkConsecutiveDots() : Result
    {
        if ($this->lexer->token['type'] === EmailLexer::S_DOT && $this->lexer->isNextToken(EmailLexer::S_DOT)) {
            return new InvalidEmail(new ConsecutiveDot(), $this->lexer->token['value']);
        }

        return new ValidEmail();
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
}