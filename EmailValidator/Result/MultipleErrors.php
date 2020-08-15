<?php

namespace Egulias\EmailValidator\Result;

use Egulias\EmailValidator\Result\InvalidEmail;
use Egulias\EmailValidator\Result\Reason\Reason;

class MultipleErrors extends InvalidEmail
{
    /**
     * @var Reason[]
     */
    private $reasons = [];

    public function __construct()
    {
    }

    public function addReason(Reason $reason)
    {
        $this->errors[$reason->code()] = $reason;
    }

    /**
     * @return Reason[]
     */
    public function getReasons() : array
    {
        return $this->reasons;
    }

    public function reason() : Reason
    {
        return $this->reasons[0];
    }
}
