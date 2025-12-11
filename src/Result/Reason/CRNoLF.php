<?php

namespace Egulias\EmailValidator\Result\Reason;

final class CRNoLF implements Reason
{
    public function code() : int
    {
        return 150;
    }

    public function description() : string
    {
        return 'Missing LF after CR';
    }
}
