<?php
namespace Egulias\EmailValidator\Parser;

use Egulias\EmailValidator\EmailLexer;
use Egulias\EmailValidator\Result\ValidEmail;
use Egulias\EmailValidator\Result\InvalidEmail;
use Egulias\EmailValidator\Warning\CFWSWithFWS;
use Egulias\EmailValidator\Warning\QuotedString;
use Egulias\EmailValidator\Result\Reason\ExpectingATEXT;
use Egulias\EmailValidator\Result\Reason\UnclosedQuotedString;
use Egulias\EmailValidator\Result\Result;

class DoubleQuote extends PartParser
{
    public function parse() : Result
    {

        $validQuotedString = $this->checkDQUOTE();
        if($validQuotedString->isInvalid()) return $validQuotedString;

        $special = [
            EmailLexer::S_CR => true,
            EmailLexer::S_HTAB => true,
            EmailLexer::S_LF => true
        ];

        $invalid = [
            EmailLexer::C_NUL => true,
            EmailLexer::S_HTAB => true,
            EmailLexer::S_CR => true,
            EmailLexer::S_LF => true
        ];
        
        $setSpecialsWarning = true;

        $this->lexer->moveNext();

        while ($this->lexer->getToken()['type'] !== EmailLexer::S_DQUOTE && null !== $this->lexer->getToken()['type']) {
            if (isset($special[$this->lexer->getToken()['type']]) && $setSpecialsWarning) {
                $this->warnings[CFWSWithFWS::CODE] = new CFWSWithFWS();
                $setSpecialsWarning = false;
            }
            if ($this->lexer->getToken()['type'] === EmailLexer::S_BACKSLASH && $this->lexer->isNextToken(EmailLexer::S_DQUOTE)) {
                $this->lexer->moveNext();
            }

            $this->lexer->moveNext();

            if (!$this->escaped() && isset($invalid[$this->lexer->getToken()['type']])) {
                return new InvalidEmail(new ExpectingATEXT("Expecting ATEXT between DQUOTE"), $this->lexer->getToken()['value']);
            }
        }

        $prev = $this->lexer->getPrevious();

        if ($prev['type'] === EmailLexer::S_BACKSLASH) {
            $validQuotedString = $this->checkDQUOTE();
            if($validQuotedString->isInvalid()) return $validQuotedString;
        }

        if (!$this->lexer->isNextToken(EmailLexer::S_AT) && $prev['type'] !== EmailLexer::S_BACKSLASH) {
            return new InvalidEmail(new ExpectingATEXT("Expecting ATEXT between DQUOTE"), $this->lexer->getToken()['value']);
        }

        return new ValidEmail();
    }

    protected function checkDQUOTE() : Result
    {
        $previous = $this->lexer->getPrevious();

        if ($this->lexer->isNextToken(EmailLexer::GENERIC) && $previous['type'] === EmailLexer::GENERIC) {
            $description = 'https://tools.ietf.org/html/rfc5322#section-3.2.4 - quoted string should be a unit';
            return new InvalidEmail(new ExpectingATEXT($description), $this->lexer->getToken()['value']);
        }

        try {
            $this->lexer->find(EmailLexer::S_DQUOTE);
        } catch (\Exception $e) {
            return new InvalidEmail(new UnclosedQuotedString(), $this->lexer->getToken()['value']);
        }
        $this->warnings[QuotedString::CODE] = new QuotedString($previous['value'], $this->lexer->getToken()['value']);

        return new ValidEmail();
    }

}
