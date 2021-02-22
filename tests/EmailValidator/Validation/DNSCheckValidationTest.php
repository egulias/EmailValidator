<?php

namespace Egulias\EmailValidator\Tests\EmailValidator\Validation;

use Egulias\EmailValidator\EmailLexer;
use Egulias\EmailValidator\Result\InvalidEmail;
use Egulias\EmailValidator\Result\Reason\DomainAcceptsNoMail;
use Egulias\EmailValidator\Result\Reason\LocalOrReservedDomain;
use Egulias\EmailValidator\Result\Reason\NoDNSRecord;
use Egulias\EmailValidator\Result\Reason\UnableToGetDNSRecord;
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

            // unicide
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
        $expectedError = new InvalidEmail(new LocalOrReservedDomain(), $localOrReservedEmails);
        $validation->isValid($localOrReservedEmails, new EmailLexer());
        $this->assertEquals($expectedError, $validation->getError());
    }

    public function testDomainAcceptsNoMailError()
    {
        $validation = new DNSCheckValidation();
        $expectedError = new InvalidEmail(new DomainAcceptsNoMail(), "");
        $isValidResult = $validation->isValid("nullmx@example.com", new EmailLexer());
        $this->assertEquals($expectedError, $validation->getError());
        $this->assertFalse($isValidResult);
    }

    public function testDNSWarnings()
    {
        $this->markTestSkipped('Need to found a domain with AAAA records and no MX that fails later in the validations');
        $validation = new DNSCheckValidation();
        $expectedWarnings = [NoDNSMXRecord::CODE => new NoDNSMXRecord()];
        $validation->isValid("example@invalid.example.com", new EmailLexer());
        $this->assertEquals($expectedWarnings, $validation->getWarnings());
    }

    public function testNoDNSError()
    {
        $validation = new DNSCheckValidation();
        $expectedError = new InvalidEmail(new NoDNSRecord(), '');
        $validation->isValid("example@invalid.example.com", new EmailLexer());
        $this->assertEquals($expectedError, $validation->getError());
    }

    /**
     * @group slow
     */
    public function testUnableToGetDNSRecord()
    {
        error_reporting(\E_ALL);

        // UnableToGetDNSRecord raises on network errors (e.g. timeout) that we can‘t emulate in tests (for sure),
        // but we can try to get timeout error by trying to fetch all DNS records
        $validation = new class extends DNSCheckValidation {
            protected const DNS_RECORD_TYPES_TO_CHECK = \DNS_ALL;
        };
        $expectedError = new InvalidEmail(new UnableToGetDNSRecord(), '');

        $validation->isValid('example@invalid.example.com', new EmailLexer());
        $this->assertEquals($expectedError, $validation->getError());
    }
}
