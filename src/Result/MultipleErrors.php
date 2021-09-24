<?php

namespace Egulias\EmailValidator\Result;

use Egulias\EmailValidator\Result\InvalidEmail;
use Egulias\EmailValidator\Result\Reason\Reason;

/**
 * @psalm-suppress PropertyNotSetInConstructor
 */
class MultipleErrors extends InvalidEmail
{
    /**
     * @var Reason[]
     */
    private $reasons = [];

    public function __construct()
    {
    }

    public function addReason(Reason $reason) : void
    {
        $this->reasons[] = $reason;
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

    public function description() : string
    {
        $description = '';
        foreach($this->reasons as $reason) {
            $description .= $reason->description() . PHP_EOL;
        }

        return $description;
    }

    public function code() : int
    {
        return 0;
    }
}
