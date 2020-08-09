<?php

namespace Egulias\EmailValidator\Validation\Result;

use Egulias\EmailValidator\Result\InvalidEmail;
use Egulias\EmailValidator\Result\Reason\Reason;

class MultipleErrors extends InvalidEmail
{
    const CODE = 999;
    const REASON = "Accumulated errors for multiple validations";
    /**
     * @var Reason[]
     */
    private $errors = [];

    /**
     * @param Reason[] $errors
     */
    public function __construct(Reason $reason, string $token, array $errors)
    {
        $this->errors = $errors;
        parent::__construct($reason, $token);
    }

    /**
     * @return Reason[]
     */
    public function getErrors()
    {
        return $this->errors;
    }
}
