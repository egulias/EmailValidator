<?php

namespace Egulias\EmailValidator\Tests\EmailValidator\Validation;

use PHPUnit\Framework\TestCase;
use Egulias\EmailValidator\EmailLexer;
use Egulias\EmailValidator\Warning\TLD;
use Egulias\EmailValidator\Warning\Comment;
use Egulias\EmailValidator\Result\InvalidEmail;
use Egulias\EmailValidator\Warning\IPV6BadChar;
use Egulias\EmailValidator\Result\Reason\CRNoLF;
use Egulias\EmailValidator\Warning\IPV6ColonEnd;
use Egulias\EmailValidator\Warning\DomainLiteral;
use Egulias\EmailValidator\Warning\IPV6MaxGroups;
use Egulias\EmailValidator\Warning\ObsoleteDTEXT;
use Egulias\EmailValidator\Result\Reason\DotAtEnd;
use Egulias\EmailValidator\Warning\AddressLiteral;
use Egulias\EmailValidator\Warning\IPV6ColonStart;
use Egulias\EmailValidator\Warning\IPV6Deprecated;
use Egulias\EmailValidator\Warning\IPV6GroupCount;
use Egulias\EmailValidator\Warning\IPV6DoubleColon;
use Egulias\EmailValidator\Result\Reason\DotAtStart;
use Egulias\EmailValidator\Validation\RFCValidation;
use Egulias\EmailValidator\Result\Reason\LabelTooLong;
use Egulias\EmailValidator\Result\Reason\NoDomainPart;
use Egulias\EmailValidator\Result\Reason\ConsecutiveAt;
use Egulias\EmailValidator\Result\Reason\ConsecutiveDot;
use Egulias\EmailValidator\Result\Reason\DomainHyphened;
use Egulias\EmailValidator\Result\Reason\ExpectingATEXT;
use Egulias\EmailValidator\Result\Reason\ExpectingDTEXT;
use Egulias\EmailValidator\Result\Reason\UnOpenedComment;


class RFCValidationDomainPartTest extends TestCase
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
            ['fabien@symfony.com'],
            ['example@example.co.uk'],
            ['example@localhost'],
            ['example@faked(fake).co.uk'],
            ['инфо@письмо.рф'],
            ['müller@möller.de'],
            ["1500111@профи-инвест.рф"],
            ['validipv6@[IPv6:2001:db8:1ff::a0b:dbd0]'],
            ['validipv4@[127.0.0.0]'],
            ['validipv4@127.0.0.0'],
            ['withhyphen@domain-exam.com'],
            ['valid_long_domain@71846jnrsoj91yfhc18rkbrf90ue3onl8y46js38kae8inz0t1.5a-xdycuau.na49.le.example.com']
        );
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
            ['test@example.com test'],
            ['example@example@example.co.uk'],
            ['test_exampel@example.fr]'],
            ['example@local\host'],
            ['example@localhost\\'],
            ['example@localhost.'],
            ['username@ example . com'],
            ['username@ example.com'],
            ['example@(fake].com'],
            ['example@(fake.com'],
            ['username@example,com'],
            ['test@' . chr(226) . '.org'],
            ['test@iana.org \r\n'],
            ['test@iana.org \r\n '],
            ['test@iana.org \r\n \r\n'],
            ['test@iana.org \r\n\r\n'],
            ['test@iana.org  \r\n\r\n '],
            ['test@iana/icann.org'],
            ['test@foo;bar.com'],
            ['test@example..com'],
            ["test@examp'le.com"],
            ['email.email@email."'],
            ['test@email>'],
            ['test@email<'],
            ['test@email{'],
            ['username@examp,le.com'],
            ['test@ '],
            ['invalidipv4@[127.\0.0.0]'],
            ['test@example.com []'],
            ['test@example.com. []'],
            ['test@test. example.com'],
            ['example@toolonglocalparttoolonglocalparttoolonglocalparttoolonglocalparttoolonglocalparttoolonglocal'.
            'parttoolonglocalparttoolonglocalparttoolonglocalparttoolonglocalparttoolonglocalparttoolonglocalpart'.
            'toolonglocalparttoolonglocalparttoolonglocalparttoolonglocalpar'],
            ['example@toolonglocalparttoolonglocalparttoolonglocalparttoolonglocalpart.co.uk'],
            ['example@toolonglocalparttoolonglocalparttoolonglocalparttoolonglocalpart.test.co.uk'],
            ['example@test.toolonglocalparttoolonglocalparttoolonglocalparttoolonglocalpart.co.uk'],
            ['test@email*a.com'],
            ['test@email!a.com'],
            ['test@email&a.com'],
            ['test@email^a.com'],
            ['test@email%a.com'],
            ['test@email$a.com'],
            ['test@email`a.com'],
            ['test@email|a.com'],
            ['test@email~a.com'],
            ['test@email{a.com'],
            ['test@email}a.com'],
            ['test@email=a.com'],
            ['test@email+a.com'],
            ['test@email_a.com'],
            ['test@email¡a.com'],
            ['test@email?a.com'],
            ['test@email#a.com'],
            ['test@email¨a.com'],
            ['test@email€a.com'],
            ['test@email$a.com'],
            ['test@email£a.com'],
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

    public static function getInvalidEmailsWithErrors()
    {
        return [
            [new InvalidEmail(new NoDomainPart(), ''), 'example@'],
            [new InvalidEmail(new DomainHyphened('Hypen found near DOT'), '-'), 'example@example-.co.uk'], [new InvalidEmail(new CRNoLF(), "\r"), "example@example\r.com"],
            [new InvalidEmail(new DomainHyphened('Hypen found at the end of the domain'), '-'), 'example@example-'],
            [new InvalidEmail(new ConsecutiveAt(), '@'), 'example@@example.co.uk'],
            [new InvalidEmail(new ConsecutiveDot(), '.'), 'example@example..co.uk'],
            [new InvalidEmail(new DotAtStart(), '.'), 'example@.localhost'],
            [new InvalidEmail(new DomainHyphened('After AT'), '-'), 'example@-localhost'],
            [new InvalidEmail(new DotAtEnd(), ''), 'example@localhost.'],
            [new InvalidEmail(new UnOpenedComment(), ')'), 'example@comment)localhost'],
            [new InvalidEmail(new UnOpenedComment(), ')'), 'example@localhost(comment))'],
            [new InvalidEmail(new UnOpenedComment(), 'com'), 'example@(comment))example.com'],
            [new InvalidEmail(new ExpectingDTEXT(), '['), "example@[[]"],
            [new InvalidEmail(new CRNoLF(), "\r"), "example@exa\rmple.co.uk"],
            [new InvalidEmail(new CRNoLF(), "["), "example@[\r]"],
            [new InvalidEmail(new ExpectingATEXT('Invalid token in domain: ,'), ','), 'example@exam,ple.com'],
            [new InvalidEmail(new ExpectingATEXT("Invalid token in domain: '"), "'"), "test@example.com'"],
            [new InvalidEmail(new LabelTooLong(), "."), sprintf('example@%s.com', str_repeat('ъ', 64))],
            [new InvalidEmail(new LabelTooLong(), "."), sprintf('example@%s.com', str_repeat('a4t', 22))],
            [new InvalidEmail(new LabelTooLong(), ""), sprintf('example@%s', str_repeat('a4t', 22))],
        ];
    }

    /**
     * @dataProvider getValidEmailsWithWarnings
     */
    public function testValidEmailsWithWarningsCheck($expectedWarnings, $email)
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

    public static function getValidEmailsWithWarnings()
    {
        return [
            //Check if this is actually possible
            //[[CFWSNearAt::CODE], 'example@ invalid.example.com'],
            [[Comment::CODE], 'example@invalid.example(examplecomment).com'],
            [[AddressLiteral::CODE, TLD::CODE], 'example@[127.0.0.1]'],
            [[AddressLiteral::CODE, TLD::CODE], 'example@[IPv6:2001:0db8:85a3:0000:0000:8a2e:0370:7334]'],
            [[AddressLiteral::CODE, IPV6Deprecated::CODE, TLD::CODE], 'example@[IPv6:2001:0db8:85a3:0000:0000:8a2e:0370::]'],
            [[AddressLiteral::CODE, IPV6MaxGroups::CODE, TLD::CODE], 'example@[IPv6:2001:0db8:85a3:0000:0000:8a2e:0370:7334::]'],
            [[AddressLiteral::CODE, IPV6DoubleColon::CODE, TLD::CODE], 'example@[IPv6:1::1::1]'],
            [[ObsoleteDTEXT::CODE, DomainLiteral::CODE, TLD::CODE], "example@[\n]"],
            [[DomainLiteral::CODE, TLD::CODE], 'example@[::1]'],
            [[DomainLiteral::CODE, TLD::CODE], 'example@[::123.45.67.178]'],
            [
                [IPV6ColonStart::CODE, AddressLiteral::CODE, IPV6GroupCount::CODE, TLD::CODE],
                'example@[IPv6::2001:0db8:85a3:0000:0000:8a2e:0370:7334]'
            ],
            [
                [AddressLiteral::CODE, IPV6BadChar::CODE, TLD::CODE],
                'example@[IPv6:z001:0db8:85a3:0000:0000:8a2e:0370:7334]'
            ],
            [
                [AddressLiteral::CODE, IPV6ColonEnd::CODE, TLD::CODE],
                'example@[IPv6:2001:0db8:85a3:0000:0000:8a2e:0370:]'
            ],
        ];
    }

    public static function invalidUTF16Chars()
    {
        return [
            ['example@symƒony.com'],
        ];
    }
    
    /**
     * @dataProvider invalidUTF16Chars
     */
    public function testInvalidUTF16($email)
    {
        $this->markTestSkipped('Util finding a way to control this kind of chars');
        $this->assertFalse($this->validator->isValid($email, $this->lexer));
    }

}