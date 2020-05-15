<?php

namespace Egulias\EmailValidator\Parser;

use Egulias\EmailValidator\EmailLexer;
use Egulias\EmailValidator\Result\Result;
use Egulias\EmailValidator\Result\ValidEmail;
use Egulias\EmailValidator\Warning\CFWSNearAt;
use Egulias\EmailValidator\Result\InvalidEmail;
use Egulias\EmailValidator\Result\Reason\ExpectingATEXT;
use Egulias\EmailValidator\Result\Reason\UnclosedComment;
use Egulias\EmailValidator\Result\Reason\UnOpenedComment;
use Egulias\EmailValidator\Warning\Comment as WarningComment;


class Comment extends Parser
{
    private $MopenedParenthesis = 0;

    public function parse($str)
    {
        if ($this->lexer->token['type'] === EmailLexer::S_OPENPARENTHESIS) {
            $this->MopenedParenthesis++;
            if($this->noClosingParenthesis()) {
                return new InvalidEmail(new UnclosedComment(), $this->lexer->token['value']);
            }
        }

        if ($this->lexer->token['type'] === EmailLexer::S_CLOSEPARENTHESIS) {
            return new InvalidEmail(new UnOpenedComment(), $this->lexer->token['value']);
        }

        $this->warnings[WarningComment::CODE] = new WarningComment();
        while (!$this->lexer->isNextToken(EmailLexer::S_AT)) {//!$this->lexer->isNextToken(EmailLexer::S_CLOSEPARENTHESIS)) {
            if ($this->lexer->isNextToken(EmailLexer::S_OPENPARENTHESIS)) {
                $this->MopenedParenthesis++;
            }
            $this->warnEscaping();
            if($this->lexer->isNextToken(EmailLexer::S_CLOSEPARENTHESIS)) {
                $this->MopenedParenthesis--;
            }
            $this->lexer->moveNext();
        }

        if($this->MopenedParenthesis >= 1) {
            return new InvalidEmail(new UnclosedComment(), $this->lexer->token['value']);
        } else if ($this->MopenedParenthesis < 0) {
            return new InvalidEmail(new UnOpenedComment(), $this->lexer->token['value']);
        }

        if (!$this->lexer->isNextToken(EmailLexer::S_AT)) {
            return new InvalidEmail(new ExpectingATEXT('ATEX is not expected after closing comments'), $this->lexer->token['value']);
        }

        //You should always end at @
        $this->warnings[CFWSNearAt::CODE] = new CFWSNearAt();
        return new ValidEmail();
    }

    private function noClosingParenthesis() : bool 
    {
        try {
            $this->lexer->find(EmailLexer::S_CLOSEPARENTHESIS);
            return false;
        } catch (\RuntimeException $e) {
            return true;
        }
    }
}