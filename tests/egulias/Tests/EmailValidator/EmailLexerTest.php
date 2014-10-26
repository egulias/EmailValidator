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

    public function testLexerForTab()
    {
        $lexer = new EmailLexer();
        $lexer->setInput("foo\tbar");
        $lexer->moveNext();
        $lexer->skipUntil(EmailLexer::S_HTAB);
        $lexer->moveNext();
        $this->assertEquals(EmailLexer::S_HTAB, $lexer->token['type']);
    }

    public function testLexerSearchToken()
    {
        $lexer = new EmailLexer();
        $lexer->setInput("foo\tbar");
        $lexer->moveNext();
        $this->assertTrue($lexer->find(EmailLexer::S_HTAB));
    }

    public function testLexerHasInvalidTokens()
    {
        $lexer = new EmailLexer();
        $lexer->setInput(chr(226));
        $lexer->moveNext();
        $lexer->moveNext();
        $this->assertTrue($lexer->hasInvalidTokens());
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
            array(chr(226),  EmailLexer::INVALID),
            array(chr(0),  EmailLexer::C_NUL)
        );
    }
}
