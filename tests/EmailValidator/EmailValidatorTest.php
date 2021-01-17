<?php

namespace Egulias\EmailValidator\Tests\EmailValidator;

use PHPUnit\Framework\TestCase;
use Egulias\EmailValidator\EmailValidator;
use Egulias\EmailValidator\Result\InvalidEmail;
use Egulias\EmailValidator\Tests\EmailValidator\Dummy\DummyReason;
use Egulias\EmailValidator\Validation\EmailValidation;
use Egulias\EmailValidator\Validation\MultipleValidationWithAnd;

class EmailValidatorTest extends TestCase
{


    public function testValidationIsUsed()
    {
        $invalidEmail = new InvalidEmail(new DummyReason(), '');
        $validator = new EmailValidator();
        $validation = $this->getMockBuilder(EmailValidation::class)->getMock();
        $validation->expects($this->once())->method("isValid")->willReturn(true);
        $validation->expects($this->once())->method("getWarnings")->willReturn([]);
        $validation->expects($this->once())->method("getError")->willReturn($invalidEmail);

        $this->assertTrue($validator->isValid("example@example.com", $validation));
    }

    public function testMultipleValidation()
    {
        $validator = new EmailValidator();
        $validation = $this->getMockBuilder(EmailValidation::class)->getMock();
        $validation->expects($this->once())->method("isValid")->willReturn(true);
        $validation->expects($this->once())->method("getWarnings")->willReturn([]);
        $validation->expects($this->never(2))->method("getError");
        $multiple = new MultipleValidationWithAnd([$validation]);

        $this->assertTrue($validator->isValid("example@example.com", $multiple));
    }

    public function testValidationIsFalse()
    {
        $invalidEmail = new InvalidEmail(new DummyReason(), '');
        $validator = new EmailValidator();
        $validation = $this->getMockBuilder(EmailValidation::class)->getMock();
        $validation->expects($this->once())->method("isValid")->willReturn(false);
        $validation->expects($this->once())->method("getWarnings")->willReturn([]);
        $validation->expects($this->once())->method("getError")->willReturn($invalidEmail);

        $this->assertFalse($validator->isValid("example@example.com", $validation));
        $this->assertEquals(false, $validator->hasWarnings());
        $this->assertEquals([], $validator->getWarnings());
        $this->assertEquals($invalidEmail, $validator->getError());
    }
}
