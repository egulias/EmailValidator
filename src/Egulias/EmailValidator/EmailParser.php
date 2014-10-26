<?php

namespace Egulias\EmailValidator;

use Egulias\EmailValidator\Parser\DomainPart;
use Egulias\EmailValidator\Parser\LocalPart;

/**
 * EmailParser
 *
 * @author Eduardo Gulias Davis <me@egulias.com>
 */
class EmailParser
{
    const EMAIL_MAX_LENGTH = 254;

    protected $warnings = array();
    protected $domainPart = '';
    protected $localPart = '';
    protected $lexer;
    protected $localPartParser;
    protected $domainPartParser;

    public function __construct(EmailLexer $lexer)
    {
        $this->lexer = $lexer;
        $this->localPartParser = new LocalPart($this->lexer);
        $this->domainPartParser = new DomainPart($this->lexer);
    }

    /**
     * @param string $str
     */
    public function parse($str)
    {
        $this->lexer->setInput($str);

        if (!$this->hasAtToken()) {
            throw new \InvalidArgumentException('ERR_NOLOCALPART');
        }

        if ($this->lexer->hasInvalidTokens()) {
            throw new \InvalidArgumentException('ERR_INVALID_ATEXT');
        }

        $this->localPartParser->parse($str);
        $this->domainPartParser->parse($str);

        $this->setParts($str);

        return array('local' => $this->localPart, 'domain' => $this->domainPart);
    }

    public function getWarnings()
    {
        $localPartWarnings = $this->localPartParser->getWarnings();
        $domainPartWarnings = $this->domainPartParser->getWarnings();

        $this->warnings = array_merge($localPartWarnings, $domainPartWarnings);
        $this->addLongEmailWarning($this->localPart, $this->domainPart);

        return $this->warnings;
    }

    public function getParsedDomainPart()
    {
        return $this->domainPart;
    }

    protected function setParts($email)
    {
        $parts = explode('@', $email);
        $this->domainPart = $this->domainPartParser->getDomainPart();
        $this->localPart = $parts[0];
    }

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
            $this->warnings[] = EmailValidator::RFC5322_TOOLONG;
        }
    }
}
