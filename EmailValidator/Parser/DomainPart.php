<?php

namespace Egulias\EmailValidator\Parser;

use Egulias\EmailValidator\EmailLexer;
use Egulias\EmailValidator\Exception\CommaInDomain;
use Egulias\EmailValidator\Exception\ExpectingATEXT;
use Egulias\EmailValidator\Result\InvalidEmail;
use Egulias\EmailValidator\Result\Reason\CharNotAllowed as ReasonCharNotAllowed;
use Egulias\EmailValidator\Result\Reason\DomainHyphened as ReasonDomainHyphened;
use Egulias\EmailValidator\Result\Reason\DotAtEnd as ReasonDotAtEnd;
use Egulias\EmailValidator\Result\Reason\DotAtStart;
use Egulias\EmailValidator\Result\Reason\NoDomainPart as ReasonNoDomainPart;
use Egulias\EmailValidator\Result\Result;
use Egulias\EmailValidator\Result\ValidEmail;
use Egulias\EmailValidator\Warning\DeprecatedComment;
use Egulias\EmailValidator\Warning\DomainLiteral;
use Egulias\EmailValidator\Warning\DomainTooLong;
use Egulias\EmailValidator\Warning\LabelTooLong;
use Egulias\EmailValidator\Warning\TLD;
use Egulias\EmailValidator\Parser\DomainLiteral as DomainLiteralParser;
use Egulias\EmailValidator\Result\Reason\ConsecutiveAt as ReasonConsecutiveAt;
use Egulias\EmailValidator\Result\Reason\ExpectingDomainLiteralClose;

class DomainPart extends Parser
{
    const DOMAIN_MAX_LENGTH = 254;

    /**
     * @var string
     */
    protected $domainPart = '';

    public function parse($domainPart)
    {
        $this->lexer->moveNext();

        $domainChecks = $this->performDomainStartChecks();
        if ($domainChecks->isInvalid()) {
            return $domainChecks;
        }

        $domain = $this->doParseDomainPart();
        if ($domain->isInvalid()) {
            return $domain;
        }

        $prev = $this->lexer->getPrevious();
        $length = strlen($this->domainPart);

        if ($prev['type'] === EmailLexer::S_DOT) {
            return new InvalidEmail(new ReasonDotAtEnd(), $this->lexer->token['value']);
        }
        if ($prev['type'] === EmailLexer::S_HYPHEN) {
            return new InvalidEmail(new ReasonDomainHyphened('Hypen found at the end of the domain'), $prev['value']);
        }
        if ($length > self::DOMAIN_MAX_LENGTH) {
            $this->warnings[DomainTooLong::CODE] = new DomainTooLong();
        }

        return new ValidEmail();
    }

    private function performDomainStartChecks() : Result
    {
        $invalidTokens = $this->checkInvalidTokensAfterAT();
        if ($invalidTokens->isInvalid()) {
            return $invalidTokens;
        }
        
        $missingDomain = $this->checkEmptyDomain();
        if ($missingDomain->isInvalid()) {
            return $missingDomain;
        }

        if ($this->lexer->token['type'] === EmailLexer::S_OPENPARENTHESIS) {
            $this->warnings[DeprecatedComment::CODE] = new DeprecatedComment();
        }
        return new ValidEmail();
    }

    private function checkEmptyDomain() : Result
    {
        $thereIsNoDomain = $this->lexer->token['type'] === EmailLexer::S_EMPTY ||
            ($this->lexer->token['type'] === EmailLexer::S_SP &&
            !$this->lexer->isNextToken(EmailLexer::GENERIC));

        if ($thereIsNoDomain) {
            return new InvalidEmail(new ReasonNoDomainPart(), $this->lexer->token['value']);
        }

        return new ValidEmail();
    }

    private function checkInvalidTokensAfterAT() : Result
    {
        if ($this->lexer->token['type'] === EmailLexer::S_DOT) {
            return new InvalidEmail(new DotAtStart(), $this->lexer->token['value']);
        }
        if ($this->lexer->token['type'] === EmailLexer::S_HYPHEN) {
            return new InvalidEmail(new ReasonDomainHyphened('After AT'), $this->lexer->token['value']);
        }
        return new ValidEmail();
    }

    /**
     * @return string
     */
    public function getDomainPart()
    {
        return $this->domainPart;
    }

    protected function parseComments()
    {
        $commentParser = new Comment($this->lexer, new DomainComment());
        $result = $commentParser->parse('remove');
        if($result->isInvalid()) {
            return $result;
        }

        $this->warnings = array_merge($this->warnings, $commentParser->getWarnings());
        return $result;
    }

    protected function doParseDomainPart() : Result
    {
        $domain = '';
        do {
            $prev = $this->lexer->getPrevious();

            $notAllowedChars = $this->checkNotAllowedChars($this->lexer->token);
            if ($notAllowedChars->isInvalid()) {
                return $notAllowedChars;
            }

            if ($this->lexer->token['type'] === EmailLexer::S_OPENPARENTHESIS || 
                $this->lexer->token['type'] === EmailLexer::S_CLOSEPARENTHESIS ) {
                $commentsResult = $this->parseComments();

                //Invalid comment parsing
                if($commentsResult->isInvalid()) {
                    return $commentsResult;
                }
            }

            $dotsResult = $this->checkConsecutiveDots();
            if ($dotsResult) {
                return $dotsResult;
            }
            $exceptionsResult = $this->checkDomainPartExceptions($prev);
            if ($exceptionsResult->isInvalid()) {
                return $exceptionsResult;
            }

            if ($this->lexer->token['type'] === EmailLexer::S_OPENBRACKET) {
                $literalResult = $this->parseDomainLiteral();
                //Invalid literal parsing
                if($literalResult->isInvalid()) {
                    return $literalResult;
                }
            }

            $this->checkLabelLength($prev);

            if ($this->isFWS()) {
                $FwsResult = $this->parseFWS();
                if($FwsResult->isInvalid()) {
                    return $FwsResult;
                }
            }

            $domain .= $this->lexer->token['value'];
            $this->lexer->moveNext();
        } while (null !== $this->lexer->token['type']);

        $this->domainPart = $domain;
        return new ValidEmail();
    }

    private function checkNotAllowedChars(array $token) : Result
    {
        $notAllowed = [EmailLexer::S_BACKSLASH => true, EmailLexer::S_SLASH=> true];
        if (isset($notAllowed[$token['type']])) {
            return new InvalidEmail(new ReasonCharNotAllowed(), $token['value']);
        }
        return new ValidEmail();
    }

    /**
     * @return string|false
     */
    protected function parseDomainLiteral() : Result
    {

        try {
            $this->lexer->find(EmailLexer::S_CLOSEBRACKET);
        } catch (\RuntimeException $e) {
            return new InvalidEmail(new ExpectingDomainLiteralClose(), $this->lexer->token['value']);
        }

        $domainLiteralParser = new DomainLiteralParser($this->lexer);
        $result = $domainLiteralParser->parse('remove');
        $this->warnings = array_merge($this->warnings, $domainLiteralParser->getWarnings());
        return $result;

        //return $this->doParseDomainLiteral();
    }

    protected function checkDomainPartExceptions(array $prev)
    {
        $invalidDomainTokens = array(
            EmailLexer::S_DQUOTE => true,
            EmailLexer::S_SEMICOLON => true,
            EmailLexer::S_GREATERTHAN => true,
            EmailLexer::S_LOWERTHAN => true,
        );

        if (isset($invalidDomainTokens[$this->lexer->token['type']])) {
            throw new ExpectingATEXT();
        }

        if ($this->lexer->token['type'] === EmailLexer::S_COMMA) {
            throw new CommaInDomain();
        }

        if ($this->lexer->token['type'] === EmailLexer::S_AT) {
            return new InvalidEmail(new ReasonConsecutiveAt(), $this->lexer->token['value']);
        }

        if ($this->lexer->token['type'] === EmailLexer::S_OPENQBRACKET && $prev['type'] !== EmailLexer::S_AT) {
            throw new ExpectingATEXT();
        }

        if ($this->lexer->token['type'] === EmailLexer::S_HYPHEN && $this->lexer->isNextToken(EmailLexer::S_DOT)) {
            return new InvalidEmail(new ReasonDomainHyphened('Hypen found near DOT'), $this->lexer->token['value']);
        }

        if ($this->lexer->token['type'] === EmailLexer::S_BACKSLASH
            && $this->lexer->isNextToken(EmailLexer::GENERIC)) {
            throw new ExpectingATEXT();
        }

        return new ValidEmail();
    }

    protected function checkLabelLength(array $prev)
    {
        if ($this->lexer->token['type'] === EmailLexer::S_DOT &&
            $prev['type'] === EmailLexer::GENERIC &&
            strlen($prev['value']) > 63
        ) {
            $this->warnings[LabelTooLong::CODE] = new LabelTooLong();
        }
    }

    protected function addTLDWarnings()
    {
        if ($this->warnings[DomainLiteral::CODE]) {
            $this->warnings[TLD::CODE] = new TLD();
        }
    }
}
