<?php

namespace Egulias\EmailValidator\Validation;

use Egulias\EmailValidator\EmailLexer;
use Egulias\EmailValidator\Result\InvalidEmail;
use Egulias\EmailValidator\Result\Reason\DomainAcceptsNoMail;
use Egulias\EmailValidator\Result\Reason\LocalOrReservedDomain;
use Egulias\EmailValidator\Result\Reason\NoDNSRecord as ReasonNoDNSRecord;
use Egulias\EmailValidator\Result\Reason\UnableToGetDNSRecord;
use Egulias\EmailValidator\Warning\NoDNSMXRecord;

class DNSCheckValidation implements EmailValidation
{
    /**
     * @var int
     */
    protected const DNS_RECORD_TYPES_TO_CHECK = DNS_MX + DNS_A + DNS_AAAA;

    /**
     * @var array
     */
    private $warnings = [];

    /**
     * @var InvalidEmail|null
     */
    private $error;

    /**
     * @var array
     */
    private $mxRecords = [];


    public function __construct()
    {
        if (!function_exists('idn_to_ascii')) {
            throw new \LogicException(sprintf('The %s class requires the Intl extension.', __CLASS__));
        }
    }

    public function isValid(string $email, EmailLexer $emailLexer) : bool
    {
        // use the input to check DNS if we cannot extract something similar to a domain
        $host = $email;

        // Arguable pattern to extract the domain. Not aiming to validate the domain nor the email
        if (false !== $lastAtPos = strrpos($email, '@')) {
            $host = substr($email, $lastAtPos + 1);
        }

        // Get the domain parts
        $hostParts = explode('.', $host);

        // Reserved Top Level DNS Names (https://tools.ietf.org/html/rfc2606#section-2),
        // mDNS and private DNS Namespaces (https://tools.ietf.org/html/rfc6762#appendix-G)
        $reservedTopLevelDnsNames = [
            // Reserved Top Level DNS Names
            'test',
            'example',
            'invalid',
            'localhost',

            // mDNS
            'local',

            // Private DNS Namespaces
            'intranet',
            'internal',
            'private',
            'corp',
            'home',
            'lan',
        ];

        $isLocalDomain = count($hostParts) <= 1;
        $isReservedTopLevel = in_array($hostParts[(count($hostParts) - 1)], $reservedTopLevelDnsNames, true);

        // Exclude reserved top level DNS names
        if ($isLocalDomain || $isReservedTopLevel) {
            $this->error = new InvalidEmail(new LocalOrReservedDomain(), $host);
            return false;
        }

        return $this->checkDns($host);
    }

    public function getError() : ?InvalidEmail
    {
        return $this->error;
    }

    public function getWarnings() : array
    {
        return $this->warnings;
    }

    /**
     * @param string $host
     *
     * @return bool
     */
    protected function checkDns($host)
    {
        $variant = INTL_IDNA_VARIANT_UTS46;

        $host = rtrim(idn_to_ascii($host, IDNA_DEFAULT, $variant), '.') . '.';

        return $this->validateDnsRecords($host);
    }


    /**
     * Validate the DNS records for given host.
     *
     * @param string $host A set of DNS records in the format returned by dns_get_record.
     *
     * @return bool True on success.
     */
    private function validateDnsRecords($host) : bool
    {
        // A workaround to fix https://bugs.php.net/bug.php?id=73149
        /** @psalm-suppress InvalidArgument */
        set_error_handler(
            static function (int $errorLevel, string $errorMessage): ?bool {
                throw new \RuntimeException("Unable to get DNS record for the host: $errorMessage");
            }
        );

        try {
            // Get all MX, A and AAAA DNS records for host
            $dnsRecords = dns_get_record($host, static::DNS_RECORD_TYPES_TO_CHECK);
        } catch (\RuntimeException $exception) {
            $this->error = new InvalidEmail(new UnableToGetDNSRecord(), '');

            return false;
        } finally {
            restore_error_handler();
        }

        // No MX, A or AAAA DNS records
        if ($dnsRecords === [] || $dnsRecords === false) {
            $this->error = new InvalidEmail(new ReasonNoDNSRecord(), '');
            return false;
        }

        // For each DNS record
        foreach ($dnsRecords as $dnsRecord) {
            if (!$this->validateMXRecord($dnsRecord)) {
                // No MX records (fallback to A or AAAA records)
                if (empty($this->mxRecords)) {
                    $this->warnings[NoDNSMXRecord::CODE] = new NoDNSMXRecord();
                }
                return false;
            }
        }
        return true;
    }

    /**
     * Validate an MX record
     *
     * @param array $dnsRecord Given DNS record.
     *
     * @return bool True if valid.
     */
    private function validateMxRecord($dnsRecord) : bool
    {
        if ($dnsRecord['type'] !== 'MX') {
            return true;
        }

        // "Null MX" record indicates the domain accepts no mail (https://tools.ietf.org/html/rfc7505)
        if (empty($dnsRecord['target']) || $dnsRecord['target'] === '.') {
            $this->error = new InvalidEmail(new DomainAcceptsNoMail(), "");
            return false;
        }

        $this->mxRecords[] = $dnsRecord;

        return true;
    }
}