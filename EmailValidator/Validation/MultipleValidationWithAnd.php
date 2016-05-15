<?php

namespace Egulias\EmailValidator\Validation;

use Egulias\EmailValidator\EmailLexer;
use Egulias\EmailValidator\Validation\Exception\EmptyValidationList;

class MultipleValidationWithAnd implements EmailValidation
{
    private $validations = [];
    private $warnings = [];
    private $error;
    
    public function __construct(array $validations)
    {
        if (count($validations) == 0) {
            throw new EmptyValidationList();
        }
        
        $this->validations = $validations;
    }

    public function isValid($email, EmailLexer $emailLexer)
    {
        $result = true;
        $errors = [];
        foreach ($this->validations as $validation) {
            $emailLexer->reset();
            $result = $result && $validation->isValid($email, $emailLexer);
            $this->warnings = array_merge($this->warnings, $validation->getWarnings());
            $errors[] = $validation->getError();
        }
        $this->error = new MultipleErrors($errors);
        
        return $result;
    }

    public function getError()
    {
        return $this->error;
    }

    public function getWarnings()
    {
        return $this->warnings;
    }
}
