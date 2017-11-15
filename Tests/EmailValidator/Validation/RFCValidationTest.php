<?php

namespace Egulias\Tests\EmailValidator\Validation;

use Egulias\EmailValidator\EmailLexer;
use Egulias\EmailValidator\Validation\RFCValidation;
use Egulias\EmailValidator\Exception\AtextAfterCFWS;
use Egulias\EmailValidator\Exception\ConsecutiveAt;
use Egulias\EmailValidator\Exception\ConsecutiveDot;
use Egulias\EmailValidator\Exception\CRNoLF;
use Egulias\EmailValidator\Exception\DomainHyphened;
use Egulias\EmailValidator\Exception\DotAtEnd;
use Egulias\EmailValidator\Exception\DotAtStart;
use Egulias\EmailValidator\Exception\ExpectingATEXT;
use Egulias\EmailValidator\Exception\ExpectingDTEXT;
use Egulias\EmailValidator\Exception\NoDomainPart;
use Egulias\EmailValidator\Exception\NoLocalPart;
use Egulias\EmailValidator\Exception\UnclosedComment;
use Egulias\EmailValidator\Exception\UnclosedQuotedString;
use Egulias\EmailValidator\Exception\UnopenedComment;
use Egulias\EmailValidator\Warning\AddressLiteral;
use Egulias\EmailValidator\Warning\CFWSNearAt;
use Egulias\EmailValidator\Warning\CFWSWithFWS;
use Egulias\EmailValidator\Warning\Comment;
use Egulias\EmailValidator\Warning\DomainLiteral;
use Egulias\EmailValidator\Warning\DomainTooLong;
use Egulias\EmailValidator\Warning\IPV6BadChar;
use Egulias\EmailValidator\Warning\IPV6ColonEnd;
use Egulias\EmailValidator\Warning\IPV6ColonStart;
use Egulias\EmailValidator\Warning\IPV6Deprecated;
use Egulias\EmailValidator\Warning\IPV6DoubleColon;
use Egulias\EmailValidator\Warning\IPV6GroupCount;
use Egulias\EmailValidator\Warning\IPV6MaxGroups;
use Egulias\EmailValidator\Warning\LabelTooLong;
use Egulias\EmailValidator\Warning\LocalTooLong;
use Egulias\EmailValidator\Warning\ObsoleteDTEXT;
use Egulias\EmailValidator\Warning\QuotedString;
use PHPUnit\Framework\TestCase;

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

    protected function setUp()
    {
        $this->validator = new RFCValidation();
        $this->lexer = new EmailLexer();
    }

    protected function tearDown()
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

    public function getValidEmails()
    {
        return array(
            ['â@iana.org'],
            ['fabien@symfony.com'],
            ['example@example.co.uk'],
            ['fabien_potencier@example.fr'],
            ['example@localhost'],
            ['fab\'ien@symfony.com'],
            ['fab\ ien@symfony.com'],
            ['example((example))@fakedfake.co.uk'],
            ['example@faked(fake).co.uk'],
            ['fabien+@symfony.com'],
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
            ['test@email*'],
            ['test@email!'],
            ['test@email&'],
            ['test@email^'],
            ['test@email%'],
            ['test@email$'],
            ["1500111@профи-инвест.рф"],
        );
    }

    public function testInvalidUTF8Email()
    {
        $email     = "\x80\x81\x82@\x83\x84\x85.\x86\x87\x88";
        $this->assertFalse($this->validator->isValid($email, $this->lexer));
    }

    /**
     * @dataProvider getInvalidEmails
     */
    public function testInvalidEmails($email)
    {
        $this->assertFalse($this->validator->isValid($email, $this->lexer));
    }

    public function getInvalidEmails()
    {
        return [
            ['test@example.com test'],
            ['user  name@example.com'],
            ['user   name@example.com'],
            ['example.@example.co.uk'],
            ['example@example@example.co.uk'],
            ['(test_exampel@example.fr]'],
            ['example(example]example@example.co.uk'],
            ['.example@localhost'],
            ['ex\ample@localhost'],
            ['example@local\host'],
            ['example@localhost\\'],
            ['example@localhost.'],
            ['user name@example.com'],
            ['username@ example . com'],
            ['example@(fake].com'],
            ['example@(fake.com'],
            ['username@example,com'],
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
            ['test@' . chr(226) . '.org'],
            ['\r\ntest@iana.org'],
            ['\r\n test@iana.org'],
            ['\r\n \r\ntest@iana.org'],
            ['\r\n \r\ntest@iana.org'],
            ['\r\n \r\n test@iana.org'],
            ['test@iana.org \r\n'],
            ['test@iana.org \r\n '],
            ['test@iana.org \r\n \r\n'],
            ['test@iana.org \r\n\r\n'],
            ['test@iana.org  \r\n\r\n '],
            ['test@iana/icann.org'],
            ['test@foo;bar.com'],
            ['test;123@foobar.com'],
            ['test@example..com'],
            ['email.email@email."'],
            ['test@email>'],
            ['test@email<'],
            ['test@email{'],
        ];
    }

    /**
     * @dataProvider getInvalidEmailsWithErrors
     */
    public function testInvalidEmailsWithErrorsCheck($error, $email)
    {
        $this->assertFalse($this->validator->isValid($email, $this->lexer));
        $this->assertEquals($error, $this->validator->getError());
    }

    public function getInvalidEmailsWithErrors()
    {
        return [
            [new NoLocalPart(), '@example.co.uk'],
            [new NoDomainPart(), 'example@'],
            [new DomainHyphened(), 'example@example-.co.uk'],
            [new DomainHyphened(), 'example@example-'],
            [new ConsecutiveAt(), 'example@@example.co.uk'],
            [new ConsecutiveDot(), 'example..example@example.co.uk'],
            [new ConsecutiveDot(), 'example@example..co.uk'],
            [new ExpectingATEXT(), '<example_example>@example.fr'],
            [new DotAtStart(), '.example@localhost'],
            [new DotAtStart(), 'example@.localhost'],
            [new DotAtEnd(), 'example@localhost.'],
            [new DotAtEnd(), 'example.@example.co.uk'],
            [new UnclosedComment(), '(example@localhost'],
            [new UnclosedQuotedString(), '"example@localhost'],
            [new ExpectingATEXT(), 'exa"mple@localhost'],
            [new UnclosedComment(), '(example@localhost'],
            [new UnopenedComment(), 'comment)example@localhost'],
            [new UnopenedComment(), 'example(comment))@localhost'],
            [new UnopenedComment(), 'example@comment)localhost'],
            [new UnopenedComment(), 'example@localhost(comment))'],
            [new UnopenedComment(), 'example@(comment))example.com'],
            //This was the original. But atext is not allowed after \n
            //array(EmailValidator::ERR_EXPECTING_ATEXT, "exampl\ne@example.co.uk"),
            [new AtextAfterCFWS(), "exampl\ne@example.co.uk"],
            [new ExpectingDTEXT(), "example@[[]"],
            [new AtextAfterCFWS(), "exampl\te@example.co.uk"],
            [new CRNoLF(), "example@exa\rmple.co.uk"],
            [new CRNoLF(), "example@[\r]"],
            [new CRNoLF(), "exam\rple@example.co.uk"],
        ];
    }

    /**
     * @dataProvider getInvalidEmailsWithWarnings
     */
    public function testInvalidEmailsWithWarningsCheck($expectedWarnings, $email)
    {
        $this->assertTrue($this->validator->isValid($email, $this->lexer));
        $warnings = $this->validator->getWarnings();
        $this->assertTrue(
            count($warnings) === count($expectedWarnings),
            "Expected: " . implode(",", $expectedWarnings) . " and got " . implode(",", $warnings)
        );

        foreach ($warnings as $warning) {
            $this->assertTrue(isset($expectedWarnings[$warning->code()]));
        }
    }

    public function getInvalidEmailsWithWarnings()
    {
        return [
            [[CFWSNearAt::CODE], 'example @invalid.example.com'],
            [[CFWSNearAt::CODE], 'example@ invalid.example.com'],
            [[Comment::CODE], 'example@invalid.example(examplecomment).com'],
            [[Comment::CODE,CFWSNearAt::CODE], 'example(examplecomment)@invalid.example.com'],
            [[QuotedString::CODE, CFWSWithFWS::CODE,], "\"\t\"@invalid.example.com"],
            [[QuotedString::CODE, CFWSWithFWS::CODE,], "\"\r\"@invalid.example.com"],
            [[AddressLiteral::CODE,], 'example@[127.0.0.1]'],
            [[AddressLiteral::CODE,], 'example@[IPv6:2001:0db8:85a3:0000:0000:8a2e:0370:7334]'],
            [[AddressLiteral::CODE, IPV6Deprecated::CODE], 'example@[IPv6:2001:0db8:85a3:0000:0000:8a2e:0370::]'],
            [[AddressLiteral::CODE, IPV6MaxGroups::CODE,], 'example@[IPv6:2001:0db8:85a3:0000:0000:8a2e:0370:7334::]'],
            [[AddressLiteral::CODE, IPV6DoubleColon::CODE,], 'example@[IPv6:1::1::1]'],
            [[ObsoleteDTEXT::CODE, DomainLiteral::CODE,], "example@[\n]"],
            [[DomainLiteral::CODE,], 'example@[::1]'],
            [[DomainLiteral::CODE,], 'example@[::123.45.67.178]'],
            [
                [IPV6ColonStart::CODE, AddressLiteral::CODE, IPV6GroupCount::CODE,],
                'example@[IPv6::2001:0db8:85a3:0000:0000:8a2e:0370:7334]'
            ],
            [
                [AddressLiteral::CODE, IPV6BadChar::CODE,],
                'example@[IPv6:z001:0db8:85a3:0000:0000:8a2e:0370:7334]'
            ],
            [
                [AddressLiteral::CODE, IPV6ColonEnd::CODE,],
                'example@[IPv6:2001:0db8:85a3:0000:0000:8a2e:0370:]'
            ],
            [[QuotedString::CODE,], '"example"@invalid.example.com'],
            [
                [LocalTooLong::CODE,],
                'too_long_localpart_too_long_localpart_too_long_localpart_too_long_localpart@invalid.example.com'
            ],
            [
                [LabelTooLong::CODE,],
                'example@toolonglocalparttoolonglocalparttoolonglocalparttoolonglocalpart.co.uk'
            ],
            [
                [DomainTooLong::CODE, LabelTooLong::CODE,],
                'example2@toolonglocalparttoolonglocalparttoolonglocalparttoolonglocalparttoolonglocalparttoolonglocal'.
                'parttoolonglocalparttoolonglocalparttoolonglocalparttoolonglocalparttoolonglocalparttoolonglocalpart'.
                'toolonglocalparttoolonglocalparttoolonglocalparttoolonglocalpart'
            ],
            [
                [DomainTooLong::CODE, LabelTooLong::CODE,],
                'example@toolonglocalparttoolonglocalparttoolonglocalparttoolonglocalparttoolonglocalparttoolonglocal'.
                'parttoolonglocalparttoolonglocalparttoolonglocalparttoolonglocalparttoolonglocalparttoolonglocalpart'.
                'toolonglocalparttoolonglocalparttoolonglocalparttoolonglocalpar'
            ],
        ];
    }
}
