<?php

namespace Egulias\EmailValidator;

use Doctrine\Common\Lexer\AbstractLexer;

class EmailLexer extends AbstractLexer
{
    //ASCII values
    const C_DEL              = 127;
    const C_NUL              = 0;
    const S_AT               = 64;
    const S_BACKSLASH        = 92;
    const S_DOT              = 46;
    const S_DQUOTE           = 34;
    const S_OPENPARENTHESIS  = 49;
    const S_CLOSEPARENTHESIS = 261;
    const S_OPENBRACKET      = 262;
    const S_CLOSEBRACKET     = 263;
    const S_HYPHEN           = 264;
    const S_COLON            = 265;
    const S_DOUBLECOLON      = 266;
    const S_SP               = 267;
    const S_HTAB             = 268;
    const S_CR               = 269;
    const S_LF               = 270;
    const S_IPV6TAG          = 271;
    const S_LOWERTHAN        = 272;
    const S_GREATERTHAN      = 273;
    const S_COMMA            = 274;
    const S_SEMICOLON        = 275;
    const S_OPENQBRACKET     = 276;
    const S_CLOSEQBRACKET    = 277;
    const S_SLASH            = 278;
    const S_EMPTY            = null;
    const GENERIC            = 300;
    const CRLF               = 301;
    const INVALID            = 302;
    const ASCII_INVALID_FROM = 127;
    const ASCII_INVALID_TO   = 199;

    /**
     * US-ASCII visible characters not valid for atext (@link http://tools.ietf.org/html/rfc5322#section-3.2.3)
     *
     * @var array
     */
    protected $charValue = array(
        '('    => self::S_OPENPARENTHESIS,
        ')'    => self::S_CLOSEPARENTHESIS,
        '<'    => self::S_LOWERTHAN,
        '>'    => self::S_GREATERTHAN,
        '['    => self::S_OPENBRACKET,
        ']'    => self::S_CLOSEBRACKET,
        ':'    => self::S_COLON,
        ';'    => self::S_SEMICOLON,
        '@'    => self::S_AT,
        '\\'   => self::S_BACKSLASH,
        '/'    => self::S_SLASH,
        ','    => self::S_COMMA,
        '.'    => self::S_DOT,
        '"'    => self::S_DQUOTE,
        '-'    => self::S_HYPHEN,
        '::'   => self::S_DOUBLECOLON,
        ' '    => self::S_SP,
        "\t"   => self::S_HTAB,
        "\r"   => self::S_CR,
        "\n"   => self::S_LF,
        "\r\n" => self::CRLF,
        'IPv6' => self::S_IPV6TAG,
        '<'    => self::S_LOWERTHAN,
        '>'    => self::S_GREATERTHAN,
        '{'    => self::S_OPENQBRACKET,
        '}'    => self::S_CLOSEQBRACKET,
        ''     => self::S_EMPTY,
        '\0'   => self::C_NUL,
    );

    protected $hasInvalidTokens = false;

    protected $previous;

    public function reset()
    {
        $this->hasInvalidTokens = false;
        parent::reset();
    }

    public function hasInvalidTokens()
    {
        return $this->hasInvalidTokens;
    }

    /**
     * @param $type
     * @throws \UnexpectedValueException
     * @return boolean
     */
    public function find($type)
    {
        $search = clone $this;
        $search->skipUntil($type);

        if (!$search->lookahead) {
            throw new \UnexpectedValueException($type . ' not found');
        }
        return true;
    }

    /**
     * getPrevious
     *
     * @return array token
     */
    public function getPrevious()
    {
        return $this->previous;
    }

    /**
     * moveNext
     *
     * @return boolean
     */
    public function moveNext()
    {
        $this->previous = $this->token;

        return parent::moveNext();
    }

    /**
     * Lexical catchable patterns.
     *
     * @return string[]
     */
    protected function getCatchablePatterns()
    {
        return array(
            '[a-zA-Z_]+[46]?', //ASCII and domain literal
            '[^\x00-\x7F]',  //UTF-8
            '[0-9]+',
            '\r\n',
            '::',
            '\s+?',
            '.',
            );
    }

    /**
     * Lexical non-catchable patterns.
     *
     * @return string[]
     */
    protected function getNonCatchablePatterns()
    {
        return array('[\xA0-\xff]+');
    }

    /**
     * Retrieve token type. Also processes the token value if necessary.
     *
     * @param string $value
     * @throws \InvalidArgumentException
     * @return integer
     */
    protected function getType(&$value)
    {
        if ($this->isNullType($value)) {
            return self::C_NUL;
        }

        if ($this->isValid($value)) {
            return $this->charValue[$value];
        }

        if ($this->isUTF8Invalid($value)) {
            $this->hasInvalidTokens = true;
            return self::INVALID;
        }

        return  self::GENERIC;
    }

    protected function isValid($value)
    {
        if (isset($this->charValue[$value])) {
            return true;
        }

        return false;
    }

    /**
     * @param $value
     * @return bool
     */
    protected function isNullType($value)
    {
        if ($value === "\0") {
            return true;
        }

        return false;
    }

    /**
     * @param $value
     * @return bool
     */
    protected function isUTF8Invalid($value)
    {
        if (preg_match('/\p{Cc}+/u', $value)) {
            return true;
        }

        return false;
    }

    protected function getModifiers()
    {
        return 'iu';
    }
}
