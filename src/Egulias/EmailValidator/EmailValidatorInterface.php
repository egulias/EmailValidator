<?php

namespace Egulias\EmailValidator;

/**
 * EmailValidatorInterface
 *
 * @author Chris McCafferty <cilefen@gmail.com>
 */
interface EmailValidatorInterface
{
    /**
     * Validates an email address against the following standards:
     *
     * RFC-5321: Simple Mail Transfer Protocol
     * RFC-5322: Internet Message Format
     * RFC-6530: Overview and Framework for Internationalized Email
     * RFC-6531: SMTP Extension for Internationalized Email
     * RFC-6532: Internationalized Email Headers
     * RFC-1123 section 2.1: Requirements for Internet Hosts -- Application and Support
     * RFC-4291 section 2.2: IP Version 6 Addressing Architecture
     *
     * @param string $email    The email address to validate.
     * @param bool   $checkDNS Whether or not the email address's hostname should
     *                         be confirmed with a DNS lookup. This only comes
     *                         into play if strict mode is also enabled.
     * @param bool   $strict   If this is true, and any informational warnings
     *                         were raised during validation, the email address
     *                         will be considered invalid. Additionally, if
     *                         $checkDNS is true and the DNS lookup failed,
     *                         the email address will be considered invalid.
     * @return bool
     */
    public function isValid($email, $checkDNS = false, $strict = false);

    /**
     * @return bool
     */
    public function hasWarnings();

    /**
     * @return array
     */
    public function getWarnings();

    /**
     * @return string
     */
    public function getError();

    /**
     * @param int $threshold The acceptable number of deprecation warnings.
     *
     * @return EmailValidator
     */
    public function setThreshold($threshold);

    /**
     * @return int
     */
    public function getThreshold();
}
