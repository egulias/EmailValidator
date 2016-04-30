<?php

namespace Egulias\Tests\EmailValidator\Validation;

use Egulias\EmailValidator\EmailLexer;
use Egulias\EmailValidator\Validation\NoRFCWarningsValidation;

class NoWarningsRFCValidationTest extends \PHPUnit_Framework_TestCase
{
    public function testEmailWithWarningsIsInvalid()
    {
        $validation = new NoRFCWarningsValidation();
        
        $this->assertFalse($validation->isValid('examp"l"e@example.com', new EmailLexer()));
    }
    
    public function testEmailWithoutWarningsIsValid()
    {
        $validation = new NoRFCWarningsValidation();

        $this->assertTrue($validation->isValid('example@example.com', new EmailLexer()));
    }
}
