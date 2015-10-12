namespace Egulias\EmailValidator\Parser;

use Egulias\EmailValidator\EmailLexer;
use Egulias\EmailValidator\EmailValidator;
abstract class Parser
{
    protected warnings = [];
    protected lexer;
    public function __construct(<EmailLexer> lexer) -> void
    {
        let this->lexer = lexer;
    }
    
    public function getWarnings()
    {
        
        return this->warnings;
    }
    
    abstract function parse(str) -> void;
    
    /**
     * validateQuotedPair
     */
    protected function validateQuotedPair() -> void
    {
        
        if !(this->lexer->token["type"] === EmailLexer::INVALID || this->lexer->token["type"] === EmailLexer::C_DEL) {
            throw new \InvalidArgumentException("ERR_EXPECTING_QPAIR");
        }
        let this->warnings[] = EmailValidator::DEPREC_QP;
    }
    
    /**
     * @return string the the comment
     * @throws \InvalidArgumentException
     */
    protected function parseComments() -> string
    {
        var tmpArray63f0c3c6be7adf1793a82cc46795d34e;
    
        this->isUnclosedComment();
        let this->warnings[] = EmailValidator::CFWS_COMMENT;
        
        while (!this->lexer->isNextToken(EmailLexer::S_CLOSEPARENTHESIS)) {
            this->warnEscaping();
            this->lexer->moveNext();
        
        }
        this->lexer->moveNext();
        let tmpArray63f0c3c6be7adf1793a82cc46795d34e = [EmailLexer::GENERIC, EmailLexer::S_EMPTY];
        if this->lexer->isNextTokenAny(tmpArray63f0c3c6be7adf1793a82cc46795d34e) {
            throw new \InvalidArgumentException("ERR_EXPECTING_ATEXT");
        }
        
        if this->lexer->isNextToken(EmailLexer::S_AT) {
            let this->warnings[] = EmailValidator::DEPREC_CFWS_NEAR_AT;
        }
    }
    
    protected function isUnclosedComment()
    {
        var e;
    
        try {
            this->lexer->find(EmailLexer::S_CLOSEPARENTHESIS);
            
            return true;
        } catch \RuntimeException, e {
            throw new \InvalidArgumentException("ERR_UNCLOSEDCOMMENT");
        }
    }
    
    protected function parseFWS() -> void
    {
        var previous;
    
        let previous =  this->lexer->getPrevious();
        this->checkCRLFInFWS();
        
        if this->lexer->token["type"] === EmailLexer::S_CR {
            throw new \InvalidArgumentException("ERR_CR_NO_LF");
        }
        
        if this->lexer->isNextToken(EmailLexer::GENERIC) && previous["type"] !== EmailLexer::S_AT {
            throw new \InvalidArgumentException("ERR_ATEXT_AFTER_CFWS");
        }
        
        if this->lexer->token["type"] === EmailLexer::S_LF || this->lexer->token["type"] === EmailLexer::C_NUL {
            throw new \InvalidArgumentException("ERR_EXPECTING_CTEXT");
        }
        
        if this->lexer->isNextToken(EmailLexer::S_AT) || previous["type"] === EmailLexer::S_AT {
            let this->warnings[] = EmailValidator::DEPREC_CFWS_NEAR_AT;
        } else {
            let this->warnings[] = EmailValidator::CFWS_FWS;
        }
    }
    
    protected function checkConsecutiveDots() -> void
    {
        
        if this->lexer->token["type"] === EmailLexer::S_DOT && this->lexer->isNextToken(EmailLexer::S_DOT) {
            throw new \InvalidArgumentException("ERR_CONSECUTIVEDOTS");
        }
    }
    
    protected function isFWS()
    {
        
        if this->escaped() {
            
            return false;
        }
        
        if this->lexer->token["type"] === EmailLexer::S_SP || this->lexer->token["type"] === EmailLexer::S_HTAB || this->lexer->token["type"] === EmailLexer::S_CR || this->lexer->token["type"] === EmailLexer::S_LF || this->lexer->token["type"] === EmailLexer::CRLF {
            
            return true;
        }
        
        return false;
    }
    
    protected function escaped()
    {
        var previous;
    
        let previous =  this->lexer->getPrevious();
        
        if previous["type"] === EmailLexer::S_BACKSLASH && this->lexer->token["type"] !== EmailLexer::GENERIC {
            
            return true;
        }
        
        return false;
    }
    
    protected function warnEscaping()
    {
        var tmpArray612d23a3c73245450bf1b63fe4fddaa8;
    
        
        if this->lexer->token["type"] !== EmailLexer::S_BACKSLASH {
            
            return false;
        }
        
        if this->lexer->isNextToken(EmailLexer::GENERIC) {
            throw new \InvalidArgumentException("ERR_EXPECTING_ATEXT");
        }
        let tmpArray612d23a3c73245450bf1b63fe4fddaa8 = [EmailLexer::S_SP, EmailLexer::S_HTAB, EmailLexer::C_DEL];
        if !this->lexer->isNextTokenAny(tmpArray612d23a3c73245450bf1b63fe4fddaa8) {
            
            return false;
        }
        let this->warnings[] = EmailValidator::DEPREC_QP;
        
        return true;
    }
    
    protected function checkDQUOTE(hasClosingQuote)
    {
        var previous, e;
    
        
        if this->lexer->token["type"] !== EmailLexer::S_DQUOTE {
            
            return hasClosingQuote;
        }
        
        if hasClosingQuote {
            
            return hasClosingQuote;
        }
        let previous =  this->lexer->getPrevious();
        
        if this->lexer->isNextToken(EmailLexer::GENERIC) && previous["type"] === EmailLexer::GENERIC {
            throw new \InvalidArgumentException("ERR_EXPECTING_ATEXT");
        }
        let this->warnings[] = EmailValidator::RFC5321_QUOTEDSTRING;
        try {
            this->lexer->find(EmailLexer::S_DQUOTE);
            let hasClosingQuote =  true;
        } catch \Exception, e {
            throw new \InvalidArgumentException("ERR_UNCLOSEDQUOTEDSTR");
        }
        
        return hasClosingQuote;
    }
    
    protected function checkCRLFInFWS()
    {
        var tmpArray1ece19b1ca3512633175a37153944cbe;
    
        
        if this->lexer->token["type"] !== EmailLexer::CRLF {
            
            return;
        }
        
        if this->lexer->isNextToken(EmailLexer::CRLF) {
            throw new \InvalidArgumentException("ERR_FWS_CRLF_X2");
        }
        let tmpArray1ece19b1ca3512633175a37153944cbe = [EmailLexer::S_SP, EmailLexer::S_HTAB];
        if !this->lexer->isNextTokenAny(tmpArray1ece19b1ca3512633175a37153944cbe) {
            throw new \InvalidArgumentException("ERR_FWS_CRLF_END");
        }
    }

}