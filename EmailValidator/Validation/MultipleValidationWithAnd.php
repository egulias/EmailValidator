<?php

namespace Egulias\EmailValidator\Validation;

use Egulias\EmailValidator\EmailLexer;
use Egulias\EmailValidator\Exception\InvalidEmail;
use Egulias\EmailValidator\Validation\Exception\EmptyValidationList;

class MultipleValidationWithAnd implements EmailValidation
{
    /**
     * @var EmailValidation[]
     */
    private $validations = [];

    /**
     * @var array
     */
    private $warnings = [];

    /**
     * @var InvalidEmail
     */
    private $error;

    /**
     * @var bool
     */
    private $breakIfError;

    /**
     * @param EmailValidation[] $validations  The validations.
     * @param bool              $breakIfError If true, it breaks out of validation loop when error occurs,
     *                                        it means returned MultipleErrors might not contain all causes of errors. (false by default)
     */
    public function __construct(array $validations, $breakIfError = false)
    {
        if (count($validations) == 0) {
            throw new EmptyValidationList();
        }
        
        $this->validations = $validations;
        $this->breakIfError = $breakIfError;
    }

    /**
     * {@inheritdoc}
     */
    public function isValid($email, EmailLexer $emailLexer)
    {
        $result = true;
        $errors = [];
        foreach ($this->validations as $validation) {
            $emailLexer->reset();
            $result = $result && $validation->isValid($email, $emailLexer);
            $this->warnings = array_merge($this->warnings, $validation->getWarnings());
            $errors[] = $validation->getError();

            if (!$result && $this->breakIfError) {
                break;
            }
        }
        $this->error = new MultipleErrors($errors);
        
        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * {@inheritdoc}
     */
    public function getWarnings()
    {
        return $this->warnings;
    }
}
