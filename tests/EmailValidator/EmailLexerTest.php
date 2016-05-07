<?php

namespace Egulias\EmailValidator\Tests;

use Egulias\EmailValidator\EmailLexer;

class EmailLexerTests extends \PHPUnit_Framework_TestCase
{

    public function testLexerExtendsLib()
    {
        $lexer = new EmailLexer();
        $this->assertInstanceOf('Doctrine\Common\Lexer\AbstractLexer', $lexer);
    }

    /**
     *  @dataProvider getTokens
     *
     */
    public function testLexerTokens($str, $token)
    {
        $lexer = new EmailLexer();
        $lexer->setInput($str);
        $lexer->moveNext();
        $lexer->moveNext();
        $this->assertEquals($token, $lexer->token['type']);
    }

    public function testLexerParsesMultipleSpaces()
    {
        $lexer = new EmailLexer();
        $lexer->setInput('  ');
        $lexer->moveNext();
        $lexer->moveNext();
        $this->assertEquals(EmailLexer::S_SP, $lexer->token['type']);
        $lexer->moveNext();
        $this->assertEquals(EmailLexer::S_SP, $lexer->token['type']);
    }

    /**
     * @dataProvider invalidUTF8CharsProvider
     */
    public function testLexerParsesInvalidUTF8($char)
    {
        $lexer = new EmailLexer();
        $lexer->setInput($char);
        $lexer->moveNext();
        $lexer->moveNext();

        $this->assertEquals(EmailLexer::INVALID, $lexer->token['type']);
    }

    public function invalidUTF8CharsProvider()
    {
        $chars = array();
        for ($i = 0; $i < 0x100; ++$i) {
            $c = $this->utf8Chr($i);
            if (preg_match('/(?=\p{Cc})(?=[^\t\n\n\r])/u', $c) && !preg_match('/\x{0000}/u', $c)) {
                $chars[] = array($c);
            }
        }

        return $chars;
    }

    protected function utf8Chr($code_point)
    {

        if ($code_point < 0 || 0x10FFFF < $code_point || (0xD800 <= $code_point && $code_point <= 0xDFFF)) {
            return '';
        }

        if ($code_point < 0x80) {
            $hex[0] = $code_point;
            $ret = chr($hex[0]);
        } elseif ($code_point < 0x800) {
            $hex[0] = 0x1C0 | $code_point >> 6;
            $hex[1] = 0x80  | $code_point & 0x3F;
            $ret = chr($hex[0]).chr($hex[1]);
        } elseif ($code_point < 0x10000) {
            $hex[0] = 0xE0 | $code_point >> 12;
            $hex[1] = 0x80 | $code_point >> 6 & 0x3F;
            $hex[2] = 0x80 | $code_point & 0x3F;
            $ret = chr($hex[0]).chr($hex[1]).chr($hex[2]);
        } else {
            $hex[0] = 0xF0 | $code_point >> 18;
            $hex[1] = 0x80 | $code_point >> 12 & 0x3F;
            $hex[2] = 0x80 | $code_point >> 6 & 0x3F;
            $hex[3] = 0x80 | $code_point  & 0x3F;
            $ret = chr($hex[0]).chr($hex[1]).chr($hex[2]).chr($hex[3]);
        }

        return $ret;
    }

    public function testLexerForTab()
    {
        $lexer = new EmailLexer();
        $lexer->setInput("foo\tbar");
        $lexer->moveNext();
        $lexer->skipUntil(EmailLexer::S_HTAB);
        $lexer->moveNext();
        $this->assertEquals(EmailLexer::S_HTAB, $lexer->token['type']);
    }

    public function testLexerForUTF8()
    {
        $lexer = new EmailLexer();
        $lexer->setInput("áÇ@bar.com");
        $lexer->moveNext();
        $lexer->moveNext();
        $this->assertEquals(EmailLexer::GENERIC, $lexer->token['type']);
        $lexer->moveNext();
        $this->assertEquals(EmailLexer::GENERIC, $lexer->token['type']);
    }

    public function testLexerSearchToken()
    {
        $lexer = new EmailLexer();
        $lexer->setInput("foo\tbar");
        $lexer->moveNext();
        $this->assertTrue($lexer->find(EmailLexer::S_HTAB));
    }

    public function getTokens()
    {
        return array(
            array("foo", EmailLexer::GENERIC),
            array("\r", EmailLexer::S_CR),
            array("\t", EmailLexer::S_HTAB),
            array("\r\n", EmailLexer::CRLF),
            array("\n", EmailLexer::S_LF),
            array(" ", EmailLexer::S_SP),
            array("@", EmailLexer::S_AT),
            array("IPv6", EmailLexer::S_IPV6TAG),
            array("::", EmailLexer::S_DOUBLECOLON),
            array(":", EmailLexer::S_COLON),
            array(".", EmailLexer::S_DOT),
            array("\"", EmailLexer::S_DQUOTE),
            array("-", EmailLexer::S_HYPHEN),
            array("\\", EmailLexer::S_BACKSLASH),
            array("/", EmailLexer::S_SLASH),
            array("(", EmailLexer::S_OPENPARENTHESIS),
            array(")", EmailLexer::S_CLOSEPARENTHESIS),
            array('<', EmailLexer::S_LOWERTHAN),
            array('>', EmailLexer::S_GREATERTHAN),
            array('[', EmailLexer::S_OPENBRACKET),
            array(']', EmailLexer::S_CLOSEBRACKET),
            array(';', EmailLexer::S_SEMICOLON),
            array(',', EmailLexer::S_COMMA),
            array('<', EmailLexer::S_LOWERTHAN),
            array('>', EmailLexer::S_GREATERTHAN),
            array('{', EmailLexer::S_OPENQBRACKET),
            array('}', EmailLexer::S_CLOSEQBRACKET),
            array('',  EmailLexer::S_EMPTY),
            array(chr(31),  EmailLexer::INVALID),
            array(chr(226),  EmailLexer::GENERIC),
            array(chr(0),  EmailLexer::C_NUL)
        );
    }
}
