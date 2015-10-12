namespace Egulias\EmailValidator\Parser;

use Egulias\EmailValidator\EmailLexer;
use Egulias\EmailValidator\Parser\Parser;
use Egulias\EmailValidator\EmailValidator;
class DomainPart extends Parser
{
    const DOMAIN_MAX_LENGTH = 254;
    protected domainPart = "";
    public function parse(domainPart) -> void
    {
        var domain, prev, length;
    
        this->lexer->moveNext();
        
        if this->lexer->token["type"] === EmailLexer::S_DOT {
            throw new \InvalidArgumentException("ERR_DOT_START");
        }
        
        if this->lexer->token["type"] === EmailLexer::S_EMPTY {
            throw new \InvalidArgumentException("ERR_NODOMAIN");
        }
        
        if this->lexer->token["type"] === EmailLexer::S_HYPHEN {
            throw new \InvalidArgumentException("ERR_DOMAINHYPHENEND");
        }
        
        if this->lexer->token["type"] === EmailLexer::S_OPENPARENTHESIS {
            let this->warnings[] = EmailValidator::DEPREC_COMMENT;
            this->parseDomainComments();
        }
        let domain =  this->doParseDomainPart();
        let prev =  this->lexer->getPrevious();
        let length =  strlen(domain);
        
        if prev["type"] === EmailLexer::S_DOT {
            throw new \InvalidArgumentException("ERR_DOT_END");
        }
        
        if prev["type"] === EmailLexer::S_HYPHEN {
            throw new \InvalidArgumentException("ERR_DOMAINHYPHENEND");
        }
        
        if length > self::DOMAIN_MAX_LENGTH {
            let this->warnings[] = EmailValidator::RFC5322_DOMAIN_TOOLONG;
        }
        
        if prev["type"] === EmailLexer::S_CR {
            throw new \InvalidArgumentException("ERR_FWS_CRLF_END");
        }
        let this->domainPart = domain;
    }
    
    public function getDomainPart()
    {
        
        return this->domainPart;
    }
    
    public function checkIPV6Tag(addressLiteral, maxGroups = 8)
    {
        var prev, IPv6, matchesIP, groupCount, colons;
    
        let prev =  this->lexer->getPrevious();
        
        if prev["type"] === EmailLexer::S_COLON {
            let this->warnings[] = EmailValidator::RFC5322_IPV6_COLONEND;
        }
        let IPv6 =  substr(addressLiteral, 5);
        //Daniel Marschall's new IPv6 testing strategy
        let matchesIP =  explode(":", IPv6);
        let groupCount =  count(matchesIP);
        let colons =  strpos(IPv6, "::");
        
        if count(preg_grep("/^[0-9A-Fa-f]{0,4}$/", matchesIP, PREG_GREP_INVERT)) !== 0 {
            let this->warnings[] = EmailValidator::RFC5322_IPV6_BADCHAR;
        }
        
        if colons === false {
            // We need exactly the right number of groups
            
            if groupCount !== maxGroups {
                let this->warnings[] = EmailValidator::RFC5322_IPV6_GRPCOUNT;
            }
            
            return;
        }
        
        if colons !== strrpos(IPv6, "::") {
            let this->warnings[] = EmailValidator::RFC5322_IPV6_2X2XCOLON;
            
            return;
        }
        
        if colons === 0 || colons === strlen(IPv6) - 2 {
            // RFC 4291 allows :: at the start or end of an address
            //with 7 other groups in addition
            let maxGroups++;
        }
        
        if groupCount > maxGroups {
            let this->warnings[] = EmailValidator::RFC5322_IPV6_MAXGRPS;
        } elseif groupCount === maxGroups {
            let this->warnings[] = EmailValidator::RFC5321_IPV6DEPRECATED;
        }
    }
    
    protected function doParseDomainPart()
    {
        var domain, prev;
    
        let domain = "";
        do {
            let prev =  this->lexer->getPrevious();
            
            if this->lexer->token["type"] === EmailLexer::S_SLASH {
                throw new \InvalidArgumentException("ERR_DOMAIN_CHAR_NOT_ALLOWED");
            }
            
            if this->lexer->token["type"] === EmailLexer::S_OPENPARENTHESIS {
                this->parseComments();
                this->lexer->moveNext();
            }
            this->checkConsecutiveDots();
            this->checkDomainPartExceptions(prev);
            
            if this->hasBrackets() {
                this->parseDomainLiteral();
            }
            this->checkLabelLength(prev);
            
            if this->isFWS() {
                this->parseFWS();
            }
            let domain .= this->lexer->token["value"];
            this->lexer->moveNext();
        } while (this->lexer->token);
        
        return domain;
    }
    
    protected function parseDomainLiteral()
    {
        var lexer;
    
        
        if this->lexer->isNextToken(EmailLexer::S_COLON) {
            let this->warnings[] = EmailValidator::RFC5322_IPV6_COLONSTRT;
        }
        
        if this->lexer->isNextToken(EmailLexer::S_IPV6TAG) {
            let lexer =  clone this->lexer;
            lexer->moveNext();
            
            if lexer->isNextToken(EmailLexer::S_DOUBLECOLON) {
                let this->warnings[] = EmailValidator::RFC5322_IPV6_COLONSTRT;
            }
        }
        
        return this->doParseDomainLiteral();
    }
    
    protected function doParseDomainLiteral()
    {
        var IPv6TAG, addressLiteral, tmpArray660bd629014c38ffb14ea5f4192457b6, tmpArray9934a9b75899ddf8df794ea12f1b354a;
    
        let IPv6TAG =  false;
        let addressLiteral = "";
        do {
            
            if this->lexer->token["type"] === EmailLexer::C_NUL {
                throw new \InvalidArgumentException("ERR_EXPECTING_DTEXT");
            }
            
            if this->lexer->token["type"] === EmailLexer::INVALID || this->lexer->token["type"] === EmailLexer::C_DEL || this->lexer->token["type"] === EmailLexer::S_LF {
                let this->warnings[] = EmailValidator::RFC5322_DOMLIT_OBSDTEXT;
            }
            let tmpArray660bd629014c38ffb14ea5f4192457b6 = [EmailLexer::S_OPENQBRACKET, EmailLexer::S_OPENBRACKET];
            if this->lexer->isNextTokenAny(tmpArray660bd629014c38ffb14ea5f4192457b6) {
                throw new \InvalidArgumentException("ERR_EXPECTING_DTEXT");
            }
            let tmpArray9934a9b75899ddf8df794ea12f1b354a = [EmailLexer::S_HTAB, EmailLexer::S_SP, this->lexer->token["type"] === EmailLexer::CRLF];
            if this->lexer->isNextTokenAny(tmpArray9934a9b75899ddf8df794ea12f1b354a) {
                let this->warnings[] = EmailValidator::CFWS_FWS;
                this->parseFWS();
            }
            
            if this->lexer->isNextToken(EmailLexer::S_CR) {
                throw new \InvalidArgumentException("ERR_CR_NO_LF");
            }
            
            if this->lexer->token["type"] === EmailLexer::S_BACKSLASH {
                let this->warnings[] = EmailValidator::RFC5322_DOMLIT_OBSDTEXT;
                let addressLiteral .= this->lexer->token["value"];
                this->lexer->moveNext();
                this->validateQuotedPair();
            }
            
            if this->lexer->token["type"] === EmailLexer::S_IPV6TAG {
                let IPv6TAG =  true;
            }
            
            if this->lexer->token["type"] === EmailLexer::S_CLOSEQBRACKET {
                break;
            }
            let addressLiteral .= this->lexer->token["value"];
        } while (this->lexer->moveNext());
        let addressLiteral =  str_replace("[", "", addressLiteral);
        let addressLiteral =  this->checkIPV4Tag(addressLiteral);
        
        if addressLiteral === false {
            
            return addressLiteral;
        }
        
        if !IPv6TAG {
            let this->warnings[] = EmailValidator::RFC5322_DOMAINLITERAL;
            
            return addressLiteral;
        }
        let this->warnings[] = EmailValidator::RFC5321_ADDRESSLITERAL;
        this->checkIPV6Tag(addressLiteral);
        
        return addressLiteral;
    }
    
    protected function checkIPV4Tag(addressLiteral)
    {
        var matchesIP, index;
    
        
        let matchesIP =  [];
        // Extract IPv4 part from the end of the address-literal (if there is one)
        
        if preg_match("/\\b(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)$/", addressLiteral, matchesIP) > 0 {
            let index =  strrpos(addressLiteral, matchesIP[0]);
            
            if index === 0 {
                let this->warnings[] = EmailValidator::RFC5321_ADDRESSLITERAL;
                
                return false;
            }
            // Convert IPv4 part to IPv6 format for further testing
            let addressLiteral =  substr(addressLiteral, 0, index) . "0:0";
        }
        
        return addressLiteral;
    }
    
    protected function checkDomainPartExceptions(prev) -> void
    {
        var invalidDomainTokens;
    
        
        let invalidDomainTokens =  [EmailLexer::S_DQUOTE : true, EmailLexer::S_SEMICOLON : true, EmailLexer::S_GREATERTHAN : true, EmailLexer::S_LOWERTHAN : true];
        
        if isset var tmpArray;
        this->lexer->token["type"];
        invalidDomainTokens[this->lexer->token] {
            throw new \InvalidArgumentException("ERR_EXPECTING_ATEXT");
        }
        
        if this->lexer->token["type"] === EmailLexer::S_COMMA {
            throw new \InvalidArgumentException("ERR_COMMA_IN_DOMAIN");
        }
        
        if this->lexer->token["type"] === EmailLexer::S_AT {
            throw new \InvalidArgumentException("ERR_CONSECUTIVEATS");
        }
        
        if this->lexer->token["type"] === EmailLexer::S_OPENQBRACKET && prev["type"] !== EmailLexer::S_AT {
            throw new \InvalidArgumentException("ERR_EXPECTING_ATEXT");
        }
        
        if this->lexer->token["type"] === EmailLexer::S_HYPHEN && this->lexer->isNextToken(EmailLexer::S_DOT) {
            throw new \InvalidArgumentException("ERR_DOMAINHYPHENEND");
        }
        
        if this->lexer->token["type"] === EmailLexer::S_BACKSLASH && this->lexer->isNextToken(EmailLexer::GENERIC) {
            throw new \InvalidArgumentException("ERR_EXPECTING_ATEXT");
        }
    }
    
    protected function hasBrackets()
    {
        var e;
    
        
        if this->lexer->token["type"] !== EmailLexer::S_OPENBRACKET {
            
            return false;
        }
        try {
            this->lexer->find(EmailLexer::S_CLOSEBRACKET);
        } catch \RuntimeException, e {
            throw new \InvalidArgumentException("ERR_EXPECTING_DOMLIT_CLOSE");
        }
        
        return true;
    }
    
    protected function checkLabelLength(prev) -> void
    {
        
        if this->lexer->token["type"] === EmailLexer::S_DOT && prev["type"] === EmailLexer::GENERIC && strlen(prev["value"]) > 63 {
            let this->warnings[] = EmailValidator::RFC5322_LABEL_TOOLONG;
        }
    }
    
    protected function parseDomainComments() -> void
    {
        this->isUnclosedComment();
        
        while (!this->lexer->isNextToken(EmailLexer::S_CLOSEPARENTHESIS)) {
            this->warnEscaping();
            this->lexer->moveNext();
        
        }
        this->lexer->moveNext();
        
        if this->lexer->isNextToken(EmailLexer::S_DOT) {
            throw new \InvalidArgumentException("ERR_EXPECTING_ATEXT");
        }
    }

}