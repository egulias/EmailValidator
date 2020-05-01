<?php
namespace Egulias\EmailValidator\Parser;

use Egulias\EmailValidator\EmailLexer;
use Egulias\EmailValidator\Warning\CFWSWithFWS;
use Egulias\EmailValidator\Exception\ExpectingAT;
use Egulias\EmailValidator\Exception\ExpectingATEXT;
use Egulias\EmailValidator\Exception\UnclosedQuotedString;

class DoubleQuote extends Parser
{
    public function parse($qouted)
    {
        if ($this->lexer->token['type'] !== EmailLexer::S_DQUOTE) {
            return true;
        }
        if(!$this->checkDQUOTE(false)) return false;
        $parseAgain = true;
        $special = array(
            EmailLexer::S_CR => true,
            EmailLexer::S_HTAB => true,
            EmailLexer::S_LF => true
        );

        $invalid = array(
            EmailLexer::C_NUL => true,
            EmailLexer::S_HTAB => true,
            EmailLexer::S_CR => true,
            EmailLexer::S_LF => true
        );
        $setSpecialsWarning = true;

        $this->lexer->moveNext();

        while ($this->lexer->token['type'] !== EmailLexer::S_DQUOTE && null !== $this->lexer->token['type']) {
            $parseAgain = false;
            if (isset($special[$this->lexer->token['type']]) && $setSpecialsWarning) {
                $this->warnings[CFWSWithFWS::CODE] = new CFWSWithFWS();
                $setSpecialsWarning = false;
            }
            if ($this->lexer->token['type'] === EmailLexer::S_BACKSLASH && $this->lexer->isNextToken(EmailLexer::S_DQUOTE)) {
                $this->lexer->moveNext();
            }

            $this->lexer->moveNext();

            if (!$this->escaped() && isset($invalid[$this->lexer->token['type']])) {
                throw new ExpectingATEXT();
            }
        }

        $prev = $this->lexer->getPrevious();

        if ($prev['type'] === EmailLexer::S_BACKSLASH) {
            if (!$this->checkDQUOTE(false)) {
                throw new UnclosedQuotedString();
            }
        }

        if (!$this->lexer->isNextToken(EmailLexer::S_AT) && $prev['type'] !== EmailLexer::S_BACKSLASH) {
            throw new ExpectingAT();
        }

        return $parseAgain;
    }

}