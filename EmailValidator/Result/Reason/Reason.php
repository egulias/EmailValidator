<?php

namespace Egulias\EmailValidator\Result\Reason;

interface Reason
{
    public function code() : int;
    public function description() : string;
}