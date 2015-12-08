<?php

namespace Egulias\EmailValidator\Warning;

class NoDNSRecord extends Warning
{
    const CODE = 6;

    public function __construct()
    {
        $this->message = 'No DSN record was found for this email';
    }
}
