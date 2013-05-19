<?php

namespace tests;

require_once 'EmailValidator.php';


class EmailValidatorTest extends \PHPUnit_Framework_TestCase
{
    protected $validator;

    protected function setUp()
    {
        $this->validator = new \EmailValidator\EmailValidator();
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
            array('example((example))@fakedfake.co.uk'),
        );
    }

    /**
     * @dataProvider getExistingEmails
     */
    public function testValidExistingEmails($email)
    {
        $this->assertTrue($this->validator->isValid($email, true));
    }

    public function getExistingEmails()
    {
        return array(
            array('fabien@symfony.com'),
            array('stloyd@o2.pl'),
            array('ominic@sayers.cc'),
        );
    }

    /**
     * @dataProvider getNonexistingEmails
     */
    public function testValidNonexistingEmails($email)
    {
        $this->assertTrue($this->validator->isValid($email, true));
    }

    /**
     * @dataProvider getNonexistingEmails
     */
    public function testValidNonexistingEmailsWithStrict($email)
    {
        $this->assertFalse($this->validator->isValid($email, true, true));
    }

    public function getNonexistingEmails()
    {
        return array(
            array('example@fakedfake.co.uk'),
            array('fabien_potencier@non-existing.fr'),
            array('example@localhost'),
        );
    }

    /**
     * @dataProvider getInvalidEmails
     */
    public function testTInvalidEmails($email)
    {
        $this->assertFalse($this->validator->isValid($email));
    }

    public function getInvalidEmails()
    {
        return array(
            array('example.@example.co.uk'),
            array('(fabien_potencier@example.fr)'),
            array('example(example)example@example.co.uk'),
            array('.example@localhost'),
            array('ex\ample@localhost'),
            array('example@local\host'),
            array('example@localhost.'),
        );
    }

    /**
     * @dataProvider getInvalidEmailsWithErrors
     */
    public function testInvalidEmailsWithErrorsCheck($errors, $email)
    {
        $this->assertFalse($this->validator->isValid($email));

        $this->assertEquals($errors, $this->validator->getErrors());
    }

    public function getInvalidEmailsWithErrors()
    {
        return array(
            array(array(\EmailValidator\EmailValidator::ERR_NOLOCALPART), '@example.co.uk'),
            array(array(\EmailValidator\EmailValidator::ERR_NODOMAIN), 'example@'),
            array(array(\EmailValidator\EmailValidator::ERR_DOMAINHYPHENEND), 'example@example-.co.uk'),
            array(array(\EmailValidator\EmailValidator::ERR_DOMAINHYPHENEND), 'example@example-'),
            array(array(\EmailValidator\EmailValidator::ERR_CONSECUTIVEATS), 'example@@example.co.uk'),
            array(array(\EmailValidator\EmailValidator::ERR_CONSECUTIVEDOTS), 'example..example@example.co.uk'),
            array(array(\EmailValidator\EmailValidator::ERR_CONSECUTIVEDOTS), 'example@example..co.uk'),
            array(array(\EmailValidator\EmailValidator::ERR_EXPECTING_ATEXT), '<fabien_potencier>@example.fr'),
            array(array(\EmailValidator\EmailValidator::ERR_DOT_START), '.example@localhost'),
            array(array(\EmailValidator\EmailValidator::ERR_DOT_START), 'example@.localhost'),
            array(array(\EmailValidator\EmailValidator::ERR_DOT_END), 'example@localhost.'),
            array(array(\EmailValidator\EmailValidator::ERR_DOT_END), 'example.@example.co.uk'),
            array(array(\EmailValidator\EmailValidator::ERR_UNCLOSEDCOMMENT), '(example@localhost'),
            array(array(\EmailValidator\EmailValidator::ERR_UNCLOSEDQUOTEDSTR), '"example@localhost'),
            array(array(\EmailValidator\EmailValidator::ERR_EXPECTING_ATEXT), 'exa"mple@localhost'),
            array(array(\EmailValidator\EmailValidator::ERR_EXPECTING_ATEXT), "exampl\ne@example.co.uk"),
            array(array(\EmailValidator\EmailValidator::ERR_EXPECTING_DTEXT), "example@[[]"),
            array(array(\EmailValidator\EmailValidator::ERR_ATEXT_AFTER_CFWS), "exampl\te@example.co.uk"),
            array(array(\EmailValidator\EmailValidator::ERR_CR_NO_LF), "example@exa\rmple.co.uk"),
            array(array(\EmailValidator\EmailValidator::ERR_CR_NO_LF), "example@[\r]"),
            array(array(\EmailValidator\EmailValidator::ERR_CR_NO_LF), "exam\rple@example.co.uk"),
            array(array(\EmailValidator\EmailValidator::ERR_CR_NO_LF), "\"\r\"@localhost"),
        );
    }

    /**
     * @dataProvider getInvalidEmailsWithWarnings
     */
    public function testInvalidEmailsWithWarningsCheck($warnings, $email)
    {
        $this->assertTrue($this->validator->isValid($email, true));

        $this->assertEquals($warnings, $this->validator->getWarnings());
    }

    public function getInvalidEmailsWithWarnings()
    {
        return array(
            array(
                array(
                    \EmailValidator\EmailValidator::DEPREC_CFWS_NEAR_AT,
                ),
                'example @example.co.uk'
            ),
            array(
                array(
                    \EmailValidator\EmailValidator::DEPREC_CFWS_NEAR_AT,
                ),
                'example@ example.co.uk'
            ),
            array(
                array(
                    \EmailValidator\EmailValidator::CFWS_COMMENT,
                ),
                'example@example(example).co.uk'
            ),
            array(
                array(
                    \EmailValidator\EmailValidator::CFWS_COMMENT,
                    \EmailValidator\EmailValidator::DEPREC_CFWS_NEAR_AT,
                ),
                'example(example)@example.co.uk'
            ),

            array(
                array(
                    \EmailValidator\EmailValidator::RFC5321_QUOTEDSTRING,
                    \EmailValidator\EmailValidator::CFWS_FWS,
                ),
                "\"\t\"@example.co.uk"
            ),
            array(
                array(
                    \EmailValidator\EmailValidator::RFC5321_ADDRESSLITERAL,
                    \EmailValidator\EmailValidator::DNSWARN_NO_MX_RECORD,
                    \EmailValidator\EmailValidator::DNSWARN_NO_RECORD,
                ),
                'example@[127.0.0.1]'
            ),
            array(
                array(
                    \EmailValidator\EmailValidator::RFC5321_ADDRESSLITERAL,
                    \EmailValidator\EmailValidator::DNSWARN_NO_MX_RECORD,
                    \EmailValidator\EmailValidator::DNSWARN_NO_RECORD,
                ),
                'example@[IPv6:2001:0db8:85a3:0000:0000:8a2e:0370:7334]'
            ),
            array(
                array(
                    \EmailValidator\EmailValidator::RFC5321_IPV6DEPRECATED,
                    \EmailValidator\EmailValidator::RFC5321_ADDRESSLITERAL,
                    \EmailValidator\EmailValidator::DNSWARN_NO_MX_RECORD,
                    \EmailValidator\EmailValidator::DNSWARN_NO_RECORD,
                ),
                'example@[IPv6:2001:0db8:85a3:0000:0000:8a2e:0370::]'
            ),
            array(
                array(
                    \EmailValidator\EmailValidator::RFC5322_IPV6_MAXGRPS,
                    \EmailValidator\EmailValidator::RFC5321_ADDRESSLITERAL,
                    \EmailValidator\EmailValidator::DNSWARN_NO_MX_RECORD,
                    \EmailValidator\EmailValidator::DNSWARN_NO_RECORD,
                ),
                'example@[IPv6:2001:0db8:85a3:0000:0000:8a2e:0370:7334::]'
            ),
            array(
                array(
                    \EmailValidator\EmailValidator::RFC5322_IPV6_2X2XCOLON,
                    \EmailValidator\EmailValidator::RFC5321_ADDRESSLITERAL,
                    \EmailValidator\EmailValidator::DNSWARN_NO_MX_RECORD,
                    \EmailValidator\EmailValidator::DNSWARN_NO_RECORD,
                ),
                'example@[IPv6:1::1::1]'
            ),

            array(
                array(
                    \EmailValidator\EmailValidator::RFC5322_DOMLIT_OBSDTEXT,
                    \EmailValidator\EmailValidator::RFC5322_DOMAINLITERAL,
                    \EmailValidator\EmailValidator::DNSWARN_NO_MX_RECORD,
                    \EmailValidator\EmailValidator::DNSWARN_NO_RECORD,
                ),
                "example@[\n]"
            ),
            array(
                array(
                    \EmailValidator\EmailValidator::RFC5322_DOMAINLITERAL,
                    \EmailValidator\EmailValidator::DNSWARN_NO_MX_RECORD,
                    \EmailValidator\EmailValidator::DNSWARN_NO_RECORD,
                ),
                'example@[::1]'
            ),
            array(
                array(
                    \EmailValidator\EmailValidator::RFC5322_DOMAINLITERAL,
                    \EmailValidator\EmailValidator::DNSWARN_NO_MX_RECORD,
                    \EmailValidator\EmailValidator::DNSWARN_NO_RECORD,
                ),
                'example@[::123.45.67.178]'
            ),
            array(
                array(
                    \EmailValidator\EmailValidator::RFC5322_IPV6_GRPCOUNT,
                    \EmailValidator\EmailValidator::RFC5322_IPV6_COLONSTRT,
                    \EmailValidator\EmailValidator::DNSWARN_NO_MX_RECORD,
                    \EmailValidator\EmailValidator::DNSWARN_NO_RECORD,
                ),
                'example@[IPv6::2001:0db8:85a3:0000:0000:8a2e:0370:7334]'
            ),
            array(
                array(
                    \EmailValidator\EmailValidator::RFC5322_IPV6_BADCHAR,
                    \EmailValidator\EmailValidator::DNSWARN_NO_MX_RECORD,
                    \EmailValidator\EmailValidator::DNSWARN_NO_RECORD,
                ),
                'example@[IPv6:z001:0db8:85a3:0000:0000:8a2e:0370:7334]'
            ),
            array(
                array(
                    \EmailValidator\EmailValidator::RFC5322_IPV6_COLONEND,
                    \EmailValidator\EmailValidator::DNSWARN_NO_MX_RECORD,
                    \EmailValidator\EmailValidator::DNSWARN_NO_RECORD,
                ),
                'example@[IPv6:2001:0db8:85a3:0000:0000:8a2e:0370:]'
            ),
            array(
                array(
                    \EmailValidator\EmailValidator::RFC5321_QUOTEDSTRING,
                ),
                '"example"@example.co.uk'
            ),
            array(
                array(
                    \EmailValidator\EmailValidator::RFC5322_LOCAL_TOOLONG,
                ),
                'too_long_localpart_too_long_localpart_too_long_localpart_too_long_localpart@example.co.uk'
            ),
            array(
                array(
                    \EmailValidator\EmailValidator::RFC5322_LABEL_TOOLONG,
                    \EmailValidator\EmailValidator::DNSWARN_NO_MX_RECORD,
                    \EmailValidator\EmailValidator::DNSWARN_NO_RECORD,
                ),
                'example@toolonglocalparttoolonglocalparttoolonglocalparttoolonglocalpart.co.uk'
            ),
            array(
                array(
                    \EmailValidator\EmailValidator::RFC5322_DOMAIN_TOOLONG,
                    \EmailValidator\EmailValidator::DNSWARN_NO_MX_RECORD,
                    \EmailValidator\EmailValidator::DNSWARN_NO_RECORD,
                ),
                'example@toolonglocalparttoolonglocalparttoolonglocalparttoolonglocalparttoolonglocalparttoolonglocalparttoolonglocalparttoolonglocalparttoolonglocalparttoolonglocalparttoolonglocalparttoolonglocalparttoolonglocalparttoolonglocalparttoolonglocalparttoolonglocalpart'
            ),
            array(
                array(
                    \EmailValidator\EmailValidator::RFC5322_TOOLONG,
                    \EmailValidator\EmailValidator::DNSWARN_NO_MX_RECORD,
                    \EmailValidator\EmailValidator::DNSWARN_NO_RECORD,
                ),
                'example@toolonglocalparttoolonglocalparttoolonglocalparttoolonglocalparttoolonglocalparttoolonglocalparttoolonglocalparttoolonglocalparttoolonglocalparttoolonglocalparttoolonglocalparttoolonglocalparttoolonglocalparttoolonglocalparttoolonglocalparttoolonglocalpar'
            ),
        );
    }
}
