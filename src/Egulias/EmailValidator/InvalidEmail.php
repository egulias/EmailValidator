<?php

namespace Egulias\EmailValidator;

abstract class InvalidEmail extends \InvalidArgumentException
{
    public function __construct()
    {
        parent::__construct(static::REASON, static::CODE);
    }
}
