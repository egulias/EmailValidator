<?php

declare(strict_types=1);

namespace Egulias\EmailValidator;

use Egulias\EmailValidator\Validation\DNSCheckValidation;
use Egulias\EmailValidator\Validation\DNSGetRecordWrapper;
use Egulias\EmailValidator\Validation\DNSRecords;
use Egulias\EmailValidator\Validation\MessageIDValidation;
use Egulias\EmailValidator\Validation\MultipleValidationWithAnd;
use Egulias\EmailValidator\Validation\NoRFCWarningsValidation;
use Egulias\EmailValidator\Validation\RFCValidation;

class EmailValidatorFactory
{
    /**
     * @param string $emailAddress
     * @return bool
     */
    public static function create(string $emailAddress): bool
    {
        $validator = new EmailValidator();

        $multipleValidations = new MultipleValidationWithAnd([
            new RFCValidation(),
            new NoRFCWarningsValidation(),
            new MessageIDValidation(),
            new DNSGetRecordWrapper(),
            new DNSCheckValidation()
        ]);

        return $validator->isValid($emailAddress, $multipleValidations);
    }
}