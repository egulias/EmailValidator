namespace Egulias\EmailValidator\Parser;

use Egulias\EmailValidator\EmailLexer;
use Egulias\EmailValidator\EmailValidator;
use InvalidArgumentException;
class LocalPart extends Parser
{
    public function parse(localPart) -> void
    {
        var parseDQuote, closingQuote, prev;
    
        let parseDQuote =  true;
        let closingQuote =  false;
        
        while (this->lexer->token["type"] !== EmailLexer::S_AT && this->lexer->token) {
            
            if this->lexer->token["type"] === EmailLexer::S_DOT && !this->lexer->getPrevious() {
                throw new \InvalidArgumentException("ERR_DOT_START");
            }
            let closingQuote =  this->checkDQUOTE(closingQuote);
            
            if closingQuote && parseDQuote {
                let parseDQuote =  this->parseDoubleQuote();
            }
            
            if this->lexer->token["type"] === EmailLexer::S_OPENPARENTHESIS {
                this->parseComments();
            }
            this->checkConsecutiveDots();
            
            if this->lexer->token["type"] === EmailLexer::S_DOT && this->lexer->isNextToken(EmailLexer::S_AT) {
                throw new \InvalidArgumentException("ERR_DOT_END");
            }
            this->warnEscaping();
            this->isInvalidToken(this->lexer->token, closingQuote);
            
            if this->isFWS() {
                this->parseFWS();
            }
            this->lexer->moveNext();
        
        }
        let prev =  this->lexer->getPrevious();
        
        if strlen(prev["value"]) > EmailValidator::RFC5322_LOCAL_TOOLONG {
            let this->warnings[] = EmailValidator::RFC5322_LOCAL_TOOLONG;
        }
    }
    
    protected function parseDoubleQuote()
    {
        var parseAgain, special, invalid, setSpecialsWarning, prev;
    
        let parseAgain =  true;
        
        let special =  [EmailLexer::S_CR : true, EmailLexer::S_HTAB : true, EmailLexer::S_LF : true];
        
        let invalid =  [EmailLexer::C_NUL : true, EmailLexer::S_HTAB : true, EmailLexer::S_CR : true, EmailLexer::S_LF : true];
        let setSpecialsWarning =  true;
        this->lexer->moveNext();
        
        while (this->lexer->token["type"] !== EmailLexer::S_DQUOTE && this->lexer->token) {
            let parseAgain =  false;
            
            if isset var tmpArray;
            this->lexer->token["type"];
            special[this->lexer->token] && setSpecialsWarning {
                let this->warnings[] = EmailValidator::CFWS_FWS;
                let setSpecialsWarning =  false;
            }
            this->lexer->moveNext();
            
            if !this->escaped() && isset var tmpArray;
            this->lexer->token["type"];
            invalid[this->lexer->token] {
                throw new InvalidArgumentException("ERR_EXPECTED_ATEXT");
            }
        
        }
        let prev =  this->lexer->getPrevious();
        
        if prev["type"] === EmailLexer::S_BACKSLASH {
            
            if !this->checkDQUOTE(false) {
                throw new \InvalidArgumentException("ERR_UNCLOSED_DQUOTE");
            }
        }
        
        if !this->lexer->isNextToken(EmailLexer::S_AT) && prev["type"] !== EmailLexer::S_BACKSLASH {
            throw new \InvalidArgumentException("ERR_EXPECED_AT");
        }
        
        return parseAgain;
    }
    
    protected function isInvalidToken(token, closingQuote) -> void
    {
        var forbidden;
    
        
        let forbidden =  [EmailLexer::S_COMMA, EmailLexer::S_CLOSEBRACKET, EmailLexer::S_OPENBRACKET, EmailLexer::S_GREATERTHAN, EmailLexer::S_LOWERTHAN, EmailLexer::S_COLON, EmailLexer::S_SEMICOLON, EmailLexer::INVALID];
        
        if in_array(token["type"], forbidden) && !closingQuote {
            throw new \InvalidArgumentException("ERR_EXPECTING_ATEXT");
        }
    }

}