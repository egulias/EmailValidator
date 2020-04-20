<?php

namespace Egulias\EmailValidator\Result;

interface Result
{
    public function isValid() : bool;
    public function description() : string;
    public function code() : int;
}