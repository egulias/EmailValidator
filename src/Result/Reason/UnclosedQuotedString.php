<?php

namespace Egulias\EmailValidator\Result\Reason;

final class UnclosedQuotedString implements Reason
{
    public function code() : int
    {
        return 145;
    }

    public function description() : string
    {
        return "Unclosed quoted string";
    }
}
