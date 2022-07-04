<?php

namespace Egulias\EmailValidator\Warning;

abstract class Warning
{
    const CODE = 0;

    /**
     * @var string
     */
    protected $message = '';

    /**
     * @var int
     */
    protected $rfcNumber = 0;

    /**
     * @return string
     */
    final public function message()
    {
        return $this->message;
    }

    /**
     * @return int
     */
    final public function code()
    {
        return self::CODE;
    }

    /**
     * @return int
     */
    final public function RFCNumber()
    {
        return $this->rfcNumber;
    }

    public function __toString()
    {
        return $this->message() . " rfc: " .  $this->rfcNumber . "internal code: " . static::CODE;
    }
}
