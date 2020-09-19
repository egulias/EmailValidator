<?php
namespace Egulias\EmailValidator\Tests\EmailValidator\Dummy;

use Egulias\EmailValidator\Result\Reason\Reason;

class AnotherDummyReason implements Reason
{
    public function code() : int
    {
        return 1;
    }

    public function description() : string
    {
        return 'Dummy Reason';
    }
}