<?php

namespace Egulias\EmailValidator\Parser;

use Egulias\EmailValidator\EmailLexer;
use Egulias\EmailValidator\Result\Result;
use Egulias\EmailValidator\Result\ValidEmail;
use Egulias\EmailValidator\Exception\DotAtEnd;
use Egulias\EmailValidator\Result\InvalidEmail;
use Egulias\EmailValidator\Warning\LocalTooLong;
use Egulias\EmailValidator\Exception\ExpectingATEXT;
use Egulias\EmailValidator\Result\Reason\DotAtStart;
use Egulias\EmailValidator\Result\Reason\UnOpenedComment;

class LocalPart extends Parser
{
    public function parse($localPart) : Result
    {
        $parseDQuote = true;
        $closingQuote = false;
        $openedParenthesis = 0;
        $totalLength = 0;

        while ($this->lexer->token['type'] !== EmailLexer::S_AT && null !== $this->lexer->token['type']) {
            if ($this->hasDotAtStart()) {
                return new InvalidEmail(new DotAtStart(), $this->lexer->token['value']);
            }

            if ($this->lexer->token['type'] === EmailLexer::S_DQUOTE) {
                $dquoteParsingResult = $this->parseDoubleQuote();
                $parseDQuote = !$dquoteParsingResult->isValid();

                //Invalid double quote parsing
                if($parseDQuote) {
                    return $dquoteParsingResult;
                }
            }

            if ($this->lexer->token['type'] === EmailLexer::S_OPENPARENTHESIS) {
                $this->parseComments();
                $openedParenthesis += $this->getOpenedParenthesis();
            }

            if ($this->lexer->token['type'] === EmailLexer::S_CLOSEPARENTHESIS) {
                if ($openedParenthesis === 0) {
                    return new InvalidEmail(new UnOpenedComment(), $this->lexer->token['value']);
                }

                $openedParenthesis--;
            }

            $this->checkConsecutiveDots();

            if ($this->lexer->token['type'] === EmailLexer::S_DOT &&
                $this->lexer->isNextToken(EmailLexer::S_AT)
            ) {
                throw new DotAtEnd();
            }

            $this->warnEscaping();
            $this->isInvalidToken($this->lexer->token, $closingQuote);

            if ($this->isFWS()) {
                $this->parseFWS();
            }

            $totalLength += strlen($this->lexer->token['value']);
            $this->lexer->moveNext();
        }

        if ($totalLength > LocalTooLong::LOCAL_PART_LENGTH) {
            $this->warnings[LocalTooLong::CODE] = new LocalTooLong();
        }

        return new ValidEmail();
    }

    protected function hasDotAtStart() : bool
    {
            return $this->lexer->token['type'] === EmailLexer::S_DOT && null === $this->lexer->getPrevious()['type'];
    }

    protected function parseDoubleQuote() : Result
    {
        $dquoteParser = new DoubleQuote($this->lexer);
        $parseAgain = $dquoteParser->parse("remove useless arg");
        $warns = $dquoteParser->getWarnings();
        foreach ($warns as $code => $dWarning) {
            $this->warnings[$code] = $dWarning;
        }

        return $parseAgain;
    }

    /**
     * @param bool $closingQuote
     */
    protected function isInvalidToken(array $token, $closingQuote)
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
            throw new ExpectingATEXT();
        }
    }
}