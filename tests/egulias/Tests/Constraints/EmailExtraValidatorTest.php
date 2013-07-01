<?php

namespace Egulias\Tests\Constraints;

use Symfony\Component\Validator\Validation;
use Egulias\Constraints\EmailExtra;
use Egulias\Constraints\EmailExtraValidator;

class EmailExtraValidatorTest extends \PHPUnit_Framework_TestCase
{
    protected $context;
    protected $validator;

    protected function setUp()
    {
        $this->context = $this->getMock('Symfony\Component\Validator\ExecutionContext', array(), array(), '', false);
        $this->validator = new EmailExtraValidator();
        $this->validator->initialize($this->context);
    }

    protected function tearDown()
    {
        $this->context = null;
        $this->validator = null;
    }

    public function testNullIsValid()
    {
        $this->context->expects($this->never())
            ->method('addViolation');

        $this->validator->validate(null, new EmailExtra());
    }

    public function testEmptyStringIsValid()
    {
        $this->context->expects($this->never())
            ->method('addViolation');

        $this->validator->validate('', new EmailExtra());
    }

    /**
     *  * @expectedException \Symfony\Component\Validator\Exception\UnexpectedTypeException
     *  */
    public function testExpectsStringCompatibleType()
    {
        $this->validator->validate(new \stdClass(), new EmailExtra());
    }

    /**
     *  * @dataProvider getValidEmails
     *  */
    public function testValidEmails($email)
    {
        $this->context->expects($this->never())
            ->method('addViolation');

        $this->validator->validate($email, new EmailExtra());
    }

    public function getValidEmails()
    {
        return array(
            array('fabien@symfony.com'),
            array('example@example.co.uk'),
            array('fabien_potencier@example.fr'),
            array('example@localhost'),
        );
    }

    /**
     *  * @dataProvider getInvalidEmails
     *  */
    public function testInvalidEmails($email)
    {
        $constraint = new EmailExtra(
            array(
                'message' => 'myMessage'
            )
        );

        $this->context->expects($this->once())->method('addViolation')
            ->with(
                'myMessage',
                array('{{ value }}' => $email,)
            );

        $this->validator->validate($email, $constraint);
    }

    public function getInvalidEmails()
    {
        return array(
            array('example'),
            array('example@'),
            array('example@example.com@example.com'),
        );
    }
}
