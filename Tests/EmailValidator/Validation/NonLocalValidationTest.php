<?php

namespace Egulias\Tests\EmailValidator\Validation;

use Egulias\EmailValidator\EmailLexer;
use Egulias\EmailValidator\Validation\Error\LocalEmail;
use Egulias\EmailValidator\Validation\NonLocalValidation;
use PHPUnit\Framework\TestCase;

class NonLocalValidationTest extends TestCase
{
    public function validEmailsProvider()
    {
        return [
            ['test@localhost.org'],
            ['test.test@example.org'],
        ];
    }

    public function invalidEmailsProvider()
    {
        return [
            ['test@localhost'],
            ['test.test@example'],
        ];
    }

    /**
     * @dataProvider validEmailsProvider
     */
    public function testValidNonLocal($validEmail)
    {
        $validation = new NonLocalValidation();
        $this->assertTrue($validation->isValid($validEmail, new EmailLexer()));
    }

    /**
     * @dataProvider invalidEmailsProvider
     */
    public function testInvalidNonLocal($invalidEmail)
    {
        $validation = new NonLocalValidation();
        $this->assertFalse($validation->isValid($invalidEmail, new EmailLexer()));
        
        $expectedError = new LocalEmail();
        $this->assertEquals($expectedError, $validation->getError());
    }
}
