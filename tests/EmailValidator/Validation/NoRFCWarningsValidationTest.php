<?php

namespace Egulias\EmailValidator\Tests\EmailValidator\Validation;

use Egulias\EmailValidator\EmailLexer;
use Egulias\EmailValidator\Result\Reason\NoDomainPart as ReasonNoDomainPart;
use Egulias\EmailValidator\Result\Reason\RFCWarnings;
use Egulias\EmailValidator\Validation\NoRFCWarningsValidation;
use PHPUnit\Framework\TestCase;

class NoRFCWarningsValidationTest extends TestCase
{
    public function testInvalidEmailIsInvalid()
    {
        $validation = new NoRFCWarningsValidation();

        $this->assertFalse($validation->isValid('non-email-string', new EmailLexer()));
        $this->assertInstanceOf(ReasonNoDomainPart::class, $validation->getError()->reason());
    }

    public function testEmailWithWarningsIsInvalid()
    {
        $validation = new NoRFCWarningsValidation();

        $this->assertFalse($validation->isValid('test()@example.com', new EmailLexer()));
        $this->assertInstanceOf(RFCWarnings::class, $validation->getError()->reason());
    }

    public function testEmailWithoutWarningsIsValid()
    {
        $validation = new NoRFCWarningsValidation();

        $this->assertTrue($validation->isValid('example@example.com', new EmailLexer()));
        $this->assertNull($validation->getError());
    }
}
