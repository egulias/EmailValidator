<?php

namespace Egulias\EmailValidator\Warning;

final class NoDNSMXRecord extends Warning
{
    public const CODE = 6;

    public function __construct()
    {
        $this->message = 'No MX DSN record was found for this email';
        $this->rfcNumber = 5321;
    }
}
