<?php

namespace Egulias\EmailValidator\Warning;

class TLD extends Warning
{
    public function __construct()
    {
        $this->code = 9;
        $this->message = "RFC5321, TLD";
    }
}
