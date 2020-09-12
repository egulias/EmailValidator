<?php

namespace Egulias\EmailValidator\Result\Reason;

class ExceptionFound implements Reason
{
    public function code() : int
    {
        return 999;
    }

    public function description() : string
    {
        return 'An exception occurred during the execution';
    }
}