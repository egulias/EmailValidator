<?php

namespace egulias\Tests\EmailValidator;

use egulias\EmailValidator\EmailParser;

class EmailParserTests extends \PHPUnit_Framework_TestCase
{
    public function testParserExtendsLib()
    {
        $mock = $this->getMock('egulias\EmailValidator\EmailLexer');
        $parser = new EmailParser($mock);
        $this->assertInstanceOf('JMS\Parser\AbstractParser', $parser);
    }
}
