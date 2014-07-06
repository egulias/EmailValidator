<?php

namespace Egulias\EmailValidator\Parser;

use Egulias\EmailValidator\EmailLexer;
use Egulias\EmailValidator\Parser\Parser;
use Egulias\EmailValidator\EmailValidator;


class LocalPart extends Parser
{
    public function parse($localPart)
    {
        $closingQuote = false;
        while ($this->lexer->token['type'] !== EmailLexer::S_AT && $this->lexer->token) {

            if ($this->lexer->token['type'] === EmailLexer::S_DOT && !$this->lexer->getPrevious()) {
                throw new \InvalidArgumentException('ERR_DOT_START');
            }

            $closingQuote = $this->checkDQUOTE($closingQuote);

            if ($this->lexer->token['type'] === EmailLexer::S_OPENPARENTHESIS) {
                $this->parseComments();
            }

            $this->checkConsecutiveDots();

            if ($this->lexer->token['type'] === EmailLexer::S_DOT && $this->lexer->isNextToken(EmailLexer::S_AT)) {
                throw new \InvalidArgumentException('ERR_DOT_END');
            }

            $this->warnEscaping();

            if ($this->lexer->isNextTokenAny(
                array(
                    EmailLexer::INVALID, EmailLexer::S_LOWERTHAN, EmailLexer::S_GREATERTHAN
                )
            )
            ) {
                throw new \InvalidArgumentException('ERR_EXPECTING_ATEXT');
            }

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
}
