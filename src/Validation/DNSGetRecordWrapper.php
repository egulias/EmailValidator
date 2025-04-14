<?php

namespace Egulias\EmailValidator\Validation;

class DNSGetRecordWrapper
{
    /**
     * @param string $host
     * @param int $type
     *
     * @return DNSRecords
     */
    public function getRecords(string $host, int $type): DNSRecords
    {
        $result = @dns_get_record($host, $type);
        return new DNSRecords($result === false ? [] : $result, $result === false);
    }
}
