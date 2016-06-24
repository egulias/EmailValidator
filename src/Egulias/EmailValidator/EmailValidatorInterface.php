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
     * @param string $email    The email address to check.
     * @param bool   $checkDNS Check DNS records for the domain.
     * @param bool   $strict   Require DNS to pass (if enabled) and no deprecation warnings.
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