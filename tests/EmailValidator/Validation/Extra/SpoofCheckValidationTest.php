<?php

namespace Egulias\EmailValidator\Tests\EmailValidator\Validation\Extra;

use Egulias\EmailValidator\EmailLexer;
use Egulias\EmailValidator\Validation\Extra\SpoofCheckValidation;
use PHPUnit\Framework\TestCase;

class SpoofCheckValidationTest extends TestCase
{
    /**
     * @dataProvider validUTF8EmailsProvider
     */
    public function testUTF8EmailAreValid($email)
    {
        $validation = new SpoofCheckValidation();

        $this->assertTrue($validation->isValid($email, new EmailLexer()));
    }

    public function testEmailWithSpoofsIsInvalid()
    {
        $validation = new SpoofCheckValidation();

        $this->assertFalse($validation->isValid("Кириллица"."latin漢字"."ひらがな"."カタカナ", new EmailLexer()));
    }

    public static function validUTF8EmailsProvider()
    {
        return [
            // Cyrillic
            ['Кириллица@Кириллица'],
            // Latin + Han + Hiragana + Katakana
            ["latin漢字"."ひらがな"."カタカナ"."@example.com"],
            // Latin + Han + Hangul
            ["latin"."漢字"."조선말"."@example.com"],
            // Latin + Han + Bopomofo
            ["latin"."漢字"."ㄅㄆㄇㄈ"."@example.com"]
        ];
    }
}
