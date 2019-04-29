<?php

namespace Egulias\Tests\EmailValidator\Validation;

use Egulias\EmailValidator\EmailLexer;
use Egulias\EmailValidator\Helper\SmtpSocketHelper;
use Egulias\EmailValidator\Validation\Error\IllegalMailbox;
use Egulias\EmailValidator\Validation\MailboxCheckValidation;
use Egulias\EmailValidator\Warning\NoDNSMXRecord;
use PHPUnit\Framework\TestCase;

class MailboxCheckValidationTest extends TestCase
{
    public function testValidMailbox()
    {
        $socketHelperMock = $this->getMockBuilder(SmtpSocketHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $socketHelperMock
            ->expects($this->any())
            ->method('isResource')
            ->willReturn(true)
        ;

        $socketHelperMock
            ->expects($this->any())
            ->method('getResponseCode')
            ->willReturnOnConsecutiveCalls(220, 250, 250, 250)
        ;

        $validation = new MailboxCheckValidation($socketHelperMock, 'test@validation.email');

        $this->assertTrue($validation->isValid('success@validation.email', new EmailLexer()));
    }

    public function testDNSWarnings()
    {
        $validation = new MailboxCheckValidation(new SmtpSocketHelper(), 'test@validation.email');
        $expectedWarnings = [NoDNSMXRecord::CODE => new NoDNSMXRecord()];
        $validation->isValid('example@invalid.example.com', new EmailLexer());
        $this->assertEquals($expectedWarnings, $validation->getWarnings());
    }

    public function testIllegalMailboxError()
    {
        $socketHelperMock = $this->getMockBuilder(SmtpSocketHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $socketHelperMock
            ->expects($this->any())
            ->method('isResource')
            ->willReturn(true)
        ;

        $socketHelperMock
            ->expects($this->any())
            ->method('getResponseCode')
            ->willReturnOnConsecutiveCalls(220, 250, 250, 550)
        ;

        $validation = new MailboxCheckValidation($socketHelperMock, 'test@validation.email');
        $validation->isValid('failure@validation.email', new EmailLexer());
        $this->assertEquals(new IllegalMailbox(550), $validation->getError());
    }
}
