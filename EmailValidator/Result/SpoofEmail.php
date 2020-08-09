<?php
namespace Egulias\EmailValidator\Validation\Result;

use Egulias\EmailValidator\Result\InvalidEmail;

class SpoofEmail extends InvalidEmail
{
    const CODE = 998;

    private $reason = "The email contains mixed UTF8 chars that makes it suspicious";

    public function __construct()
    {
        parent::__construct($this->reason, '');
    }
}
