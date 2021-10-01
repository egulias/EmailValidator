<?php

namespace Egulias\EmailValidator\Result\Exception;

use Exception;

class EmptyReasonList extends \InvalidArgumentException
{
    /**
    * @param int $code
    */
    public function __construct($code = 0, Exception $previous = null)
    {
        parent::__construct("Empty reason list is not allowed", $code, $previous);
    }
}
