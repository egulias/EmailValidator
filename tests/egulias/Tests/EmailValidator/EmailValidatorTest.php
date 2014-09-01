<?php

namespace Egulias\Tests\EmailValidator;

use Egulias\EmailValidator\EmailValidator;

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

    public function getValidEmails()
    {
        return array(
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
            array('example.@example.co.uk'),
            array('example@example@example.co.uk'),
            array('(fabien_potencier@example.fr)'),
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
            array(EmailValidator::ERR_NOLOCALPART, '@example.co.uk'),
            array(EmailValidator::ERR_NODOMAIN, 'example@'),
            array(EmailValidator::ERR_DOMAINHYPHENEND, 'example@example-.co.uk'),
            array(EmailValidator::ERR_DOMAINHYPHENEND, 'example@example-'),
            array(EmailValidator::ERR_CONSECUTIVEATS, 'example@@example.co.uk'),
            array(EmailValidator::ERR_CONSECUTIVEDOTS, 'example..example@example.co.uk'),
            array(EmailValidator::ERR_CONSECUTIVEDOTS, 'example@example..co.uk'),
            array(EmailValidator::ERR_EXPECTING_ATEXT, '<fabien_potencier>@example.fr'),
            array(EmailValidator::ERR_DOT_START, '.example@localhost'),
            array(EmailValidator::ERR_DOT_START, 'example@.localhost'),
            array(EmailValidator::ERR_DOT_END, 'example@localhost.'),
            array(EmailValidator::ERR_DOT_END, 'example.@example.co.uk'),
            array(EmailValidator::ERR_UNCLOSEDCOMMENT, '(example@localhost'),
            array(EmailValidator::ERR_UNCLOSEDQUOTEDSTR, '"example@localhost'),
            array(EmailValidator::ERR_EXPECTING_ATEXT, 'exa"mple@localhost'),
            //This was the original. But atext is not allowed after \n
            //array(EmailValidator::ERR_EXPECTING_ATEXT, "exampl\ne@example.co.uk"),
            array(EmailValidator::ERR_ATEXT_AFTER_CFWS, "exampl\ne@example.co.uk"),
            array(EmailValidator::ERR_EXPECTING_DTEXT, "example@[[]"),
            array(EmailValidator::ERR_ATEXT_AFTER_CFWS, "exampl\te@example.co.uk"),
            array(EmailValidator::ERR_CR_NO_LF, "example@exa\rmple.co.uk"),
            array(EmailValidator::ERR_CR_NO_LF, "example@[\r]"),
            array(EmailValidator::ERR_CR_NO_LF, "exam\rple@example.co.uk"),
        );
    }

    /**
     * @dataProvider getInvalidEmailsWithWarnings
     */
    public function testValidEmailsWithWarningsCheck($warnings, $email)
    {
        $this->assertTrue($this->validator->isValid($email, true));

        $this->assertEquals($warnings, $this->validator->getWarnings());
    }

    public function getInvalidEmailsWithWarnings()
    {
        return array(
            array(array( EmailValidator::DEPREC_CFWS_NEAR_AT,), 'example @example.co.uk'),
            array(array( EmailValidator::DEPREC_CFWS_NEAR_AT,), 'example@ example.co.uk'),
            array(array( EmailValidator::CFWS_COMMENT,), 'example@example(examplecomment).co.uk'),
            array(
                array(
                    EmailValidator::CFWS_COMMENT,
                    EmailValidator::DEPREC_CFWS_NEAR_AT,
                ),
                'example(examplecomment)@example.co.uk'
            ),
            array(
                array(
                    EmailValidator::RFC5321_QUOTEDSTRING,
                    EmailValidator::CFWS_FWS,
                ),
                "\"\t\"@example.co.uk"
            ),
            array(
                array(
                    EmailValidator::RFC5321_QUOTEDSTRING,
                    EmailValidator::CFWS_FWS,
                ),
                "\"\r\"@example.co.uk"
            ),
            array(
                array(
                    EmailValidator::RFC5321_ADDRESSLITERAL,
                    EmailValidator::DNSWARN_NO_RECORD,
                ),
                'example@[127.0.0.1]'
            ),
            array(
                array(
                    EmailValidator::RFC5321_ADDRESSLITERAL,
                    EmailValidator::DNSWARN_NO_RECORD,
                ),
                'example@[IPv6:2001:0db8:85a3:0000:0000:8a2e:0370:7334]'
            ),
            array(
                array(
                    EmailValidator::RFC5321_ADDRESSLITERAL,
                    EmailValidator::RFC5321_IPV6DEPRECATED,
                    EmailValidator::DNSWARN_NO_RECORD,
                ),
                'example@[IPv6:2001:0db8:85a3:0000:0000:8a2e:0370::]'
            ),
            array(
                array(
                    EmailValidator::RFC5321_ADDRESSLITERAL,
                    EmailValidator::RFC5322_IPV6_MAXGRPS,
                    EmailValidator::DNSWARN_NO_RECORD,
                ),
                'example@[IPv6:2001:0db8:85a3:0000:0000:8a2e:0370:7334::]'
            ),
            array(
                array(
                    EmailValidator::RFC5321_ADDRESSLITERAL,
                    EmailValidator::RFC5322_IPV6_2X2XCOLON,
                    EmailValidator::DNSWARN_NO_RECORD,
                ),
                'example@[IPv6:1::1::1]'
            ),
            array(
                array(
                    EmailValidator::RFC5322_DOMLIT_OBSDTEXT,
                    EmailValidator::RFC5322_DOMAINLITERAL,
                    EmailValidator::DNSWARN_NO_RECORD,
                ),
                "example@[\n]"
            ),
            array(
                array(
                    EmailValidator::RFC5322_DOMAINLITERAL,
                    EmailValidator::DNSWARN_NO_RECORD,
                ),
                'example@[::1]'
            ),
            array(
                array(
                    EmailValidator::RFC5322_DOMAINLITERAL,
                    EmailValidator::DNSWARN_NO_RECORD,
                ),
                'example@[::123.45.67.178]'
            ),
            array(
                array(
                    EmailValidator::RFC5322_IPV6_COLONSTRT,
                    EmailValidator::RFC5321_ADDRESSLITERAL,
                    EmailValidator::RFC5322_IPV6_GRPCOUNT,
                    EmailValidator::DNSWARN_NO_RECORD,
                ),
                'example@[IPv6::2001:0db8:85a3:0000:0000:8a2e:0370:7334]'
            ),
            array(
                array(
                    EmailValidator::RFC5321_ADDRESSLITERAL,
                    EmailValidator::RFC5322_IPV6_BADCHAR,
                    EmailValidator::DNSWARN_NO_RECORD,
                ),
                'example@[IPv6:z001:0db8:85a3:0000:0000:8a2e:0370:7334]'
            ),
            array(
                array(
                    EmailValidator::RFC5321_ADDRESSLITERAL,
                    EmailValidator::RFC5322_IPV6_COLONEND,
                    EmailValidator::DNSWARN_NO_RECORD,
                ),
                'example@[IPv6:2001:0db8:85a3:0000:0000:8a2e:0370:]'
            ),
            array(
                array(
                    EmailValidator::RFC5321_QUOTEDSTRING,
                ),
                '"example"@example.co.uk'
            ),
            array(
                array(
                    EmailValidator::RFC5322_LOCAL_TOOLONG,
                ),
                'too_long_localpart_too_long_localpart_too_long_localpart_too_long_localpart@example.co.uk'
            ),
            array(
                array(
                    EmailValidator::RFC5322_LABEL_TOOLONG,
                    EmailValidator::DNSWARN_NO_RECORD,
                ),
                'example@toolonglocalparttoolonglocalparttoolonglocalparttoolonglocalpart.co.uk'
            ),
            array(
                array(
                    EmailValidator::RFC5322_DOMAIN_TOOLONG,
                    EmailValidator::RFC5322_TOOLONG,
                    EmailValidator::DNSWARN_NO_RECORD,
                ),
                'example@toolonglocalparttoolonglocalparttoolonglocalparttoolonglocalparttoolonglocalparttoolonglocal'.
                'parttoolonglocalparttoolonglocalparttoolonglocalparttoolonglocalparttoolonglocalparttoolonglocalpart'.
                'toolonglocalparttoolonglocalparttoolonglocalparttoolonglocalpart'
            ),
            array(
                array(
                    EmailValidator::RFC5322_DOMAIN_TOOLONG,
                    EmailValidator::RFC5322_TOOLONG,
                    EmailValidator::DNSWARN_NO_RECORD,
                ),
                'example@toolonglocalparttoolonglocalparttoolonglocalparttoolonglocalparttoolonglocalparttoolonglocal'.
                'parttoolonglocalparttoolonglocalparttoolonglocalparttoolonglocalparttoolonglocalparttoolonglocalpart'.
                'toolonglocalparttoolonglocalparttoolonglocalparttoolonglocalpar'
            ),
        );
    }
}
