<?php

namespace Egulias\Tests\EmailValidator;

use Egulias\EmailValidator\EmailValidator;
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
use Egulias\EmailValidator\Warning\NoDNSRecord;
use Egulias\EmailValidator\Warning\ObsoleteDTEXT;
use Egulias\EmailValidator\Warning\QuotedString;

class EmailValidatorTest extends \PHPUnit_Framework_TestCase
{
    protected $validator;

    protected function setUp()
    {
        $this->validator = new EmailValidator();
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
        $this->assertTrue($this->validator->isValid($email));
    }

    public function testInvalidUTF8Email()
    {
        $validator = new EmailValidator;
        $email     = "\x80\x81\x82@\x83\x84\x85.\x86\x87\x88";

        $this->assertFalse($validator->isValid($email));
    }

    public function getValidEmails()
    {
        return array(
            array('â@iana.org'),
            array('fabien@symfony.com'),
            array('example@example.co.uk'),
            array('fabien_potencier@example.fr'),
            array('example@localhost'),
            array('fab\'ien@symfony.com'),
            array('fab\ ien@symfony.com'),
            array('example((example))@fakedfake.co.uk'),
            array('example@faked(fake).co.uk'),
            array('fabien+@symfony.com'),
            array('инфо@письмо.рф'),
            array('"username"@example.com'),
            array('"user,name"@example.com'),
            array('"user name"@example.com'),
            array('"user@name"@example.com'),
            array('"\a"@iana.org'),
            array('"test\ test"@iana.org'),
            array('""@iana.org'),
            array('"\""@iana.org'),
            array('müller@möller.de'),
            array('test@email*'),
            array('test@email!'),
            array('test@email&'),
            array('test@email^'),
            array('test@email%'),
            array('test@email$'),
        );
    }

    /**
     * @dataProvider getInvalidEmails
     */
    public function testInvalidEmails($email)
    {
        $this->assertFalse($this->validator->isValid($email));
    }

    public function getInvalidEmails()
    {
        return array(
            array('test@example.com test'),
            array('user  name@example.com'),
            array('user   name@example.com'),
            array('example.@example.co.uk'),
            array('example@example@example.co.uk'),
            array('(test_exampel@example.fr)'),
            array('example(example)example@example.co.uk'),
            array('.example@localhost'),
            array('ex\ample@localhost'),
            array('example@local\host'),
            array('example@localhost.'),
            array('user name@example.com'),
            array('username@ example . com'),
            array('example@(fake).com'),
            array('example@(fake.com'),
            array('username@example,com'),
            array('usern,ame@example.com'),
            array('user[na]me@example.com'),
            array('"""@iana.org'),
            array('"\"@iana.org'),
            array('"test"test@iana.org'),
            array('"test""test"@iana.org'),
            array('"test"."test"@iana.org'),
            array('"test".test@iana.org'),
            array('"test"' . chr(0) . '@iana.org'),
            array('"test\"@iana.org'),
            array(chr(226) . '@iana.org'),
            array('test@' . chr(226) . '.org'),
            array('\r\ntest@iana.org'),
            array('\r\n test@iana.org'),
            array('\r\n \r\ntest@iana.org'),
            array('\r\n \r\ntest@iana.org'),
            array('\r\n \r\n test@iana.org'),
            array('test@iana.org \r\n'),
            array('test@iana.org \r\n '),
            array('test@iana.org \r\n \r\n'),
            array('test@iana.org \r\n\r\n'),
            array('test@iana.org  \r\n\r\n '),
            array('test@iana/icann.org'),
            array('test@foo;bar.com'),
            array('test;123@foobar.com'),
            array('test@example..com'),
            array('email.email@email."'),
            array('test@email>'),
            array('test@email<'),
            array('test@email{'),
        );
    }

    /**
     * @dataProvider getInvalidEmailsWithErrors
     */
    public function testInvalidEmailsWithErrorsCheck($errors, $email)
    {
        $this->assertFalse($this->validator->isValid($email));

        $this->assertEquals($errors, $this->validator->getError());
    }

    public function getInvalidEmailsWithErrors()
    {
        return array(
            array(NoLocalPart::CODE, '@example.co.uk'),
            array(NoDomainPart::CODE, 'example@'),
            array(DomainHyphened::CODE, 'example@example-.co.uk'),
            array(DomainHyphened::CODE, 'example@example-'),
            array(ConsecutiveAt::CODE, 'example@@example.co.uk'),
            array(ConsecutiveDot::CODE, 'example..example@example.co.uk'),
            array(ConsecutiveDot::CODE, 'example@example..co.uk'),
            array(ExpectingATEXT::CODE, '<fabien_potencier>@example.fr'),
            array(DotAtStart::CODE, '.example@localhost'),
            array(DotAtStart::CODE, 'example@.localhost'),
            array(DotAtEnd::CODE, 'example@localhost.'),
            array(DotAtEnd::CODE, 'example.@example.co.uk'),
            array(UnclosedComment::CODE, '(example@localhost'),
            array(UnclosedQuotedString::CODE, '"example@localhost'),
            array(ExpectingATEXT::CODE, 'exa"mple@localhost'),
            array(UnclosedComment::CODE, '(example@localhost'),
            array(UnopenedComment::CODE, 'comment)example@localhost'),
            array(UnopenedComment::CODE, 'example(comment))@localhost'),
            array(UnopenedComment::CODE, 'example@comment)localhost'),
            array(UnopenedComment::CODE, 'example@localhost(comment))'),
            array(UnopenedComment::CODE, 'example@(comment))example.com'),
            //This was the original. But atext is not allowed after \n
            //array(EmailValidator::ERR_EXPECTING_ATEXT, "exampl\ne@example.co.uk"),
            array(AtextAfterCFWS::CODE, "exampl\ne@example.co.uk"),
            array(ExpectingDTEXT::CODE, "example@[[]"),
            array(AtextAfterCFWS::CODE, "exampl\te@example.co.uk"),
            array(CRNoLF::CODE, "example@exa\rmple.co.uk"),
            array(CRNoLF::CODE, "example@[\r]"),
            array(CRNoLF::CODE, "exam\rple@example.co.uk"),
        );
    }

    /**
     * @dataProvider getInvalidEmailsWithWarnings
     */
    public function testInvalidEmailsWithWarningsCheck($expectedWarnings, $email)
    {
        $this->assertTrue($this->validator->isValid($email, true));
        $warnings = $this->validator->getWarnings();
        $this->assertTrue(count($warnings) === count($expectedWarnings));

        foreach ($warnings as $warning) {
            $this->assertTrue(isset($expectedWarnings[$warning->code()]));
        }
    }

    /**
     * @dataProvider getInvalidEmailsWithWarnings
     */
    public function testInvalidEmailsWithDnsCheckAndStrictMode($expectedWarnings, $email)
    {
        $this->assertFalse($this->validator->isValid($email, true, true));

        $warnings = $this->validator->getWarnings();
        $this->assertTrue(count($warnings) === count($expectedWarnings));

        foreach ($warnings as $warning) {
            $this->assertTrue(isset($expectedWarnings[$warning->code()]));
        }
    }

    public function getInvalidEmailsWithWarnings()
    {
        return array(
            [
                [CFWSNearAt::CODE, NoDNSRecord::CODE],
                'example @invalid.example.com'
            ],
            [
                [CFWSNearAt::CODE, NoDNSRecord::CODE],
                'example@ invalid.example.com'
            ],
            [
                [Comment::CODE, NoDNSRecord::CODE],
                'example@invalid.example(examplecomment).com'
            ],
            [
                [Comment::CODE, CFWSNearAt::CODE, NoDNSRecord::CODE],
                'example(examplecomment)@invalid.example.com'
            ],
            [
                [QuotedString::CODE, CFWSWithFWS::CODE, NoDNSRecord::CODE],
                "\"\t\"@invalid.example.com"
            ],
            [
                [QuotedString::CODE, CFWSWithFWS::CODE, NoDNSRecord::CODE],
                "\"\r\"@invalid.example.com"
            ],
            [
                [AddressLiteral::CODE, NoDNSRecord::CODE],
                'example@[127.0.0.1]'
            ],
            [
                [AddressLiteral::CODE, NoDNSRecord::CODE],
                'example@[IPv6:2001:0db8:85a3:0000:0000:8a2e:0370:7334]'
            ],
            [
                [AddressLiteral::CODE, IPV6Deprecated::CODE, NoDNSRecord::CODE],
                'example@[IPv6:2001:0db8:85a3:0000:0000:8a2e:0370::]'
            ],
            [
                [AddressLiteral::CODE, IPV6MaxGroups::CODE, NoDNSRecord::CODE],
                'example@[IPv6:2001:0db8:85a3:0000:0000:8a2e:0370:7334::]'
            ],
            [

                [AddressLiteral::CODE, IPV6DoubleColon::CODE, NoDNSRecord::CODE],
                'example@[IPv6:1::1::1]'
            ],
            [
                [ObsoleteDTEXT::CODE, DomainLiteral::CODE, NoDNSRecord::CODE ],
                "example@[\n]"
            ],
            [
                [DomainLiteral::CODE, NoDNSRecord::CODE ],
                'example@[::1]'
            ],
            [
                [DomainLiteral::CODE, NoDNSRecord::CODE ],
                'example@[::123.45.67.178]'
            ],
            [
                [IPV6ColonStart::CODE, AddressLiteral::CODE, IPV6GroupCount::CODE, NoDNSRecord::CODE],
                'example@[IPv6::2001:0db8:85a3:0000:0000:8a2e:0370:7334]'
            ],
            [
                [AddressLiteral::CODE, IPV6BadChar::CODE, NoDNSRecord::CODE],
                'example@[IPv6:z001:0db8:85a3:0000:0000:8a2e:0370:7334]'
            ],
            [
                [AddressLiteral::CODE, IPV6ColonEnd::CODE, NoDNSRecord::CODE],
                'example@[IPv6:2001:0db8:85a3:0000:0000:8a2e:0370:]'
            ],
            [
                [QuotedString::CODE, NoDNSRecord::CODE],
                '"example"@invalid.example.com'
            ],
            [
                [LocalTooLong::CODE, NoDNSRecord::CODE],
                'too_long_localpart_too_long_localpart_too_long_localpart_too_long_localpart@invalid.example.com'
            ],
            [
                [LabelTooLong::CODE, NoDNSRecord::CODE],
                'example@toolonglocalparttoolonglocalparttoolonglocalparttoolonglocalpart.co.uk'
            ],
            [
                [DomainTooLong::CODE, LabelTooLong::CODE, NoDNSRecord::CODE],
                'example2@toolonglocalparttoolonglocalparttoolonglocalparttoolonglocalparttoolonglocalparttoolonglocal'.
                'parttoolonglocalparttoolonglocalparttoolonglocalparttoolonglocalparttoolonglocalparttoolonglocalpart'.
                'toolonglocalparttoolonglocalparttoolonglocalparttoolonglocalpart'
            ],
            [
                [DomainTooLong::CODE, LabelTooLong::CODE, NoDNSRecord::CODE],
                'example@toolonglocalparttoolonglocalparttoolonglocalparttoolonglocalparttoolonglocalparttoolonglocal'.
                'parttoolonglocalparttoolonglocalparttoolonglocalparttoolonglocalparttoolonglocalparttoolonglocalpart'.
                'toolonglocalparttoolonglocalparttoolonglocalparttoolonglocalpar'
            ],
            [
                [NoDNSRecord::CODE],
                'test@test'
            ],
        );
    }

    public function testInvalidEmailsWithStrict()
    {
        $this->assertFalse($this->validator->isValid('"test"@test', false, true));
    }
}
