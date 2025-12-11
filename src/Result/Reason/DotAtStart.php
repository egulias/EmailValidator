<?php

namespace Egulias\EmailValidator\Result\Reason;

final class DotAtStart implements Reason
{
    public function code() : int
    {
        return 141;
    }

    public function description() : string
    {
        return "Starts with a DOT";
    }
}
