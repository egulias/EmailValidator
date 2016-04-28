<?php

namespace Egulias\EmailValidator\Warning;

class NoDNSRecord extends Warning
{
    const CODE = 5;

    public function __construct()
    {
        $this->message = 'No MX or A DSN record was found for this email';
        $this->rfcNumber = 5321;
    }
}
