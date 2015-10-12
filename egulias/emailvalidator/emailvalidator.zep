namespace Egulias\EmailValidator;

/**
 * EmailValidator
 *
 * @author Eduardo Gulias Davis <me@egulias.com>
 */
class EmailValidator
{
    const ERR_CONSECUTIVEATS = 128;
    const ERR_EXPECTING_DTEXT = 129;
    const ERR_NOLOCALPART = 130;
    const ERR_NODOMAIN = 131;
    const ERR_CONSECUTIVEDOTS = 132;
    const ERR_ATEXT_AFTER_CFWS = 133;
    const ERR_ATEXT_AFTER_QS = 134;
    const ERR_ATEXT_AFTER_DOMLIT = 135;
    const ERR_EXPECTING_QPAIR = 136;
    const ERR_EXPECTING_ATEXT = 137;
    const ERR_EXPECTING_QTEXT = 138;
    const ERR_EXPECTING_CTEXT = 139;
    const ERR_BACKSLASHEND = 140;
    const ERR_DOT_START = 141;
    const ERR_DOT_END = 142;
    const ERR_DOMAINHYPHENSTART = 143;
    const ERR_DOMAINHYPHENEND = 144;
    const ERR_UNCLOSEDQUOTEDSTR = 145;
    const ERR_UNCLOSEDCOMMENT = 146;
    const ERR_UNCLOSEDDOMLIT = 147;
    const ERR_FWS_CRLF_X2 = 148;
    const ERR_FWS_CRLF_END = 149;
    const ERR_CR_NO_LF = 150;
    const ERR_DEPREC_REACHED = 151;
    const RFC5321_TLD = 9;
    const RFC5321_TLDNUMERIC = 10;
    const RFC5321_QUOTEDSTRING = 11;
    const RFC5321_ADDRESSLITERAL = 12;
    const RFC5321_IPV6DEPRECATED = 13;
    const CFWS_COMMENT = 17;
    const CFWS_FWS = 18;
    const DEPREC_LOCALPART = 33;
    const DEPREC_FWS = 34;
    const DEPREC_QTEXT = 35;
    const DEPREC_QP = 36;
    const DEPREC_COMMENT = 37;
    const DEPREC_CTEXT = 38;
    const DEPREC_CFWS_NEAR_AT = 49;
    const RFC5322_LOCAL_TOOLONG = 64;
    const RFC5322_LABEL_TOOLONG = 63;
    const RFC5322_DOMAIN = 65;
    const RFC5322_TOOLONG = 66;
    const RFC5322_DOMAIN_TOOLONG = 255;
    const RFC5322_DOMAINLITERAL = 70;
    const RFC5322_DOMLIT_OBSDTEXT = 71;
    const RFC5322_IPV6_GRPCOUNT = 72;
    const RFC5322_IPV6_2X2XCOLON = 73;
    const RFC5322_IPV6_BADCHAR = 74;
    const RFC5322_IPV6_MAXGRPS = 75;
    const RFC5322_IPV6_COLONSTRT = 76;
    const RFC5322_IPV6_COLONEND = 77;
    const DNSWARN_NO_MX_RECORD = 5;
    const DNSWARN_NO_RECORD = 6;
    protected parser;
    protected warnings = [];
    protected error;
    protected threshold = 255;
    public function __construct() -> void
    {
        let this->parser =  new EmailParser(new EmailLexer());
    }
    
    public function isValid(email, checkDNS = false, strict = false)
    {
        var e, rClass, dns, error;
    
        try {
            this->parser->parse((string) email);
            let this->warnings =  this->parser->getWarnings();
        } catch \Exception, e {
            let rClass =  new \ReflectionClass(this);
            let this->error =  rClass->getConstant(e->getMessage());
            
            return false;
        }
        let dns =  true;
        
        if checkDNS {
            let dns =  this->checkDNS();
        }
        
        if this->hasWarnings() && (int) max(this->warnings) > this->threshold {
            let this->error =  self::ERR_DEPREC_REACHED;
            
            return false;
        }
        
        return !strict || !this->hasWarnings() && dns;
    }
    
    /**
     * @return boolean
     */
    public function hasWarnings() -> boolean
    {
        
        return !empty(this->warnings);
    }
    
    /**
     * @return array
     */
    public function getWarnings() -> array
    {
        
        return this->warnings;
    }
    
    /**
     * @return string
     */
    public function getError() -> string
    {
        
        return this->error;
    }
    
    /**
     * @param int $threshold
     *
     * @return EmailValidator
     */
    public function setThreshold(int threshold)
    {
        let this->threshold =  (int) threshold;
        
        return this;
    }
    
    /**
     * @return int
     */
    public function getThreshold() -> int
    {
        
        return this->threshold;
    }
    
    protected function checkDNS()
    {
        var checked, result;
    
        let checked =  true;
        let result =  checkdnsrr(trim(this->parser->getParsedDomainPart()), "MX");
        
        if !result {
            let this->warnings[] = self::DNSWARN_NO_RECORD;
            let checked =  false;
            this->addTLDWarnings();
        }
        
        return checked;
    }
    
    protected function addTLDWarnings() -> void
    {
        
        if !in_array(self::DNSWARN_NO_RECORD, this->warnings) && !in_array(self::DNSWARN_NO_MX_RECORD, this->warnings) && in_array(self::RFC5322_DOMAINLITERAL, this->warnings) {
            let this->warnings[] = self::RFC5321_TLD;
        }
    }

}