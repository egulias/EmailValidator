<?php

namespace Egulias\EmailValidator\Result;

use Egulias\EmailValidator\Result\Reason\Reason;

class InvalidEmail implements Result
{
    private $token;
    /**
     * @var Reason
     */
    protected $reason;

    public function __construct(Reason $reason, string $token)
    {
        $this->token = $token;
        $this->reason = $reason;
    }

    public function isValid(): bool
    {
        return false;
    }

    public function isInvalid(): bool
    {
        return true;
    }

    public function description(): string
    {
        return $this->reason->description() . " in char " . $this->token;
    }

    public function code(): int
    {
        return $this->reason->code();
    }

    public function reason() : Reason
    {
        return $this->reason;
    }

}
