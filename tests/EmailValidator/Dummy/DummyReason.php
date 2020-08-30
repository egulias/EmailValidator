<?php
namespace Egulias\EmailValidator\Tests\EmailValidator\Dummy;

use Egulias\EmailValidator\Result\Reason\Reason;

class DummyReason implements Reason
{
    public function code() : int
    {
        return 0;
    }

    public function description() : string
    {
        return 'Dummy Reason';
    }
}