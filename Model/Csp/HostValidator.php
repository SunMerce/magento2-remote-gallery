<?php
declare(strict_types=1);

namespace Sunmerce\RemoteGallery\Model\Csp;

/**
 * Validates host entries for the Content-Security-Policy img-src directive.
 *
 * Rejects anything that would weaken the policy:
 *  - wildcards (*)
 *  - scheme-only sources such as https:, http: or data:
 *  - commas, semicolons or whitespace inside an entry
 */
class HostValidator
{
    /**
     * Characters that must never appear inside a single host entry
     */
    private const FORBIDDEN_CHARS_PATTERN = '/[\s,;*]/';

    /**
     * A bare scheme followed by a colon, e.g. "https:", "data:"
     */
    private const SCHEME_ONLY_PATTERN = '/^[a-zA-Z0-9+.\-]*:$/';

    /**
     * Check whether a single host entry is safe to use in a CSP source list
     *
     * @param string $host
     * @return bool
     */
    public function isValid(string $host): bool
    {
        if ($host === '') {
            return false;
        }

        if (preg_match(self::FORBIDDEN_CHARS_PATTERN, $host)) {
            return false;
        }

        if (preg_match(self::SCHEME_ONLY_PATTERN, $host)) {
            return false;
        }

        return true;
    }

    /**
     * Return only the valid entries, preserving order
     *
     * @param string[] $hosts
     * @return string[]
     */
    public function getValid(array $hosts): array
    {
        return array_values(array_filter($hosts, function (string $host): bool {
            return $this->isValid($host);
        }));
    }

    /**
     * Return only the invalid entries, preserving order
     *
     * @param string[] $hosts
     * @return string[]
     */
    public function getInvalid(array $hosts): array
    {
        return array_values(array_filter($hosts, function (string $host): bool {
            return !$this->isValid($host);
        }));
    }
}
