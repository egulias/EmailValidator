<?php

namespace Egulias\EmailValidator\Parser;

use Egulias\EmailValidator\EmailLexer;
use Egulias\EmailValidator\Result\Result;
use Egulias\EmailValidator\Result\ValidEmail;
use Egulias\EmailValidator\Result\InvalidEmail;
use Egulias\EmailValidator\Warning\LocalTooLong;
use Egulias\EmailValidator\Result\Reason\DotAtEnd;
use Egulias\EmailValidator\Result\Reason\DotAtStart;
use Egulias\EmailValidator\Result\Reason\ConsecutiveDot;
use Egulias\EmailValidator\Result\Reason\ExpectingATEXT;
use Egulias\EmailValidator\Parser\CommentStrategy\LocalComment;

class LocalPart extends PartParser
{
    public const INVALID_TOKENS = [
        EmailLexer::S_COMMA => EmailLexer::S_COMMA,
        EmailLexer::S_CLOSEBRACKET => EmailLexer::S_CLOSEBRACKET,
        EmailLexer::S_OPENBRACKET => EmailLexer::S_OPENBRACKET,
        EmailLexer::S_GREATERTHAN => EmailLexer::S_GREATERTHAN,
        EmailLexer::S_LOWERTHAN => EmailLexer::S_LOWERTHAN,
        EmailLexer::S_COLON => EmailLexer::S_COLON,
        EmailLexer::S_SEMICOLON => EmailLexer::S_SEMICOLON,
        EmailLexer::INVALID => EmailLexer::INVALID
    ];

    /**
     * @var string
     */
    private $localPart = '';


    public function parse() : Result
    {
        $this->lexer->startRecording();

        while (((array) $this->lexer->token)['type'] !== EmailLexer::S_AT && null !== ((array) $this->lexer->token)['type']) {
            if ($this->hasDotAtStart()) {
                return new InvalidEmail(new DotAtStart(), ((array) $this->lexer->token)['value']);
            }

            if (((array) $this->lexer->token)['type'] === EmailLexer::S_DQUOTE) {
                $dquoteParsingResult = $this->parseDoubleQuote();

                //Invalid double quote parsing
                if($dquoteParsingResult->isInvalid()) {
                    return $dquoteParsingResult;
                }
            }

            if (((array) $this->lexer->token)['type'] === EmailLexer::S_OPENPARENTHESIS ||
                ((array) $this->lexer->token)['type'] === EmailLexer::S_CLOSEPARENTHESIS ) {
                $commentsResult = $this->parseComments();

                //Invalid comment parsing
                if($commentsResult->isInvalid()) {
                    return $commentsResult;
                }
            }

            if (((array) $this->lexer->token)['type'] === EmailLexer::S_DOT && $this->lexer->isNextToken(EmailLexer::S_DOT)) {
                return new InvalidEmail(new ConsecutiveDot(), ((array) $this->lexer->token)['value']);
            }

            if (((array) $this->lexer->token)['type'] === EmailLexer::S_DOT &&
                $this->lexer->isNextToken(EmailLexer::S_AT)
            ) {
                return new InvalidEmail(new DotAtEnd(), ((array) $this->lexer->token)['value']);
            }

            $resultEscaping = $this->validateEscaping();
            if ($resultEscaping->isInvalid()) {
                return $resultEscaping;
            }

            $resultToken = $this->validateTokens(false);
            if ($resultToken->isInvalid()) {
                return $resultToken;
            }

            $resultFWS = $this->parseLocalFWS();
            if($resultFWS->isInvalid()) {
                return $resultFWS;
            }

            $this->lexer->moveNext();
        }

        $this->lexer->stopRecording();
        $this->localPart = rtrim($this->lexer->getAccumulatedValues(), '@');
        if (strlen($this->localPart) > LocalTooLong::LOCAL_PART_LENGTH) {
            $this->warnings[LocalTooLong::CODE] = new LocalTooLong();
        }

        return new ValidEmail();
    }

    protected function validateTokens(bool $hasComments) : Result
    {
        if (isset(self::INVALID_TOKENS[((array) $this->lexer->token)['type']])) {
            return new InvalidEmail(new ExpectingATEXT('Invalid token found'), ((array) $this->lexer->token)['value']);
        }
        return new ValidEmail();
    }

    public function localPart() : string
    {
        return $this->localPart;
    }

    private function parseLocalFWS() : Result
    {
        $foldingWS = new FoldingWhiteSpace($this->lexer);
        $resultFWS = $foldingWS->parse();
        if ($resultFWS->isValid()) {
            $this->warnings = array_merge($this->warnings, $foldingWS->getWarnings());
        }
        return $resultFWS;
    }

    private function hasDotAtStart() : bool
    {
            return ((array) $this->lexer->token)['type'] === EmailLexer::S_DOT && null === $this->lexer->getPrevious()['type'];
    }

    private function parseDoubleQuote() : Result
    {
        $dquoteParser = new DoubleQuote($this->lexer);
        $parseAgain = $dquoteParser->parse();
        $this->warnings = array_merge($this->warnings, $dquoteParser->getWarnings());

        return $parseAgain;
    }

    protected function parseComments(): Result
    {
        $commentParser = new Comment($this->lexer, new LocalComment());
        $result = $commentParser->parse();
        $this->warnings = array_merge($this->warnings, $commentParser->getWarnings());
        if($result->isInvalid()) {
            return $result;
        }
        return $result;
    }

    private function validateEscaping() : Result
    {
        //Backslash found
        if (((array) $this->lexer->token)['type'] !== EmailLexer::S_BACKSLASH) {
            return new ValidEmail();
        }

        if ($this->lexer->isNextToken(EmailLexer::GENERIC)) {
            return new InvalidEmail(new ExpectingATEXT('Found ATOM after escaping'), ((array) $this->lexer->token)['value']);
        }

        if (!$this->lexer->isNextTokenAny(array(EmailLexer::S_SP, EmailLexer::S_HTAB, EmailLexer::C_DEL))) {
            return new ValidEmail();
        }

        return new ValidEmail();
    }
}
