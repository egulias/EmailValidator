<?php

namespace Egulias\EmailValidator\Warning;

abstract class Warning
{
    protected $message;
    protected $code;

    public function code()
    {
        return $this->code;
    }

    public function message()
    {
        return $this->message;
    }
}
