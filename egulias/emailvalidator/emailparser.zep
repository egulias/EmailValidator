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
    protected warnings = [];
    protected domainPart = "";
    protected localPart = "";
    protected lexer;
    protected localPartParser;
    protected domainPartParser;
    public function __construct(<EmailLexer> lexer) -> void
    {
        let this->lexer = lexer;
        let this->localPartParser =  new LocalPart(this->lexer);
        let this->domainPartParser =  new DomainPart(this->lexer);
    }
    
    /**
     * @param $str
     * @return array
     */
    public function parse(str) -> array
    {
        var tmpArray384eacd3233973d48b46f6665fe87aa5;
    
        this->lexer->setInput(str);
        
        if !this->hasAtToken() {
            throw new \InvalidArgumentException("ERR_NOLOCALPART");
        }
        this->localPartParser->parse(str);
        this->domainPartParser->parse(str);
        this->setParts(str);
        
        if this->lexer->hasInvalidTokens() {
            throw new \InvalidArgumentException("ERR_INVALID_ATEXT");
        }
        let tmpArray384eacd3233973d48b46f6665fe87aa5 = ["local" : this->localPart, "domain" : this->domainPart];
        return tmpArray384eacd3233973d48b46f6665fe87aa5;
    }
    
    public function getWarnings()
    {
        var localPartWarnings, domainPartWarnings;
    
        let localPartWarnings =  this->localPartParser->getWarnings();
        let domainPartWarnings =  this->domainPartParser->getWarnings();
        let this->warnings =  array_merge(localPartWarnings, domainPartWarnings);
        this->addLongEmailWarning(this->localPart, this->domainPart);
        
        return this->warnings;
    }
    
    public function getParsedDomainPart()
    {
        
        return this->domainPart;
    }
    
    protected function setParts(email) -> void
    {
        var parts;
    
        let parts =  explode("@", email);
        let this->domainPart =  this->domainPartParser->getDomainPart();
        let this->localPart = parts[0];
    }
    
    protected function hasAtToken()
    {
        this->lexer->moveNext();
        this->lexer->moveNext();
        
        if this->lexer->token["type"] === EmailLexer::S_AT {
            
            return false;
        }
        
        return true;
    }
    
    /**
     * @param string $localPart
     * @param string $parsedDomainPart
     */
    protected function addLongEmailWarning(string localPart, string parsedDomainPart) -> void
    {
        
        if strlen(localPart . "@" . parsedDomainPart) > self::EMAIL_MAX_LENGTH {
            let this->warnings[] = EmailValidator::RFC5322_TOOLONG;
        }
    }

}