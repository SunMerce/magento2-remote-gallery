<?php
declare(strict_types=1);

namespace Sunmerce\RemoteGallery\Model;

use Sunmerce\RemoteGallery\Model\Source\OptimizationLocation;

/**
 * Applies configured CDN optimization options to remote gallery URLs
 */
class UrlTransformer
{
    public const ROLE_THUMB = 'thumb';
    public const ROLE_IMAGE = 'image';
    public const ROLE_FULL = 'full';
    public const ROLE_LISTING = 'listing';

    public const PLACEHOLDER = '{OPTIMIZE_OPTIONS}';

    /**
     * @var Config
     */
    private $config;

    /**
     * @param Config $config
     */
    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    /**
     * Apply configured optimization options for the given role.
     *
     * @param string $url
     * @param string $role
     * @param int|string|null $storeId
     * @return string
     */
    public function transform(string $url, string $role, $storeId = null): string
    {
        $options = trim($this->config->getOptimizationOptions($role, $storeId));
        $location = $this->config->getOptimizationLocation($storeId);

        if ($location === OptimizationLocation::PLACEHOLDER) {
            return str_replace(self::PLACEHOLDER, $options, $url);
        }

        if ($options === '') {
            return str_replace(self::PLACEHOLDER, '', $url);
        }

        if ($location === OptimizationLocation::AFTER_DOMAIN) {
            return $this->insertAfterDomain($url, $options);
        }

        if ($location === OptimizationLocation::APPEND_QUERY) {
            return $this->appendQuery($url, $options);
        }

        return $url;
    }

    /**
     * Insert options as a path segment immediately after the URL domain.
     *
     * @param string $url
     * @param string $options
     * @return string
     */
    private function insertAfterDomain(string $url, string $options): string
    {
        $parts = parse_url($url);
        if (empty($parts['scheme']) || empty($parts['host'])) {
            return $url;
        }

        $authority = $parts['scheme'] . '://' . $parts['host'] . (isset($parts['port']) ? ':' . $parts['port'] : '');
        $path = '/' . ltrim($parts['path'] ?? '', '/');
        $query = isset($parts['query']) ? '?' . $parts['query'] : '';
        $fragment = isset($parts['fragment']) ? '#' . $parts['fragment'] : '';

        return $authority . '/' . trim($options, '/') . $path . $query . $fragment;
    }

    /**
     * Append options to the URL query string.
     *
     * @param string $url
     * @param string $options
     * @return string
     */
    private function appendQuery(string $url, string $options): string
    {
        $fragment = '';
        $fragmentPosition = strpos($url, '#');
        if ($fragmentPosition !== false) {
            $fragment = substr($url, $fragmentPosition);
            $url = substr($url, 0, $fragmentPosition);
        }

        $separator = strpos($url, '?') === false ? '?' : '&';

        return $url . $separator . ltrim($options, '?&') . $fragment;
    }
}
