<?php

namespace Egulias\EmailValidator\Tests\EmailValidator;

use PHPUnit\Framework\TestCase;
use Egulias\EmailValidator\EmailValidator;
use Egulias\EmailValidator\Result\InvalidEmail;
use Egulias\EmailValidator\Tests\EmailValidator\Dummy\DummyReason;
use Egulias\EmailValidator\Validation\EmailValidation;
use Egulias\EmailValidator\Validation\RFCValidation;
use Egulias\EmailValidator\Result\Reason\LabelTooLong;
use Egulias\EmailValidator\Validation\MultipleValidationWithAnd;

class EmailValidatorTest extends TestCase
{


    /**
     * @dataProvider domainLabelLengths
     */
    public function testDomainLabelLengths(string $email, bool $valid): void
    {
        $validator = new EmailValidator();
        $this->assertSame($valid, $validator->isValid($email, new RFCValidation()));
        if (!$valid) {
            $this->assertInstanceOf(LabelTooLong::class, $validator->getError()->reason());
        }
        // A failed parse must not affect the next address validated by this instance.
        $this->assertTrue($validator->isValid('test@example.org', new RFCValidation()));
    }

    public static function domainLabelLengths(): array
    {
        $cases = [];
        foreach ([62, 63, 64] as $length) {
            $labels = [
                'ascii' => str_repeat('a', $length),
                'multiple-tokens' => str_repeat('a', $length - 2) . '-1',
                'idn' => str_repeat('ü', $length - 6),
            ];
            foreach ($labels as $kind => $label) {
                foreach ([$label . '.org', 'x.' . $label . '.org', 'x.y.' . $label] as $position => $domain) {
                    $cases[$kind . '-' . $length . '-' . $position] = ['test@' . $domain, $length <= 63];
                }
            }
        }
        return $cases;
    }

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
