<?php

namespace Egulias\EmailValidator\Tests\EmailValidator\Validation;

use PHPUnit\Framework\TestCase;
use Egulias\EmailValidator\EmailLexer;
use Egulias\EmailValidator\Warning\Comment;
use Egulias\EmailValidator\Warning\CFWSNearAt;
use Egulias\EmailValidator\Result\InvalidEmail;
use Egulias\EmailValidator\Warning\CFWSWithFWS;
use Egulias\EmailValidator\Warning\LocalTooLong;
use Egulias\EmailValidator\Warning\QuotedString;
use Egulias\EmailValidator\Validation\RFCValidation;
use Egulias\EmailValidator\Result\Reason\NoLocalPart;
use Egulias\EmailValidator\Result\Reason\AtextAfterCFWS;
use Egulias\EmailValidator\Result\Reason\UnOpenedComment;
use Egulias\EmailValidator\Result\Reason\UnclosedQuotedString;
use Egulias\EmailValidator\Result\Reason\CRNoLF;
use Egulias\EmailValidator\Result\Reason\DotAtEnd;
use Egulias\EmailValidator\Result\Reason\DotAtStart;
use Egulias\EmailValidator\Result\Reason\ConsecutiveDot;
use Egulias\EmailValidator\Result\Reason\ExpectingATEXT;
use Egulias\EmailValidator\Result\Reason\UnclosedComment;
use Egulias\EmailValidator\Warning\TLD;

class RFCValidationTest extends TestCase
{
    /**
     * @var RFCValidation
     */
    protected $validator;

    /**
     * @var EmailLexer
     */
    protected $lexer;

    protected function setUp() : void
    {
        $this->validator = new RFCValidation();
        $this->lexer = new EmailLexer();
    }

    protected function tearDown() : void
    {
        $this->validator = null;
    }

    /**
     * @dataProvider getValidEmails
     */
    public function testValidEmails($email)
    {
        $this->assertTrue($this->validator->isValid($email, $this->lexer));
    }

    public static function getValidEmails()
    {
        return array(
            ['â@iana.org'],
            ['fabien@symfony.com'],
            ['example@example.co.uk'],
            ['fabien_potencier@example.fr'],
            ['fab\'ien@symfony.com'],
            ['fab\ ien@symfony.com'],
            ['example((example))@fakedfake.co.uk'],
            ['fabien+a@symfony.com'],
            ['exampl=e@example.com'],
            ['инфо@письмо.рф'],
            ['"username"@example.com'],
            ['"user,name"@example.com'],
            ['"user name"@example.com'],
            ['"user@name"@example.com'],
            ['"user\"name"@example.com'],
            ['"\a"@iana.org'],
            ['"test\ test"@iana.org'],
            ['""@iana.org'],
            ['"\""@iana.org'],
            ['müller@möller.de'],
            ["1500111@профи-инвест.рф"],
            [sprintf('example@%s.com', str_repeat('ъ', 40))],
        );
    }

    /**
     * @dataProvider getValidEmailsWithWarnings
     */
    public function testValidEmailsWithWarningsCheck($email, $expectedWarnings)
    {
        $this->assertTrue($this->validator->isValid($email, $this->lexer));
        $this->assertEquals($expectedWarnings, $this->validator->getWarnings());
    }

    public static function getValidEmailsWithWarnings()
    {
        return [
            ['a5aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa@example.com', [new LocalTooLong()]],
            ['example@example', [new TLD()]],
            ['example @invalid.example.com', [new CFWSNearAt()]],
            ['example(examplecomment)@invalid.example.com',[new Comment(), new CFWSNearAt()]],
            ["\"\t\"@invalid.example.com", [new QuotedString("", '"'), new CFWSWithFWS(),]],
            ["\"\r\"@invalid.example.com", [new QuotedString('', '"'), new CFWSWithFWS(),]],
            ['"example"@invalid.example.com', [new QuotedString('', '"')]],
            ['too_long_localpart_too_long_localpart_too_long_localpart_too_long_localpart@invalid.example.com',
                [new LocalTooLong()]],
        ];
    }

    public function testInvalidUTF8Email()
    {
        $email = "\x80\x81\x82@\x83\x84\x85.\x86\x87\x88";
        $this->assertFalse($this->validator->isValid($email, $this->lexer));
    }

    /**
     * @dataProvider getInvalidEmails
     */
    public function testInvalidEmails($email)
    {
        $this->assertFalse($this->validator->isValid($email, $this->lexer));
    }

    public static function getInvalidEmails()
    {
        return [
            ['user  name@example.com'],
            ['user   name@example.com'],
            ['example.@example.co.uk'],
            ['example@example@example.co.uk'],
            ['(test_exampel@example.fr'],
            ['example(example]example@example.co.uk'],
            ['.example@localhost'],
            ['ex\ample@localhost'],
            ['user name@example.com'],
            ['usern,ame@example.com'],
            ['user[na]me@example.com'],
            ['"""@iana.org'],
            ['"\"@iana.org'],
            ['"test"test@iana.org'],
            ['"test""test"@iana.org'],
            ['"test"."test"@iana.org'],
            ['"test".test@iana.org'],
            ['"test"' . chr(0) . '@iana.org'],
            ['"test\"@iana.org'],
            [chr(226) . '@iana.org'],
            ['\r\ntest@iana.org'],
            ['\r\n test@iana.org'],
            ['\r\n \r\ntest@iana.org'],
            ['\r\n \r\ntest@iana.org'],
            ['\r\n \r\n test@iana.org'],
            ['test;123@foobar.com'],
            ['examp║le@symfony.com'],
            ['0'],
            [0],
        ];
    }

    /**
     * @dataProvider getInvalidEmailsWithErrors
     */
    public function testInvalidDEmailsWithErrorsCheck($error, $email)
    {
        $this->assertFalse($this->validator->isValid($email, $this->lexer));
        $this->assertEquals($error, $this->validator->getError());
    }

    public static function getInvalidEmailsWithErrors()
    {
        return [
            [new InvalidEmail(new NoLocalPart(), "@"), '@example.co.uk'],
            [new InvalidEmail(new ConsecutiveDot(), '.'), 'example..example@example.co.uk'],
            [new InvalidEmail(new ExpectingATEXT('Invalid token found'), '<'), '<example_example>@example.fr'],
            [new InvalidEmail(new DotAtStart(), '.'), '.example@localhost'],
            [new InvalidEmail(new DotAtEnd(), '.'), 'example.@example.co.uk'],
            [new InvalidEmail(new UnclosedComment(), '('), '(example@localhost'],
            [new InvalidEmail(new UnclosedQuotedString(), '"'), '"example@localhost'],
            [
                new InvalidEmail(
                    new ExpectingATEXT('https://tools.ietf.org/html/rfc5322#section-3.2.4 - quoted string should be a unit'),
                    '"'),
                'exa"mple@localhost'
            ],
            [new InvalidEmail(new UnOpenedComment(), ')'), 'comment)example@localhost'],
            [new InvalidEmail(new UnOpenedComment(), ')'), 'example(comment))@localhost'],
            [new InvalidEmail(new AtextAfterCFWS(), "\n"), "exampl\ne@example.co.uk"],
            [new InvalidEmail(new AtextAfterCFWS(), "\t"), "exampl\te@example.co.uk"],
            [new InvalidEmail(new CRNoLF(), "\r"), "exam\rple@example.co.uk"],
        ];
    }
}