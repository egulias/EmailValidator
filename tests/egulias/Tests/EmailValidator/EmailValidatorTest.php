<?php

namespace Egulias\Tests\EmailValidator;

use Egulias\EmailValidator\EmailValidator;

class EmailValidatorTest extends \PHPUnit_Framework_TestCase
{
    public function testValidationIsUsed()
    {
        $validator = new EmailValidator();
        $validation = $this->getMock("Egulias\\EmailValidator\\Validation\\EmailValidation");
        $validation->expects($this->once())->method("isValid")->willReturn(true);
        $validation->expects($this->once())->method("getWarnings")->willReturn([]);
        $validation->expects($this->once())->method("getError")->willReturn(new \InvalidArgumentException());

        $this->assertTrue($validator->isValid("example@example.com", $validation));
    }
}
