<?php

namespace Egulias\EmailValidator\Tests\EmailValidator\Validation;

use Egulias\EmailValidator\EmailLexer;
use Egulias\EmailValidator\Result\Reason\NoDomainPart;
use Egulias\EmailValidator\Result\Reason\RFCWarnings;
use Egulias\EmailValidator\Validation\NoRFCWarningsValidation;
use PHPUnit\Framework\TestCase;

class NoRFCWarningsValidationTest extends TestCase
{
    public function testInvalidEmailIsInvalid()
    {
        $validation = new NoRFCWarningsValidation();

        $this->assertFalse($validation->isValid('non-email-string', new EmailLexer()));
        $this->assertInstanceOf(NoDomainPart::class, $validation->getError()->reason());
    }

    /**
     * @dataProvider getEmailsWithWarnings
     */
    public function testEmailWithWarningsIsInvalid(string $email)
    {
        $validation = new NoRFCWarningsValidation();

        $this->assertFalse($validation->isValid($email, new EmailLexer()));
        $this->assertInstanceOf(RFCWarnings::class, $validation->getError()->reason());
    }

    public static function getEmailsWithWarnings(): array
    {
        return [
            ['test()@example.com'],
            [' test @iana.org'],
            ['test . test@iana.org'],
            ['test@(comment)[255.255.255.255]'],
        ];
    }

    /**
     * @dataProvider getValidEmailsWithoutWarnings
     */
    public function testEmailWithoutWarningsIsValid($email)
    {
        $validation = new NoRFCWarningsValidation();

        $this->assertTrue($validation->isValid('example@example.com', new EmailLexer()));
        $this->assertTrue($validation->isValid($email, new EmailLexer()));
        $this->assertNull($validation->getError());
    }

    public static function getValidEmailsWithoutWarnings()
    {
        return [
            ['example@example.com',],
            [sprintf('example@%s.com', str_repeat('ъ', 40)),],
        ];
    }
}
