<?php

namespace Egulias\EmailValidator\Tests\EmailValidator;

use Egulias\EmailValidator\EmailLexer;
use Egulias\EmailValidator\EmailParser;
use PHPUnit\Framework\TestCase;

class EmailParserTest extends TestCase
{
    public static function emailPartsProvider()
    {
        return [
            ['test@foo.com', 'test', 'foo.com'],
            ['"user@name"@example.com', '"user@name"', 'example.com'],
            ['validipv6@[IPv6:2001:db8:1ff::a0b:dbd0]', 'validipv6', '[IPv6:2001:db8:1ff::a0b:dbd0]'],
            ['validipv4@[127.0.0.0]', 'validipv4', '[127.0.0.0]']
        ];
    }
    /**
     * @dataProvider emailPartsProvider
     */
    public function testGetParts($email, $local, $domain)
    {
        $parser = new EmailParser(new EmailLexer());
        $parser->parse($email);

        $this->assertEquals($local, $parser->getLocalPart());
        $this->assertEquals($domain, $parser->getDomainPart());
    }
}
