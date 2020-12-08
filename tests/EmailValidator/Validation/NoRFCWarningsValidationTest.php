<?php

namespace Egulias\EmailValidator\Tests\EmailValidator\Validation;

use Egulias\EmailValidator\EmailLexer;
use Egulias\EmailValidator\Exception\NoDomainPart;
use Egulias\EmailValidator\Validation\Error\RFCWarnings;
use Egulias\EmailValidator\Validation\NoRFCWarningsValidation;
use PHPUnit\Framework\TestCase;

class NoRFCWarningsValidationTest extends TestCase
{
    public function testInvalidEmailIsInvalid()
    {
        $validation = new NoRFCWarningsValidation();

        $this->assertFalse($validation->isValid('non-email-string', new EmailLexer()));
        $this->assertInstanceOf(NoDomainPart::class, $validation->getError());
    }

    public function testEmailWithWarningsIsInvalid()
    {
        $validation = new NoRFCWarningsValidation();

        $this->assertFalse($validation->isValid(str_repeat('x', 254).'@example.com', new EmailLexer())); // too long email
        $this->assertInstanceOf(RFCWarnings::class, $validation->getError());
    }

    /**
     * @dataProvider getValidEmailsWithoutWarnings
     */
    public function testEmailWithoutWarningsIsValid($email)
    {
        $validation = new NoRFCWarningsValidation();

        $this->assertTrue($validation->isValid($email, new EmailLexer()));
        $this->assertNull($validation->getError());
    }

    public function getValidEmailsWithoutWarnings()
    {
        return [
            ['example@example.com',],
            [sprintf('example@%s.com', str_repeat('ъ', 40)),],
        ];
    }
}
