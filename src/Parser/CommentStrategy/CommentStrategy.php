<?php

namespace Egulias\EmailValidator\Parser\CommentStrategy;

use Egulias\EmailValidator\EmailLexer;
use Egulias\EmailValidator\Result\Result;

interface CommentStrategy
{
    /**
     * Return "true" to continue, "false" to exit
     */
    public function exitCondition(EmailLexer $lexer, int $openedParenthesis) : bool;

    public function endOfLoopValidations(EmailLexer $lexer) : Result;

    public function getWarnings() : array;
}