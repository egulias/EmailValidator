<?php

namespace Egulias\Constraints;

use Symfony\Component\Validator\Constraints\Email;

class EmailExtra extends Email
{
    public $strict = false;
    public $verbose = false;
}
