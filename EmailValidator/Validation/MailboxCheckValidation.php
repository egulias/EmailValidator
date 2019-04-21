<?php

namespace Egulias\EmailValidator\Validation;

use Egulias\EmailValidator\EmailLexer;
use Egulias\EmailValidator\Exception\InvalidEmail;
use Egulias\EmailValidator\Helper\SmtpSocketHelper;
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
     * @var SmtpSocketHelper
     */
    private $socketHelper;

    /**
     * @var string
     */
    private $fromEmail;

    /**
     * @var int
     */
    private $lastResponseCode;

    /**
     * MailboxCheckValidation constructor.
     *
     * @param SmtpSocketHelper $socketHelper
     * @param string $fromEmail
     */
    public function __construct(SmtpSocketHelper $socketHelper, $fromEmail)
    {
        if (!extension_loaded('intl')) {
            throw new \LogicException(sprintf('The %s class requires the Intl extension.', __CLASS__));
        }

        $this->socketHelper = $socketHelper;
        $this->fromEmail = $fromEmail;
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
     * @inheritDoc
     */
    public function isValid($email, EmailLexer $emailLexer)
    {
        $mxHosts = $this->getMXHosts($email);

        $isValid = false;
        foreach ($mxHosts as $mxHost) {
            if ($this->checkMailboxExists($mxHost, $email)) {
                $isValid = true;
                break;
            }
        }

        if (!$isValid) {
            $this->error = new IllegalMailbox($this->lastResponseCode);
        }

        return $this->error === null;
    }

    /**
     * Gets MX Hosts from email
     *
     * @param string $email
     *
     * @return array
     */
    protected function getMXHosts($email)
    {
        $hostname = $this->extractHostname($email);

        $result = getmxrr($hostname, $mxHosts);
        if (!$result) {
            $this->warnings[NoDNSMXRecord::CODE] = new NoDNSMXRecord();
        }

        return $mxHosts;
    }

    /**
     * Extracts hostname from email
     *
     * @param string $email
     *
     * @return string
     */
    private function extractHostname($email)
    {
        $variant = defined('INTL_IDNA_VARIANT_UTS46') ? INTL_IDNA_VARIANT_UTS46 : INTL_IDNA_VARIANT_2003;

        $lastAtPos = strrpos($email, '@');
        if ((bool) $lastAtPos) {
            $hostname = substr($email, $lastAtPos + 1);
            return rtrim(idn_to_ascii($hostname, IDNA_DEFAULT, $variant), '.') . '.';
        }

        return rtrim(idn_to_ascii($email, IDNA_DEFAULT, $variant), '.') . '.';
    }

    /**
     * Checks mailbox
     *
     * @param string $hostname
      * @param string $email
     * @return bool
     */
    private function checkMailboxExists($hostname, $email)
    {
        $this->socketHelper->open($hostname, $errno, $errstr);

        if (!$this->socketHelper->isResource()) {
            $this->warnings[SocketWarning::CODE][] = new SocketWarning($hostname, $errno, $errstr);

            return false;
        }

        $this->lastResponseCode = $this->socketHelper->getResponseCode();
        if ($this->lastResponseCode !== 220) {
            return false;
        }

        $this->socketHelper->write("EHLO {$hostname}" . self::END_OF_LINE);
        $this->lastResponseCode = $this->socketHelper->getResponseCode();
        if ($this->lastResponseCode !== 250) {
            return false;
        }

        $this->socketHelper->write("MAIL FROM: <{$this->fromEmail}>" . self::END_OF_LINE);
        $this->lastResponseCode = $this->socketHelper->getResponseCode();
        if ($this->lastResponseCode !== 250) {
            return false;
        }

        $this->socketHelper->write("RCPT TO: <{$email}>" . self::END_OF_LINE);
        $this->lastResponseCode = $this->socketHelper->getResponseCode();
        if ($this->lastResponseCode !== 250) {
            return false;
        }

        $this->socketHelper->write('QUIT' . self::END_OF_LINE);

        $this->socketHelper->close();

        return true;
    }
}