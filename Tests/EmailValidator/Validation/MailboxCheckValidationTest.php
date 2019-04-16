<?php

namespace Egulias\Tests\EmailValidator\Validation;

use Egulias\EmailValidator\EmailLexer;
use Egulias\EmailValidator\Validation\Error\IllegalMailbox;
use Egulias\EmailValidator\Validation\MailboxCheckValidation;
use Egulias\EmailValidator\Warning\NoDNSMXRecord;
use PHPUnit\Framework\TestCase;

class MailboxCheckValidationTest extends TestCase
{
    public function testValidMailbox()
    {
        $validation = new MailboxCheckValidation();
        $this->assertTrue($validation->isValid('no-reply@gmail.com', new EmailLexer()));
    }

    public function testInvalidMailbox()
    {
        $validation = new MailboxCheckValidation();
        $this->assertFalse($validation->isValid('invalid-mailbox@example.com', new EmailLexer()));
    }

    public function testDNSWarnings()
    {
        $validation = new MailboxCheckValidation();
        $expectedWarnings = [NoDNSMXRecord::CODE => new NoDNSMXRecord()];
        $validation->isValid('example@invalid.example.com', new EmailLexer());
        $this->assertEquals($expectedWarnings, $validation->getWarnings());
    }

    public function testIllegalMailboxError()
    {
        $validation = new MailboxCheckValidation();
        $expectedError = new IllegalMailbox(550);
        $validation->isValid('invalid-mailbox@gmail.com', new EmailLexer());
        $this->assertEquals($expectedError, $validation->getError());
    }
}
