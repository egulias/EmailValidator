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

    public static function validMessageIDs() : array
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

    public static function invalidMessageIDs() : array
    {
        return [
            ['example'],
            ['example@with space'],
            ['example@iana.'],
            ['example@ia\na.'],
            /**
             * RFC 2822, section 3.6.4, Page 25
             * Since the msg-id has
             * a similar syntax to angle-addr (identical except that comments and
             * folding white space are not allowed), a good method is to put the
             * domain name (or a domain literal IP address) of the host on which the
             * message identifier was created on the right hand side of the "@", and
             * put a combination of the current absolute date and time along with
             * some other currently unique (perhaps sequential) identifier available
             * on the system (for example, a process id number) on the left hand
             * side.
             */
            ['example(comment)@example.com'],
            ["\r\nFWS@example.com"]
        ];
    }

    public function testInvalidMessageIDsWithError()
    {
        $this->markTestIncomplete("missing error check");

    }
}
