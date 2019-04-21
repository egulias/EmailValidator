<?php

namespace Egulias\EmailValidator\Helper;

class SmtpSocketHelper
{
    /**
     * @var int
     */
    private $port;

    /**
     * @var int
     */
    private $timeout;

    /**
     * @var resource
     */
    private $handle;

    public function __construct($port = 25, $timeout = 15)
    {
        $this->port = $port;
        $this->timeout = $timeout;
    }

    /**
     * Checks is resource
     *
     * @return bool
     */
    public function isResource()
    {
        return is_resource($this->handle);
    }

    /**
     * Opens resource
     *
     * @param string $hostname
     * @param int $errno
     * @param string $errstr
     */
    public function open($hostname, &$errno, &$errstr)
    {
        $this->handle = @fsockopen($hostname, $this->port, $errno, $errstr, $this->timeout);
    }

    /**
     * Writes message
     *
     * @param string $message
     *
     * @return bool|int
     */
    public function write($message)
    {
        if (!$this->isResource()) {
            return false;
        }

        return @fwrite($this->handle, $message);
    }

    /**
     * Get last response code
     *
     * @return int
     */
    public function getResponseCode()
    {
        if (!$this->isResource()) {
            return -1;
        }

        $data = '';
        while (substr($data, 3, 1) !== ' ') {
            if (!($data = @fgets($this->handle, 256))) {
                return -1;
            }
        }

        return intval(substr($data, 0, 3));
    }

    /**
     * Closes resource
     */
    public function close()
    {
        if (!$this->isResource()) {
            return;
        }

        @fclose($this->handle);

        $this->handle = null;
    }
}