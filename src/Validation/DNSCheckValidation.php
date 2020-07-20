<?php

namespace Egulias\EmailValidator\Validation;

use Egulias\EmailValidator\EmailLexer;
use Egulias\EmailValidator\Exception\InvalidEmail;
use Egulias\EmailValidator\Exception\LocalOrReservedDomain;
use Egulias\EmailValidator\Exception\DomainAcceptsNoMail;
use Egulias\EmailValidator\Warning\NoDNSMXRecord;
use Egulias\EmailValidator\Exception\NoDNSRecord;

class DNSCheckValidation implements EmailValidation
{
    /**
     * @var array
     */
    private $warnings = [];

    /**
     * @var InvalidEmail|null
     */
    private $error;

    public function __construct()
    {
        if (!function_exists('idn_to_ascii')) {
            throw new \LogicException(sprintf('The %s class requires the Intl extension.', __CLASS__));
        }
    }

    public function isValid($email, EmailLexer $emailLexer)
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

        // Exclude reserved top level DNS names
        if (count($hostParts) <= 1 || in_array($hostParts[(count($hostParts) - 1)], $reservedTopLevelDnsNames)) {
            $this->error = new LocalOrReservedDomain();
            return false;
        }

        return $this->checkDNS($host);
    }

    public function getError()
    {
        return $this->error;
    }

    public function getWarnings()
    {
        return $this->warnings;
    }

    /**
     * @param string $host
     *
     * @return bool
     */
    protected function checkDNS($host)
    {
        $variant = INTL_IDNA_VARIANT_2003;
        if (defined('INTL_IDNA_VARIANT_UTS46')) {
            $variant = INTL_IDNA_VARIANT_UTS46;
        }
        $host = rtrim(idn_to_ascii($host, IDNA_DEFAULT, $variant), '.') . '.';

        // Get all MX, A and AAAA DNS records
        $dnsRecords = dns_get_record($host, DNS_MX + DNS_A + DNS_AAAA);

        // No MX, A or AAAA records
        if (empty($dnsRecords)) {
            $this->error = new NoDNSRecord();
            return false;
        }

        $aRecords  = [];
        $mxRecords = [];

        // Iterate over all returned DNS records
        foreach ($dnsRecords as $dnsRecord) {
            if ($dnsRecord['type'] == 'MX') {
                // "Null MX" record indicates the domain accepts no mail (https://tools.ietf.org/html/rfc7505)
                if (empty($dnsRecord['target']) || $dnsRecord['target'] == '.') {
                    $this->error = new DomainAcceptsNoMail();
                    return false;
                }

                $mxRecords[] = $dnsRecord;
                continue;
            }

            if (in_array($dnsRecord['type'], ['A', 'AAAA'])) {
                $aRecords[] = $dnsRecord;
            }
        }

        // No MX record
        if (empty($mxRecords)) {
            $this->warnings[NoDNSMXRecord::CODE] = new NoDNSMXRecord();
        }

        return true;
    }
}