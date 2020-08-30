<?php

namespace Egulias\EmailValidator\Result\Reason;

class UnclosedComment implements Reason 
{
    public function code() : int
    {
        return 146;
    }

    public function description(): string
    {
        return 'No colosing comment token found';
    }
}
