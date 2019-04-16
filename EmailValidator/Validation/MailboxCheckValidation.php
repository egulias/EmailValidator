<?php

namespace Egulias\EmailValidator\Validation;

use Egulias\EmailValidator\EmailLexer;
use Egulias\EmailValidator\Exception\InvalidEmail;
use Egulias\EmailValidator\Validation\Error\IllegalMailbox;
use Egulias\EmailValidator\Warning\NoDNSMXRecord;
use Egulias\EmailValidator\Warning\SocketWarning;
use Egulias\EmailValidator\Warning\Warning;

class MailboxCheckValidation implements EmailValidation
{
    const END_OF_LINE = "\r\n";

    /**
     * @var InvalidEmail
     */
    private $error;

    /**
     * @var Warning[]
     */
    private $warnings = [];

    /**
     * @var int
     */
    private $lastResponseCode;

    /**
     * @var int
     */
    private $port = 25;

    /**
     * @var int
     */
    private $timeout = 10;

    /**
     * @var string
     */
    private $fromEmail = 'test-mailbox@validation.email';

    /**
     * MailboxCheckValidation constructor.
     */
    public function __construct()
    {
        if (!extension_loaded('intl')) {
            throw new \LogicException(sprintf('The %s class requires the Intl extension.', __CLASS__));
        }
    }

    /**
     * @inheritDoc
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * @inheritDoc
     */
    public function getWarnings()
    {
        return $this->warnings;
    }

    /**
     * @return int
     */
    public function getLastResponseCode()
    {
        return $this->lastResponseCode;
    }

    /**
     * @return int
     */
    public function getPort()
    {
        return $this->port;
    }

    /**
     * @param int $port
     */
    public function setPort($port)
    {
        $this->port = $port;
    }

    /**
     * @return int
     */
    public function getTimeout()
    {
        return $this->timeout;
    }

    /**
     * @param int $timeout
     */
    public function setTimeout($timeout)
    {
        $this->timeout = $timeout;
    }

    /**
     * @return string
     */
    public function getFromEmail()
    {
        return $this->fromEmail;
    }

    /**
     * @param string $fromEmail
     */
    public function setFromEmail($fromEmail)
    {
        $this->fromEmail = $fromEmail;
    }

    /**
     * @inheritDoc
     */
    public function isValid($email, EmailLexer $emailLexer)
    {
        $mxHosts = $this->getMXHosts($email);

        $isValid = false;
        foreach ($mxHosts as $mxHost) {
            if ( ($isValid = $this->checkMailbox($mxHost, $this->port, $this->timeout, $this->fromEmail, $email)) ) {
                break;
            }
        }

        if ( ! $isValid ) {
            $this->error = new IllegalMailbox($this->lastResponseCode);
        }

        return $this->error === null;
    }

    /**
     * @param string $email
     *
     * @return array
     */
    protected function getMXHosts($email)
    {
        $variant = defined('INTL_IDNA_VARIANT_UTS46') ? INTL_IDNA_VARIANT_UTS46 : INTL_IDNA_VARIANT_2003;

        $hostname = $email;
        if ( false !== ($lastAtPos = strrpos($email, '@')) ) {
            $hostname = substr($email, $lastAtPos + 1);
        }
        $hostname = rtrim(idn_to_ascii($hostname, IDNA_DEFAULT, $variant), '.') . '.';

        $mxHosts = [];
        $result = getmxrr($hostname, $mxHosts);
        if ( ! $result ) {
            $this->warnings[NoDNSMXRecord::CODE] = new NoDNSMXRecord();
        }

        return $mxHosts;
    }

    /**
     * @param string $hostname
     * @param int $port
     * @param int $timeout
     * @param string $fromEmail
     * @param string $toEmail
     * @return bool
     */
    protected function checkMailbox($hostname, $port, $timeout, $fromEmail, $toEmail)
    {
        $socket = @fsockopen($hostname, $port, $errno, $errstr, $timeout);

        if ( ! $socket ) {
            $this->warnings[SocketWarning::CODE][] = new SocketWarning($hostname, $errno, $errstr);

            return false;
        }

        if ( ! ($this->assertResponse($socket, 220) ) ) {
            return false;
        }

        fwrite($socket, "EHLO {$hostname}" . self::END_OF_LINE);
        if ( ! ($this->assertResponse($socket, 250) ) ) {
            return false;
        }

        fwrite($socket, "MAIL FROM: <{$fromEmail}>" . self::END_OF_LINE);
        if ( ! ($this->assertResponse($socket, 250) ) ) {
            return false;
        }

        fwrite($socket, "RCPT TO: <{$toEmail}>" . self::END_OF_LINE);
        if ( ! ($this->assertResponse($socket, 250) ) ) {
            return false;
        }

        fwrite($socket, 'QUIT' . self::END_OF_LINE);

        fclose($socket);

        return true;
    }

    /**
     * @param resource $socket
     * @param int $expectedCode
     *
     * @return bool
     */
    private function assertResponse($socket, $expectedCode)
    {
        if ( ! is_resource($socket) ) {
            return false;
        }

        $data = '';
        while (substr($data, 3, 1) !== ' ') {
            if ( ! ( $data = @fgets($socket, 256) ) ) {
                $this->lastResponseCode = -1;

                return false;
            }
        }

        return ($this->lastResponseCode = intval( substr($data, 0, 3) )) === $expectedCode;
    }
}