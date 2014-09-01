<?php

namespace Egulias\EmailValidator\Parser;

use Egulias\EmailValidator\EmailLexer;
use Egulias\EmailValidator\EmailValidator;


class LocalPart extends Parser
{
    public function parse($localPart)
    {
        $parseDQuote = true;
        $closingQuote = false;

        while ($this->lexer->token['type'] !== EmailLexer::S_AT && $this->lexer->token) {

            if ($this->lexer->token['type'] === EmailLexer::S_DOT && !$this->lexer->getPrevious()) {
                throw new \InvalidArgumentException('ERR_DOT_START');
            }

            $closingQuote = $this->checkDQUOTE($closingQuote);
            if ($closingQuote && $parseDQuote) {
                $this->parseDoubleQuote();
                $parseDQuote = false;
            }

            if ($this->lexer->token['type'] === EmailLexer::S_OPENPARENTHESIS) {
                $this->parseComments();
            }

            $this->checkConsecutiveDots();

            if (
                $this->lexer->token['type'] === EmailLexer::S_DOT &&
                $this->lexer->isNextToken(EmailLexer::S_AT)
            ) {
                throw new \InvalidArgumentException('ERR_DOT_END');
            }

            $this->warnEscaping();
            $this->isInvalidToken($this->lexer->token, $closingQuote);

            if ($this->isFWS()) {
                $this->parseFWS();
            }

            $this->lexer->moveNext();
        }

        $prev = $this->lexer->getPrevious();
        if (strlen($prev['value']) > EmailValidator::RFC5322_LOCAL_TOOLONG) {
            $this->warnings[] = EmailValidator::RFC5322_LOCAL_TOOLONG;
        }
    }

    protected function parseDoubleQuote()
    {
        $special = array (
            EmailLexer::S_CR => true,
            EmailLexer::S_HTAB => true,
            EmailLexer::S_LF => true
        );
        $setSpecialsWarning = true;

        $this->lexer->moveNext();
        while ($this->lexer->token['type'] !== EmailLexer::S_DQUOTE && $this->lexer->token) {
            if (isset($special[$this->lexer->token['type']]) && $setSpecialsWarning) {
                $this->warnings[] = EmailValidator::CFWS_FWS;
                $setSpecialsWarning = false;
            }
            $this->lexer->moveNext();
        }
    }


    protected function isInvalidToken($token, $closingQuote)
    {
        $forbidden = array(
            EmailLexer::S_COMMA,
            EmailLexer::S_CLOSEBRACKET,
            EmailLexer::S_OPENBRACKET,
            EmailLexer::S_GREATERTHAN,
            EmailLexer::S_LOWERTHAN,
            EmailLexer::S_COLON,
            EmailLexer::S_SEMICOLON,
            EmailLexer::INVALID
        );

        if (in_array($token['type'], $forbidden) && !$closingQuote) {
            throw new \InvalidArgumentException('ERR_EXPECTING_ATEXT');
        }
    }
}
