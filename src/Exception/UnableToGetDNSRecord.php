<?php

namespace Egulias\EmailValidator\Exception;

class UnableToGetDNSRecord extends NoDNSRecord
{
    const CODE = 3;
    const REASON = 'Unable to get DNS records for the host';
}
