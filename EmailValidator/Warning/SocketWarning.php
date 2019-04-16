<?php

namespace Egulias\EmailValidator\Warning;

class SocketWarning extends Warning
{
    const CODE = 996;

    public function __construct($hostname, $errno, $errstr)
    {
        $this->message = "Error connecting to {$hostname} ({$errno}) ({$errstr})";
    }
}
