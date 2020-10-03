<?php

namespace Egulias\EmailValidator;

use Egulias\EmailValidator\EmailLexer;
use Egulias\EmailValidator\Result\Result;
use Egulias\EmailValidator\Parser\LocalPart;
use Egulias\EmailValidator\Parser\DomainPart;
use Egulias\EmailValidator\Result\ValidEmail;
use Egulias\EmailValidator\Result\InvalidEmail;
use Egulias\EmailValidator\Warning\EmailTooLong;
use Egulias\EmailValidator\Result\Reason\ExpectingATEXT;
use Egulias\EmailValidator\Result\Reason\NoLocalPart;

class EmailParser
{
    const EMAIL_MAX_LENGTH = 254;

    /**
     * @var array
     */
    protected $warnings = [];

    /**
     * @var string
     */
    protected $domainPart = '';

    /**
     * @var string
     */
    protected $localPart = '';
    /**
     * @var EmailLexer
     */
    protected $lexer;

    /**
     * @var LocalPart
     */
    protected $localPartParser;

    /**
     * @var DomainPart
     */
    protected $domainPartParser;

    public function __construct(EmailLexer $lexer)
    {
        $this->lexer = $lexer;
    }

    /**
     * @param string $str
     * @return Result 
     */
    public function parse($str) : Result
    {
        $this->lexer->setInput($str);

        if (!$this->hasAtToken()) {
            return new InvalidEmail(new NoLocalPart(), $this->lexer->token["value"]);
        }

        $localPartResult = $this->processLocalPart();

        if ($localPartResult->isInvalid()) {
            return $localPartResult;
        }

        $domainPartResult = $this->processDomainPart();

        if ($domainPartResult->isInvalid()) {
            return $domainPartResult;
        }

        if ($this->lexer->hasInvalidTokens()) {
            return new InvalidEmail(new ExpectingATEXT("Invalid tokens found"), $this->lexer->token["value"]);
        }

        $this->addLongEmailWarning($this->localPart, $this->domainPart);

        return new ValidEmail();
    }

    private function processLocalPart() : Result
    {
        $this->lexer->startRecording();
        $this->localPartParser = new LocalPart($this->lexer);
        $localPartResult = $this->localPartParser->parse();
        $this->lexer->stopRecording();
        $this->localPart = rtrim($this->lexer->getAccumulatedValues(), '@');
        $this->warnings = array_merge($this->localPartParser->getWarnings(), $this->warnings);

        return $localPartResult;
    }

    private function processDomainPart() : Result
    {
        $this->lexer->clearRecorded();
        $this->lexer->startRecording();
        $this->domainPartParser = new DomainPart($this->lexer);
        $domainPartResult = $this->domainPartParser->parse();
        $this->lexer->stopRecording();
        $this->domainPart = $this->lexer->getAccumulatedValues();
        $this->warnings = array_merge($this->domainPartParser->getWarnings(), $this->warnings);
        
        return $domainPartResult;
    }

    /**
     * @return Warning\Warning[]
     */
    public function getWarnings() : array
    {
        return $this->warnings;
    }

    /**
     * @return string
     */
    public function getDomainPart() : string
    {
        return $this->domainPart;
    }

    public function getLocalPart() : string
    {
        return $this->localPart;
    }

    /**
     * @return bool
     */
    protected function hasAtToken() : bool
    {
        $this->lexer->moveNext();
        $this->lexer->moveNext();
        if ($this->lexer->token['type'] === EmailLexer::S_AT) {
            return false;
        }

        return true;
    }

    /**
     * @param string $localPart
     * @param string $parsedDomainPart
     */
    protected function addLongEmailWarning($localPart, $parsedDomainPart) : void
    {
        if (strlen($localPart . '@' . $parsedDomainPart) > self::EMAIL_MAX_LENGTH) {
            $this->warnings[EmailTooLong::CODE] = new EmailTooLong();
        }
    }
}
