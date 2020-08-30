<?php

namespace Egulias\EmailValidator\Result\Reason;

class CRLFAtTheEnd implements Reason
{
    const CODE = 149;
    const REASON = "CRLF at the end";

    public function code() : int
    {
        return 149;
    }

    public function description() : string
    {
        return 'CRLF at the end';
    }
}
