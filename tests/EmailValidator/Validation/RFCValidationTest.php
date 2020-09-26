<?php

namespace Egulias\EmailValidator\Tests\EmailValidator\Validation;

use PHPUnit\Framework\TestCase;
use Egulias\EmailValidator\EmailLexer;
use Egulias\EmailValidator\Warning\Comment;
use Egulias\EmailValidator\Warning\CFWSNearAt;
use Egulias\EmailValidator\Result\InvalidEmail;
use Egulias\EmailValidator\Warning\CFWSWithFWS;
use Egulias\EmailValidator\Warning\IPV6BadChar;
use Egulias\EmailValidator\Warning\IPV6ColonEnd;
use Egulias\EmailValidator\Warning\LabelTooLong;
use Egulias\EmailValidator\Warning\LocalTooLong;
use Egulias\EmailValidator\Warning\QuotedString;
use Egulias\EmailValidator\Warning\DomainLiteral;
use Egulias\EmailValidator\Warning\DomainTooLong;
use Egulias\EmailValidator\Warning\IPV6MaxGroups;
use Egulias\EmailValidator\Warning\ObsoleteDTEXT;
use Egulias\EmailValidator\Warning\AddressLiteral;
use Egulias\EmailValidator\Warning\IPV6ColonStart;
use Egulias\EmailValidator\Warning\IPV6Deprecated;
use Egulias\EmailValidator\Warning\IPV6GroupCount;
use Egulias\EmailValidator\Warning\IPV6DoubleColon;
use Egulias\EmailValidator\Validation\RFCValidation;
use Egulias\EmailValidator\Result\Reason\NoLocalPart;
use Egulias\EmailValidator\Result\Reason\AtextAfterCFWS;
use Egulias\EmailValidator\Result\Reason\CommaInDomain;
use Egulias\EmailValidator\Result\Reason\ConsecutiveAt;
use Egulias\EmailValidator\Result\Reason\UnOpenedComment;
use Egulias\EmailValidator\Result\Reason\UnclosedQuotedString;
use Egulias\EmailValidator\Result\Reason\CRNoLF;
use Egulias\EmailValidator\Result\Reason\DotAtEnd;
use Egulias\EmailValidator\Result\Reason\DotAtStart;
use Egulias\EmailValidator\Result\Reason\NoDomainPart;
use Egulias\EmailValidator\Result\Reason\ConsecutiveDot;
use Egulias\EmailValidator\Result\Reason\DomainHyphened;
use Egulias\EmailValidator\Result\Reason\ExpectingATEXT;
use Egulias\EmailValidator\Result\Reason\ExpectingDTEXT;
use Egulias\EmailValidator\Result\Reason\UnclosedComment;

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
            ['validipv6@[IPv6:2001:db8:1ff::a0b:dbd0]'],
            ['validipv4@[127.0.0.0]'],
            ['validipv4@127.0.0.0'],
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

    public function getValidEmailsWithWarnings()
    {
        return [
            ['a5aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa@example.com', [new LocalTooLong()]]
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
            ['username@examp,le.com'],
            ['test@ '],
            ['validipv4@[127.\0.0.0]'],
            ['test@example.com []'],
            ['test@example.com. []'],
            ['test@test. example.com'],
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
            [new InvalidEmail(new NoLocalPart(), "@"), '@example.co.uk'],
            [new InvalidEmail(new NoDomainPart(), ''), 'example@'],
            [new InvalidEmail(new DomainHyphened('Hypen found near DOT'), '-'), 'example@example-.co.uk'], [new InvalidEmail(new CRNoLF(), "\r"), "example@example\r.com"],
            [new InvalidEmail(new DomainHyphened('Hypen found at the end of the domain'), '-'), 'example@example-'],
            [new InvalidEmail(new ConsecutiveAt(), '@'), 'example@@example.co.uk'],
            [new InvalidEmail(new ConsecutiveDot(), '.'), 'example..example@example.co.uk'],
            [new InvalidEmail(new ConsecutiveDot(), '.'), 'example@example..co.uk'],
            [new InvalidEmail(new ExpectingATEXT('Invalid token found'), '<'), '<example_example>@example.fr'],
            [new InvalidEmail(new DotAtStart(), '.'), '.example@localhost'],
            [new InvalidEmail(new DotAtStart(), '.'), 'example@.localhost'],
            [new InvalidEmail(new DomainHyphened('After AT'), '-'), 'example@-localhost'],
            [new InvalidEmail(new DotAtEnd(), ''), 'example@localhost.'],
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
            [new InvalidEmail(new UnOpenedComment(), ')'), 'example@comment)localhost'],
            [new InvalidEmail(new UnOpenedComment(), ')'), 'example@localhost(comment))'],
            [new InvalidEmail(new UnOpenedComment(), 'com'), 'example@(comment))example.com'],
            //This was the original. But atext is not allowed after \n
            //array(EmailValidator::ERR_EXPECTING_ATEXT, "exampl\ne@example.co.uk"),
            [new InvalidEmail(new AtextAfterCFWS(), "\n"), "exampl\ne@example.co.uk"],
            [new InvalidEmail(new ExpectingDTEXT(), '['), "example@[[]"],
            [new InvalidEmail(new AtextAfterCFWS(), "\t"), "exampl\te@example.co.uk"],
            [new InvalidEmail(new CRNoLF(), "\r"), "example@exa\rmple.co.uk"],
            [new InvalidEmail(new CRNoLF(), "["), "example@[\r]"],
            [new InvalidEmail(new CRNoLF(), "\r"), "exam\rple@example.co.uk"],
            [new InvalidEmail(new CommaInDomain(), ','), 'example@exam,ple.com'],
        ];
    }

    /**
     * @dataProvider getInvalidEmailsWithWarnings
     */
    public function testInvalidEmailsWithWarningsCheck($expectedWarnings, $email)
    {
        $this->assertTrue($this->validator->isValid($email, $this->lexer));
        $warnings = $this->validator->getWarnings();
        $this->assertCount(
            count($expectedWarnings), $warnings,
            "Expected: " . implode(",", $expectedWarnings) . " and got: " . PHP_EOL . implode(PHP_EOL, $warnings)
        );

        foreach ($warnings as $warning) {
            $this->assertArrayHasKey($warning->code(), $expectedWarnings);
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
                [LocalTooLong::CODE],
                'too_long_localpart_too_long_localpart_123_too_long_localpart_too_long_localpart@example.com'
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
