<?php

namespace Egulias\Tests\EmailValidator\Validation;

use Egulias\EmailValidator\EmailLexer;
use Egulias\EmailValidator\Exception\NoDNSRecord;
use Egulias\EmailValidator\Validation\DNSCheckValidation;
use Egulias\EmailValidator\Warning\NoDNSMXRecord;

class DNSCheckValidationTest extends \PHPUnit_Framework_TestCase
{
    public function testValidDNS()
    {
        $validation = new DNSCheckValidation();
        $this->assertTrue($validation->isValid("example@example.com", new EmailLexer()));
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
