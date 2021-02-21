<?php

namespace Egulias\EmailValidator;

use Egulias\EmailValidator\Parser;
use Egulias\EmailValidator\EmailLexer;
use Egulias\EmailValidator\Result\Result;
use Egulias\EmailValidator\Parser\LocalPart;
use Egulias\EmailValidator\Parser\IDLeftPart;
use Egulias\EmailValidator\Result\ValidEmail;
use Egulias\EmailValidator\Result\InvalidEmail;
use Egulias\EmailValidator\Warning\EmailTooLong;
use Egulias\EmailValidator\Result\Reason\NoLocalPart;

class MessageIDParser extends Parser
{

    const EMAILID_MAX_LENGTH = 254;

    /**
     * @var string
     */
    protected $idLeft = '';

    /**
     * @var string
     */
    protected $idRight = '';

    public function __construct(EmailLexer $lexer)
    {
        $this->lexer = $lexer;
    }

    public function parse(string $str) : Result
    {
        $result = parent::parse($str);

        $this->addLongEmailWarning($this->localPart, $this->domainPart);

        return $result;
    }
    
    protected function preRightParsing(): Result
    {
        if (!$this->hasAtToken()) {
            return new InvalidEmail(new NoLocalPart(), $this->lexer->token["value"]);
        }
        return new ValidEmail();
    }

    protected function parseRightFromAt(): Result
    {
        return $this->processIDRight();
    }

    protected function parseLeftFromAt(): Result
    {
        return $this->processIDLeft();
    }

    private function processIDRight() : Result
    {
        $localPartParser = new LocalPart($this->lexer);
        $localPartResult = $localPartParser->parse();
        $this->localPart = $localPartParser->localPart();
        $this->warnings = array_merge($localPartParser->getWarnings(), $this->warnings);

        return $localPartResult;
    }

    private function processIDLeft() : Result
    {
        $domainPartParser = new IDLeftPart($this->lexer);
        $domainPartResult = $domainPartParser->parse();
        $this->domainPart = $domainPartParser->domainPart();
        $this->warnings = array_merge($domainPartParser->getWarnings(), $this->warnings);
        
        return $domainPartResult;
    }

    public function getLeftPart() : string
    {
        return $this->idLeft;
    }

    public function getRightPart() : string
    {
        return $this->idRight;
    }

    private function hasAtToken() : bool
    {
        $this->lexer->moveNext();
        $this->lexer->moveNext();
        if ($this->lexer->token['type'] === EmailLexer::S_AT) {
            return false;
        }

        return true;
    }

    private function addLongEmailWarning(string $localPart, string $parsedDomainPart) : void
    {
        if (strlen($localPart . '@' . $parsedDomainPart) > self::EMAILID_MAX_LENGTH) {
            $this->warnings[EmailTooLong::CODE] = new EmailTooLong();
        }
    }
}