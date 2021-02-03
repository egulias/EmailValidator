<?php

namespace Egulias\EmailValidator\Tests\EmailValidator\Result;

use PHPUnit\Framework\TestCase;
use Egulias\EmailValidator\Result\ValidEmail;
use Egulias\EmailValidator\Result\InvalidEmail;
use Egulias\EmailValidator\Result\Reason\CharNotAllowed;

class ResultTest extends TestCase
{
    public function testResultIsValidEmail()
    {
        $result = new ValidEmail();
        $expectedCode = 0;
        $expectedDescription = "Valid email";

        $this->assertTrue($result->isValid());
        $this->assertEquals($expectedCode, $result->code());
        $this->assertEquals($expectedDescription, $result->description());
    }

    public function testResultIsInvalidEmail()
    {
        $reason = new CharNotAllowed();
        $token = "T";
        $result = new InvalidEmail($reason, $token);
        $expectedCode = $reason->code();
        $expectedDescription = $reason->description() . " in char " . $token;

        $this->assertFalse($result->isValid());
        $this->assertEquals($expectedCode, $result->code());
        $this->assertEquals($expectedDescription, $result->description());
    }
}
