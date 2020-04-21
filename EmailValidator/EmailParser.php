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

/**
 * EmailParser
 *
 * @author Eduardo Gulias Davis <me@egulias.com>
 */
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
        $this->localPartParser = new LocalPart($this->lexer);
        $this->domainPartParser = new DomainPart($this->lexer);
    }

    /**
     * @param string $str
     * @return array
     */
    public function parse($str) : Result
    {
        $this->lexer->setInput($str);

        if (!$this->hasAtToken()) {
            return new InvalidEmail(new NoLocalPart(), $this->lexer->token["value"]);
        }

        $this->localPartParser->parse($str);
        $this->domainPartParser->parse($str);

        $this->setParts($str);

        if ($this->lexer->hasInvalidTokens()) {
            return new InvalidEmail(new ExpectingATEXT("Invalid tokens found"), $this->lexer->token["value"]);
        }

        return new ValidEmail();
        //return array('local' => $this->localPart, 'domain' => $this->domainPart);
    }

    /**
     * @return Warning\Warning[]
     */
    public function getWarnings()
    {
        $localPartWarnings = $this->localPartParser->getWarnings();
        $domainPartWarnings = $this->domainPartParser->getWarnings();
        $this->warnings = array_merge($localPartWarnings, $domainPartWarnings);

        $this->addLongEmailWarning($this->localPart, $this->domainPart);

        return $this->warnings;
    }

    /**
     * @return string
     */
    public function getParsedDomainPart()
    {
        return $this->domainPart;
    }

    /**
     * @param string $email
     */
    protected function setParts($email)
    {
        $parts = explode('@', $email);
        $this->domainPart = $this->domainPartParser->getDomainPart();
        $this->localPart = $parts[0];
    }

    /**
     * @return bool
     */
    protected function hasAtToken()
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
    protected function addLongEmailWarning($localPart, $parsedDomainPart)
    {
        if (strlen($localPart . '@' . $parsedDomainPart) > self::EMAIL_MAX_LENGTH) {
            $this->warnings[EmailTooLong::CODE] = new EmailTooLong();
        }
    }
}
