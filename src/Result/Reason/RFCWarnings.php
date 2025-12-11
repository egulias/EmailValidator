<?php

namespace Egulias\EmailValidator\Result\Reason;

final class RFCWarnings implements Reason
{
    public function code() : int
    {
        return 997;
    }

    public function description() : string
    {
        return 'Warnings found after validating';
    }
}
