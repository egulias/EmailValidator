<?php

namespace Egulias\Tests\EmailValidator;

use Egulias\EmailValidator\EmailParser;

class EmailParserTests extends \PHPUnit_Framework_TestCase
{
    public function testParserExtendsLib()
    {
        $mock = $this->getMock('egulias\EmailValidator\EmailLexer');
        $parser = new EmailParser($mock);
        $this->markTestIncomplete();
    }
}
