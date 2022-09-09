<?php

declare(strict_types=1);

namespace Egulias\EmailValidator;

use Egulias\EmailValidator\Validation\DNSCheckValidation;
use Egulias\EmailValidator\Validation\DNSGetRecordWrapper;
use Egulias\EmailValidator\Validation\MessageIDValidation;
use Egulias\EmailValidator\Validation\NoRFCWarningsValidation;
use Egulias\EmailValidator\Validation\RFCValidation;

class EmailValidatorFactory
{
    /** @var Validator[] */
    protected static array $defaultValidators = [
        RFCValidation::class,
        NoRFCWarningsValidation::class,
        MessageIDValidation::class,
        DNSGetRecordWrapper::class,
        DNSCheckValidation::class
    ];
    
    /**
     * @param string $emailAddress
     * @return array
     */
    public static function create(string $emailAddress): array
    {
        $validator = new EmailValidator();
        $result = [];

        foreach (self::$defaultValidators as $key => $val) {
            $result[get_class(new $val)] = $validator->isValid($emailAddress, new $val);
        }

        return $result;
    }
}