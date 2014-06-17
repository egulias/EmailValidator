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
    protected $warnings = array();
    protected $domainPart = '';
    protected $lexer;
    protected $localPartParser;
    protected $domainPartParser;

    public function __construct(EmailLexer $lexer)
    {
        $this->lexer = $lexer;
        $this->localPartParser = new LocalPart($this->lexer);
        $this->domainPartParser = new DomainPart($this->lexer);
    }

    public function parse($str)
    {
        $this->lexer->setInput($str);

        if (!$this->hasAtToken()) {
            throw new \InvalidArgumentException('ERR_NOLOCALPART');
        }

        $this->localPartParser->parse($str);
        $this->domainPartParser->parse($str);
        $this->domainPart = $this->domainPartParser->getDomainPart($str);

        $parts = explode('@', $str);

        $this->longEmailWarning($parts[0], $this->domainPart);

        return array('local' => $parts[0], 'domain' => $this->domainPart);
    }

    public function getWarnings()
    {
        if (!$this->warnings) {
            $localPartWarnings = $this->localPartParser->getWarnings();
            $domainPartWarnings = $this->domainPartParser->getWarnings();

            $this->warnings = array_merge($localPartWarnings, $domainPartWarnings);
        }

        return $this->warnings;
    }

    public function getParsedDomainPart()
    {
        return $this->domainPart;
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

    protected function longEmailWarning($localPart, $parsedDomainPart)
    {
        if (strlen($localPart . '@' . $parsedDomainPart) >254) {
            $this->warnings[] = EmailValidator::RFC5322_TOOLONG;
        }
    }
}
