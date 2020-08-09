<?php

namespace Egulias\EmailValidator\Result;

use Egulias\EmailValidator\Result\InvalidEmail;
use Egulias\EmailValidator\Result\Reason\Reason;

class MultipleErrors extends InvalidEmail
{
    /**
     * @var Reason[]
     */
    private $errors = [];

    public function __construct()
    {
    }

    public function addError(Reason $reason)
    {
        $this->errors[$reason->code()] = $reason;
    }

    /**
     * @return Reason[]
     */
    public function getErrors()
    {
        return $this->errors;
    }
}
