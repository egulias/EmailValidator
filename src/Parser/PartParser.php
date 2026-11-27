<?php

namespace Egulias\EmailValidator\Parser;

use Egulias\EmailValidator\EmailLexer;
use Egulias\EmailValidator\Result\InvalidEmail;
use Egulias\EmailValidator\Result\Reason\ConsecutiveDot;
use Egulias\EmailValidator\Result\Result;
use Egulias\EmailValidator\Result\ValidEmail;
use Egulias\EmailValidator\Warning\Warning;

abstract class PartParser
{
    /**
     * @var Warning[]
     */
    protected $warnings = [];

    /**
     * @var EmailLexer
     */
    protected $lexer;

    private static ?ValidEmail $validEmail = null;

    public function __construct(EmailLexer $lexer)
    {
        $this->lexer = $lexer;
    }

    abstract public function parse(): Result;

    protected function validEmail(): ValidEmail
    {
        return self::$validEmail ??= new ValidEmail();
    }

    /**
     * @return Warning[]
     */
    public function getWarnings()
    {
        return $this->warnings;
    }

    protected function parseFWS(): Result
    {
        if (!FoldingWhiteSpace::isFWSType($this->lexer->current->type)) {
            return $this->validEmail();
        }

        $foldingWS = new FoldingWhiteSpace($this->lexer);
        $resultFWS = $foldingWS->parse();
        if ($resultFWS->isValid()) {
            $warnings = $foldingWS->getWarnings();
            if ($warnings !== []) {
                $this->warnings = [...$this->warnings, ...$warnings];
            }
        }
        return $resultFWS;
    }

    protected function checkConsecutiveDots(): Result
    {
        if ($this->lexer->current->type === EmailLexer::S_DOT && $this->lexer->isNextToken(EmailLexer::S_DOT)) {
            return new InvalidEmail(new ConsecutiveDot(), $this->lexer->current->value);
        }

        return $this->validEmail();
    }

    protected function escaped(): bool
    {
        $previous = $this->lexer->getPrevious();

        return $previous->type === EmailLexer::S_BACKSLASH
            && $this->lexer->current->type !== EmailLexer::GENERIC;
    }
}
