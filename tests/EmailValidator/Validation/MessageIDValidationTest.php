<?php

namespace Egulias\EmailValidator\Tests\EmailValidator\Validation;

use Egulias\EmailValidator\EmailLexer;
use Egulias\EmailValidator\Validation\MessageIDValidation;
use PHPUnit\Framework\TestCase;

class MessageIDValidationTest extends TestCase
{

    /**
     * @dataProvider validMessageIDs
     */
    public function testValidMessageIDs(string $messageID)
    {
        $validator = new MessageIDValidation();

        $this->assertTrue($validator->isValid($messageID, new EmailLexer()));
    }

    public function validMessageIDs() : array
    {
        return [
            ['a@b.c+&%$.d'],
            ['a.b+&%$.c@d'],
            ['a@ä'],
        ];
    }

    /**
     * @dataProvider invalidMessageIDs
     */
    public function testInvalidMessageIDs(string $messageID)
    {
        $validator = new MessageIDValidation();

        $this->assertFalse($validator->isValid($messageID, new EmailLexer()));
    }

    public function invalidMessageIDs() : array
    {
        return [
            ['example'],
            ['example@with space'],
            ['example@iana.'],
            ['example@ia\na.'],
        ];
    }

    public function testInvalidMessageIDsWithError()
    {
        $this->markTestIncomplete("missing error check");

    }
}
