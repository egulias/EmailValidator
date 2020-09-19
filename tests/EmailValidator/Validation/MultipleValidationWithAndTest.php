<?php

namespace Egulias\EmailValidator\Tests\EmailValidator\Validation;

use PHPUnit\Framework\TestCase;
use Egulias\EmailValidator\EmailLexer;
use Egulias\EmailValidator\Result\InvalidEmail;
use Egulias\EmailValidator\Result\MultipleErrors;
use Egulias\EmailValidator\Tests\EmailValidator\Dummy\AnotherDummyReason;
use Egulias\EmailValidator\Warning\DomainLiteral;
use Egulias\EmailValidator\Warning\AddressLiteral;
use Egulias\EmailValidator\Validation\RFCValidation;
use Egulias\EmailValidator\Validation\EmailValidation;
use Egulias\EmailValidator\Validation\MultipleValidationWithAnd;
use Egulias\EmailValidator\Tests\EmailValidator\Dummy\DummyReason;
use Egulias\EmailValidator\Validation\Exception\EmptyValidationList;

class MultipleValidationWithAndTest extends TestCase
{
    public function testUsesAndLogicalOperation()
    {
        $invalidEmail = new InvalidEmail(new DummyReason(), '');
        $lexer = new EmailLexer();
        $validationTrue = $this->getMockBuilder(EmailValidation::class)->getMock();
        $validationTrue->expects($this->any())->method("isValid")->willReturn(true);
        $validationTrue->expects($this->any())->method("getWarnings")->willReturn([]);

        $validationFalse = $this->getMockBuilder(EmailValidation::class)->getMock();
        $validationFalse->expects($this->any())->method("isValid")->willReturn(false);
        $validationFalse->expects($this->any())->method("getWarnings")->willReturn([]);
        $validationFalse->expects($this->any())->method("getError")->willReturn($invalidEmail);

        $multipleValidation = new MultipleValidationWithAnd([$validationTrue, $validationFalse]);

        $this->assertFalse($multipleValidation->isValid("exmpale@example.com", $lexer));
    }

    public function testEmptyListIsNotAllowed()
    {
        $this->expectException(EmptyValidationList::class);
        new MultipleValidationWithAnd([]);
    }

    public function testValidationIsValid()
    {
        $lexer = new EmailLexer();

        $validation = $this->getMockBuilder(EmailValidation::class)->getMock();
        $validation->expects($this->any())->method("isValid")->willReturn(true);
        $validation->expects($this->once())->method("getWarnings")->willReturn([]);
        $validation->expects($this->any())->method("getError")->willReturn(null);

        $multipleValidation = new MultipleValidationWithAnd([$validation]);
        $this->assertTrue($multipleValidation->isValid("example@example.com", $lexer));
        $this->assertNull($multipleValidation->getError());
    }

    public function testAccumulatesWarnings()
    {
        $invalidEmail = new InvalidEmail(new DummyReason(), '');
        $warnings1 = [
            AddressLiteral::CODE => new AddressLiteral()
        ];
        $warnings2 = [
            DomainLiteral::CODE => new DomainLiteral()
        ];
        $expectedResult = array_merge($warnings1, $warnings2);

        $lexer = new EmailLexer();
        $validation1 = $this->getMockBuilder(EmailValidation::class)->getMock();
        $validation1->expects($this->any())->method("isValid")->willReturn(true);
        $validation1->expects($this->once())->method("getWarnings")->willReturn($warnings1);
        $validation1->expects($this->any())->method("getError")->willReturn($invalidEmail);

        $validation2 = $this->getMockBuilder(EmailValidation::class)->getMock();

        $validation2->expects($this->any())->method("isValid")->willReturn(false);
        $validation2->expects($this->once())->method("getWarnings")->willReturn($warnings2);
        $validation2->expects($this->any())->method("getError")->willReturn($invalidEmail);

        $multipleValidation = new MultipleValidationWithAnd([$validation1, $validation2]);
        $multipleValidation->isValid("example@example.com", $lexer);
        $this->assertEquals($expectedResult, $multipleValidation->getWarnings());
    }

    public function testGathersAllTheErrors()
    {
        $invalidEmail = new InvalidEmail(new DummyReason(), '');
        $invalidEmail2 = new InvalidEmail(new AnotherDummyReason(), '');

        $error1 = new DummyReason();
        $error2 = new AnotherDummyReason();

        $expectedResult = new MultipleErrors();
        $expectedResult->addReason($error1);
        $expectedResult->addReason($error2);

        $lexer = new EmailLexer();

        $validation1 = $this->getMockBuilder(EmailValidation::class)->getMock();
        $validation1->expects($this->once())->method("isValid")->willReturn(false);
        $validation1->expects($this->once())->method("getWarnings")->willReturn([]);
        $validation1->expects($this->exactly(2))->method("getError")->willReturn($invalidEmail);

        $validation2 = $this->getMockBuilder(EmailValidation::class)->getMock();
        $validation2->expects($this->once())->method("isValid")->willReturn(false);
        $validation2->expects($this->once())->method("getWarnings")->willReturn([]);
        $validation2->expects($this->exactly(2))->method("getError")->willReturn($invalidEmail2);

        $multipleValidation = new MultipleValidationWithAnd([$validation1, $validation2]);
        $multipleValidation->isValid("example@example.com", $lexer);
        $this->assertEquals($expectedResult, $multipleValidation->getError());
    }

    public function testStopsAfterFirstError()
    {
        $invalidEmail = new InvalidEmail(new DummyReason(), '');
        $invalidEmail2 = new InvalidEmail(new AnotherDummyReason(), '');

        $error1 = new DummyReason();

        $expectedResult = new MultipleErrors();
        $expectedResult->addReason($error1);

        $lexer = new EmailLexer();

        $validation1 = $this->getMockBuilder(EmailValidation::class)->getMock();
        $validation1->expects($this->any())->method("isValid")->willReturn(false);
        $validation1->expects($this->once())->method("getWarnings")->willReturn([]);
        $validation1->expects($this->exactly(2))->method("getError")->willReturn($invalidEmail);

        $validation2 = $this->getMockBuilder(EmailValidation::class)->getMock();
        $validation2->expects($this->any())->method("isValid")->willReturn(false);
        $validation2->expects($this->never())->method("getWarnings")->willReturn([]);
        $validation1->expects($this->exactly(2))->method("getError")->willReturn($invalidEmail2);

        $multipleValidation = new MultipleValidationWithAnd([$validation1, $validation2], MultipleValidationWithAnd::STOP_ON_ERROR);
        $multipleValidation->isValid("example@example.com", $lexer);
        $this->assertEquals($expectedResult, $multipleValidation->getError());
    }

    public function testBreakOutOfLoopWhenError()
    {
        $invalidEmail = new InvalidEmail(new DummyReason(), '');
        $error1 = new DummyReason();

        $expectedResult = new MultipleErrors();
        $expectedResult->addReason($error1);

        $lexer = new EmailLexer();

        $validation1 = $this->getMockBuilder(EmailValidation::class)->getMock();
        $validation1->expects($this->any())->method("isValid")->willReturn(false);
        $validation1->expects($this->once())->method("getWarnings")->willReturn([]);
        $validation1->expects($this->exactly(2))->method("getError")->willReturn($invalidEmail);

        $validation2 = $this->getMockBuilder(EmailValidation::class)->getMock();
        $validation2->expects($this->never())->method("isValid");
        $validation2->expects($this->never())->method("getWarnings");
        $validation2->expects($this->never())->method("getError");

        $multipleValidation = new MultipleValidationWithAnd([$validation1, $validation2], MultipleValidationWithAnd::STOP_ON_ERROR);
        $multipleValidation->isValid("example@example.com", $lexer);
        $this->assertEquals($expectedResult, $multipleValidation->getError());
    }

    public function testBreakoutOnInvalidEmail()
    {
        $lexer = new EmailLexer();

        $validationNotCalled = $this->getMockBuilder(EmailValidation::class)->getMock();
        $validationNotCalled->expects($this->never())->method("isValid");
        $validationNotCalled->expects($this->never())->method("getWarnings");
        $validationNotCalled->expects($this->never())->method("getError");
        $multipleValidation = new MultipleValidationWithAnd([new RFCValidation(), $validationNotCalled], MultipleValidationWithAnd::STOP_ON_ERROR);
        $this->assertFalse($multipleValidation->isValid("invalid-email", $lexer));
    }
}
