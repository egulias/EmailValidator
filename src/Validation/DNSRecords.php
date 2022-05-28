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

    public function __construct(array $records, bool $error = false)
    {
        $this->records = $records;
        $this->error = $error;
    }

    public function getRecords() : array
    {
        return $this->records;
    }

    public function withError() : bool
    {
        return $this->error;
    }


}