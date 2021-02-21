<?php

namespace Egulias\EmailValidator;

use Egulias\EmailValidator\Result\Result;
use Egulias\EmailValidator\Result\ValidEmail;
use Egulias\EmailValidator\Result\InvalidEmail;
use Egulias\EmailValidator\Result\Reason\ExpectingATEXT;

abstract class Parser
{
    /**
     * @var array
     */

    protected $warnings = [];

    /**
     * @var EmailLexer
     */
    protected $lexer;

    abstract protected function parseRightFromAt() : Result;
    abstract protected function parseLeftFromAt() : Result;
    abstract protected function preRightParsing() : Result;

    public function parse(string $str) : Result
    {
        $this->lexer->setInput($str);

        if ($this->lexer->hasInvalidTokens()) {
            return new InvalidEmail(new ExpectingATEXT("Invalid tokens found"), $this->lexer->token["value"]);
        }

        $preParsingResult = $this->preRightParsing();
        if ($preParsingResult->isInvalid()) {
            return $preParsingResult;
        }

        $localPartResult = $this->parseRightFromAt();

        if ($localPartResult->isInvalid()) {
            return $localPartResult;
        }

        $domainPartResult = $this->parseLeftFromAt();

        if ($domainPartResult->isInvalid()) {
            return $domainPartResult;
        }

        return new ValidEmail();
    }

    /**
     * @return Warning\Warning[]
     */
    public function getWarnings() : array
    {
        return $this->warnings;
    }
}