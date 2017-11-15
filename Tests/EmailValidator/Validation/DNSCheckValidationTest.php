<?php

namespace Egulias\Tests\EmailValidator\Validation;

use Egulias\EmailValidator\EmailLexer;
use Egulias\EmailValidator\Exception\NoDNSRecord;
use Egulias\EmailValidator\Validation\DNSCheckValidation;
use Egulias\EmailValidator\Warning\NoDNSMXRecord;
use PHPUnit\Framework\TestCase;

class DNSCheckValidationTest extends TestCase
{
    public function validEmailsProvider()
    {
        return [
            // dot-atom
            ['Abc@example.com'],
            ['ABC@EXAMPLE.COM'],
            ['Abc.123@example.com'],
            ['user+mailbox/department=shipping@example.com'],
            ['!#$%&\'*+-/=?^_`.{|}~@example.com'],

            // quoted string
            ['"Abc@def"@example.com'],
            ['"Fred\ Bloggs"@example.com'],
            ['"Joe.\\Blow"@example.com'],
        ];
    }

    /**
     * @dataProvider validEmailsProvider
     */
    public function testValidDNS($validEmail)
    {
        $validation = new DNSCheckValidation();
        $this->assertTrue($validation->isValid($validEmail, new EmailLexer()));
    }

    public function testInvalidDNS()
    {
        $validation = new DNSCheckValidation();
        $this->assertFalse($validation->isValid("example@invalid.example.com", new EmailLexer()));
    }

    public function testDNSWarnings()
    {
        $validation = new DNSCheckValidation();
        $expectedWarnings = [NoDNSMXRecord::CODE => new NoDNSMXRecord()];
        $validation->isValid("example@invalid.example.com", new EmailLexer());
        $this->assertEquals($expectedWarnings, $validation->getWarnings());
    }

    public function testNoDNSError()
    {
        $validation = new DNSCheckValidation();
        $expectedError = new NoDNSRecord();
        $validation->isValid("example@invalid.example.com", new EmailLexer());
        $this->assertEquals($expectedError, $validation->getError());
    }
}
