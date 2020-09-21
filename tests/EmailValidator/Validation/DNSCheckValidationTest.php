<?php

namespace Egulias\EmailValidator\Tests\EmailValidator\Validation;

use Egulias\EmailValidator\EmailLexer;
use Egulias\EmailValidator\Exception\NoDNSRecord;
use Egulias\EmailValidator\Exception\LocalOrReservedDomain;
use Egulias\EmailValidator\Exception\DomainAcceptsNoMail;
use Egulias\EmailValidator\Validation\DNSCheckValidation;
use Egulias\EmailValidator\Warning\NoDNSMXRecord;
use PHPUnit\Framework\TestCase;

class DNSCheckValidationTest extends TestCase
{
    public function validEmailsProvider()
    {
        return [
            // dot-atom
            ['Abc@ietf.org'],
            ['ABC@ietf.org'],
            ['Abc.123@ietf.org'],
            ['user+mailbox/department=shipping@ietf.org'],
            ['!#$%&\'*+-/=?^_`.{|}~@ietf.org'],

            // quoted string
            ['"Abc@def"@ietf.org'],
            ['"Fred\ Bloggs"@ietf.org'],
            ['"Joe.\\Blow"@ietf.org'],

            // unicode
            ['ñandu.cl'],
        ];
    }

    public function localOrReservedEmailsProvider()
    {
        return [
            // Reserved Top Level DNS Names
            ['test'],
            ['example'],
            ['invalid'],
            ['localhost'],

            // mDNS
            ['local'],

            // Private DNS Namespaces
            ['intranet'],
            ['internal'],
            ['private'],
            ['corp'],
            ['home'],
            ['lan'],
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

    /**
     * @dataProvider localOrReservedEmailsProvider
     */
    public function testLocalOrReservedDomainError($localOrReservedEmails)
    {
        $validation = new DNSCheckValidation();
        $expectedError = new LocalOrReservedDomain();
        $validation->isValid($localOrReservedEmails, new EmailLexer());
        $this->assertEquals($expectedError, $validation->getError());
    }

    public function testDomainAcceptsNoMailError()
    {
        $validation = new DNSCheckValidation();
        $expectedError = new DomainAcceptsNoMail();
        $isValidResult = $validation->isValid("nullmx@example.com", new EmailLexer());
        $this->assertEquals($expectedError, $validation->getError());
        $this->assertFalse($isValidResult);
    }

    /*
    public function testDNSWarnings()
    {
        $validation = new DNSCheckValidation();
        $expectedWarnings = [NoDNSMXRecord::CODE => new NoDNSMXRecord()];
        $validation->isValid("example@invalid.example.com", new EmailLexer());
        $this->assertEquals($expectedWarnings, $validation->getWarnings());
    }
    */

    public function testNoDNSError()
    {
        $validation = new DNSCheckValidation();
        $expectedError = new NoDNSRecord();
        $validation->isValid("example@invalid.example.com", new EmailLexer());
        $this->assertEquals($expectedError, $validation->getError());
    }
}
