<?php

namespace Egulias\EmailValidator\Tests\EmailValidator\Reason;

use PHPUnit\Framework\TestCase;
use Egulias\EmailValidator\Result\MultipleErrors;
use Egulias\EmailValidator\Tests\EmailValidator\Dummy\AnotherDummyReason;
use Egulias\EmailValidator\Tests\EmailValidator\Dummy\DummyReason;

class MultipleErrorsTest extends TestCase
{
    public function testRegisterSameReason()
    {
        $error1 = new DummyReason();
        $error2 = new DummyReason();

        $multiError = new MultipleErrors();
        $multiError->addReason($error1);
        $multiError->addReason($error2);

        $this->assertCount(1, $multiError->getReasons());
    }

    public function testRegisterDifferentReasons()
    {
        $error1 = new DummyReason();
        $error2 = new AnotherDummyReason();
        $expectedReason = $error1->description() . PHP_EOL . $error2->description() . PHP_EOL;

        $multiError = new MultipleErrors();
        $multiError->addReason($error1);
        $multiError->addReason($error2);

        $this->assertCount(2, $multiError->getReasons());
        $this->assertEquals($expectedReason, $multiError->description());
        $this->assertEquals($error1, $multiError->reason());
    }
}
