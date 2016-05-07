<?php

namespace Egulias\EmailValidator\Exception;

class ExpectedQPair extends InvalidEmail
{
    const CODE = 136;
    const REASON = "Expecting QPAIR";
}
