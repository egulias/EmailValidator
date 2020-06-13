<?php

namespace Egulias\Tests\EmailValidator\Validation;

use Egulias\EmailValidator\EmailLexer;
use Egulias\EmailValidator\Exception\NoDomainPart;
use Egulias\EmailValidator\Result\Reason\NoDomainPart as ReasonNoDomainPart;
use Egulias\EmailValidator\Validation\Error\RFCWarnings;
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

        $this->assertFalse($validation->isValid(str_repeat('x', 254).'@example.com', new EmailLexer())); // too long email
        $this->assertInstanceOf(RFCWarnings::class, $validation->getError());
    }

    public function testEmailWithoutWarningsIsValid()
    {
        $validation = new NoRFCWarningsValidation();

        $this->assertTrue($validation->isValid('example@example.com', new EmailLexer()));
        $this->assertNull($validation->getError());
    }
}
