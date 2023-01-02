<?php

namespace Egulias\EmailValidator\Validation;

class DNSRecords
{

    /**
     * @var array $records
     */
    private $records = [];

    /**
     * @var bool $error
     */
    private $error = false;

    /**
     * @param array $records
     * @param bool $error
     */
    public function __construct(array $records, bool $error = false)
    {
        $this->records = $records;
        $this->error = $error;
    }

    /**
     * @return array
     */
    public function getRecords(): array
    {
        return $this->records;
    }

    public function withError(): bool
    {
        return $this->error;
    }
}
