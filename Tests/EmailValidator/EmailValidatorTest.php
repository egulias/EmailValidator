<?php

namespace Egulias\Tests\EmailValidator;

use Egulias\EmailValidator\EmailValidator;
use Egulias\EmailValidator\Validation\MultipleValidationWithAnd;
use PHPUnit\Framework\TestCase;

class EmailValidatorTest extends TestCase
{
    public function testValidationIsUsed()
    {
        $validator = new EmailValidator();
        $validation = $this->getMockBuilder("Egulias\\EmailValidator\\Validation\\EmailValidation")->getMock();
        $validation->expects($this->once())->method("isValid")->willReturn(true);
        $validation->expects($this->once())->method("getWarnings")->willReturn([]);
        $validation->expects($this->once())->method("getError")->willReturn(null);

        $this->assertTrue($validator->isValid("example@example.com", $validation));
    }

    public function testMultipleValidation()
    {
        $validator = new EmailValidator();
        $validation = $this->getMockBuilder("Egulias\\EmailValidator\\Validation\\EmailValidation")->getMock();
        $validation->expects($this->once())->method("isValid")->willReturn(true);
        $validation->expects($this->once())->method("getWarnings")->willReturn([]);
        $validation->expects($this->once())->method("getError")->willReturn(null);
        $multiple = new MultipleValidationWithAnd([$validation]);

        $this->assertTrue($validator->isValid("example@example.com", $multiple));
    }
}
