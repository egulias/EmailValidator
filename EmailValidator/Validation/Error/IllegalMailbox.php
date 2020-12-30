<?php

namespace Egulias\EmailValidator\Validation\Error;

use Egulias\EmailValidator\Exception\InvalidEmail;

class IllegalMailbox extends InvalidEmail
{
    const CODE = 995;
    const REASON = "The mailbox is illegal.";

    /**
     * @var int
     */
    private $responseCode;

    /**
     * IllegalMailbox constructor.
     *
     * @param int $responseCode
     */
    public function __construct($responseCode)
    {
        parent::__construct();

        $this->responseCode = $responseCode;
    }

    /**
     * @return int
     */
    public function getResponseCode()
    {
        return $this->responseCode;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return sprintf(
            "%s SMTP response code: %s. Internal code: %s.",
            $this->message,
            $this->responseCode,
            $this->code
        );
    }
}
