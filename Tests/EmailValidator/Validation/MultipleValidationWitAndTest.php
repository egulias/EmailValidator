<?php

namespace Egulias\Tests\EmailValidator\Validation;

use Egulias\EmailValidator\Exception\CommaInDomain;
use Egulias\EmailValidator\Exception\NoDomainPart;
use Egulias\EmailValidator\Validation\MultipleErrors;
use Egulias\EmailValidator\Validation\MultipleValidationWithAnd;
use Egulias\EmailValidator\Warning\AddressLiteral;
use Egulias\EmailValidator\Warning\DomainLiteral;
use PHPUnit\Framework\TestCase;

class MultipleValidationWitAndTest extends TestCase
{
    public function testUsesAndLogicalOperation()
    {
        $lexer = $this->getMockBuilder("Egulias\\EmailValidator\\EmailLexer")->getMock();
        $validationTrue = $this->getMockBuilder("Egulias\\EmailValidator\\Validation\\EmailValidation")->getMock();
        $validationTrue->expects($this->any())->method("isValid")->willReturn(true);
        $validationTrue->expects($this->any())->method("getWarnings")->willReturn([]);
        $validationFalse = $this->getMockBuilder("Egulias\\EmailValidator\\Validation\\EmailValidation")->getMock();
        $validationFalse->expects($this->any())->method("isValid")->willReturn(false);
        $validationFalse->expects($this->any())->method("getWarnings")->willReturn([]);
        $multipleValidation = new MultipleValidationWithAnd([$validationTrue, $validationFalse]);
        $this->assertFalse($multipleValidation->isValid("exmpale@example.com", $lexer));
    }

    /**
     * @expectedException \Egulias\EmailValidator\Validation\Exception\EmptyValidationList
     */
    public function testEmptyListIsNotAllowed()
    {
        new MultipleValidationWithAnd([]);
    }

    public function testValidationIsValid()
    {
        $lexer = $this->getMockBuilder("Egulias\\EmailValidator\\EmailLexer")->getMock();

        $validation = $this->getMockBuilder("Egulias\\EmailValidator\\Validation\\EmailValidation")->getMock();
        $validation->expects($this->any())->method("isValid")->willReturn(true);
        $validation->expects($this->once())->method("getWarnings")->willReturn([]);

        $multipleValidation = new MultipleValidationWithAnd([$validation]);
        $this->assertTrue($multipleValidation->isValid("example@example.com", $lexer));
        $this->assertNull($multipleValidation->getError());
    }

    public function testAccumulatesWarnings()
    {
        $warnings1 = [
            AddressLiteral::CODE => new AddressLiteral()
        ];
        $warnings2 = [
            DomainLiteral::CODE => new DomainLiteral()
        ];
        $expectedResult = array_merge($warnings1, $warnings2);

        $lexer = $this->getMockBuilder("Egulias\\EmailValidator\\EmailLexer")->getMock();
        $validation1 = $this->getMockBuilder("Egulias\\EmailValidator\\Validation\\EmailValidation")->getMock();
        $validation1->expects($this->any())->method("isValid")->willReturn(true);
        $validation1->expects($this->once())->method("getWarnings")->willReturn($warnings1);

        $validation2 = $this->getMockBuilder("Egulias\\EmailValidator\\Validation\\EmailValidation")->getMock();

        $validation2->expects($this->any())->method("isValid")->willReturn(false);
        $validation2->expects($this->once())->method("getWarnings")->willReturn($warnings2);

        $multipleValidation = new MultipleValidationWithAnd([$validation1, $validation2]);
        $multipleValidation->isValid("example@example.com", $lexer);
        $this->assertEquals($expectedResult, $multipleValidation->getWarnings());
    }

    public function testGathersAllTheErrors()
    {
        $error1 = new CommaInDomain();
        $error2 = new NoDomainPart();

        $expectedResult = new MultipleErrors([$error1, $error2]);

        $lexer = $this->getMockBuilder("Egulias\\EmailValidator\\EmailLexer")->getMock();

        $validation1 = $this->getMockBuilder("Egulias\\EmailValidator\\Validation\\EmailValidation")->getMock();
        $validation1->expects($this->any())->method("isValid")->willReturn(true);
        $validation1->expects($this->once())->method("getWarnings")->willReturn([]);
        $validation1->expects($this->once())->method("getError")->willReturn($error1);

        $validation2 = $this->getMockBuilder("Egulias\\EmailValidator\\Validation\\EmailValidation")->getMock();
        $validation2->expects($this->any())->method("isValid")->willReturn(false);
        $validation2->expects($this->once())->method("getWarnings")->willReturn([]);
        $validation2->expects($this->once())->method("getError")->willReturn($error2);

        $multipleValidation = new MultipleValidationWithAnd([$validation1, $validation2]);
        $multipleValidation->isValid("example@example.com", $lexer);
        $this->assertEquals($expectedResult, $multipleValidation->getError());
    }

    public function testBreakOutOfLoopWhenError()
    {
        $error = new CommaInDomain();

        $expectedResult = new MultipleErrors([$error]);

        $lexer = $this->getMockBuilder("Egulias\\EmailValidator\\EmailLexer")->getMock();

        $validation1 = $this->getMockBuilder("Egulias\\EmailValidator\\Validation\\EmailValidation")->getMock();
        $validation1->expects($this->any())->method("isValid")->willReturn(false);
        $validation1->expects($this->once())->method("getWarnings")->willReturn([]);
        $validation1->expects($this->once())->method("getError")->willReturn($error);

        $validation2 = $this->getMockBuilder("Egulias\\EmailValidator\\Validation\\EmailValidation")->getMock();
        $validation2->expects($this->never())->method("isValid");
        $validation2->expects($this->never())->method("getWarnings");
        $validation2->expects($this->never())->method("getError");

        $multipleValidation = new MultipleValidationWithAnd([$validation1, $validation2], MultipleValidationWithAnd::STOP_ON_ERROR);
        $multipleValidation->isValid("example@example.com", $lexer);
        $this->assertEquals($expectedResult, $multipleValidation->getError());
    }
}
