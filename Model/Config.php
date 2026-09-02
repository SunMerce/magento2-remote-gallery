<?php
declare(strict_types=1);

namespace Sunmerce\RemoteGallery\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Sunmerce\RemoteGallery\Model\Csp\HostValidator;

/**
 * Remote gallery module configuration
 */
class Config
{
    public const XML_PATH_ENABLED = 'catalog/sunmerce_remote_gallery/enabled';
    public const XML_PATH_OPTIMIZATION_LOCATION = 'catalog/sunmerce_remote_gallery/optimization_location';
    public const XML_PATH_THUMB_OPTIONS = 'catalog/sunmerce_remote_gallery/thumb_options';
    public const XML_PATH_IMAGE_OPTIONS = 'catalog/sunmerce_remote_gallery/image_options';
    public const XML_PATH_FULL_OPTIONS = 'catalog/sunmerce_remote_gallery/full_options';
    public const XML_PATH_LISTING_OPTIONS = 'catalog/sunmerce_remote_gallery/listing_options';
    public const XML_PATH_CSP_IMG_HOSTS = 'catalog/sunmerce_remote_gallery/csp_img_hosts';

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var HostValidator
     */
    private $hostValidator;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param HostValidator $hostValidator
     */
    public function __construct(ScopeConfigInterface $scopeConfig, HostValidator $hostValidator)
    {
        $this->scopeConfig = $scopeConfig;
        $this->hostValidator = $hostValidator;
    }

    /**
     * Check whether remote gallery substitution is enabled
     *
     * @param int|string|null $storeId
     * @return bool
     */
    public function isEnabled($storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get CDN optimization option insertion location
     *
     * @param int|string|null $storeId
     * @return string
     */
    public function getOptimizationLocation($storeId = null): string
    {
        return (string)$this->scopeConfig->getValue(
            self::XML_PATH_OPTIMIZATION_LOCATION,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get CDN optimization options for an image role
     *
     * @param string $role
     * @param int|string|null $storeId
     * @return string
     */
    public function getOptimizationOptions(string $role, $storeId = null): string
    {
        $paths = [
            UrlTransformer::ROLE_THUMB => self::XML_PATH_THUMB_OPTIONS,
            UrlTransformer::ROLE_IMAGE => self::XML_PATH_IMAGE_OPTIONS,
            UrlTransformer::ROLE_FULL => self::XML_PATH_FULL_OPTIONS,
            UrlTransformer::ROLE_LISTING => self::XML_PATH_LISTING_OPTIONS,
        ];

        if (!isset($paths[$role])) {
            return '';
        }

        return (string)$this->scopeConfig->getValue(
            $paths[$role],
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get CDN hosts allowed by the Content-Security-Policy img-src directive
     *
     * @param int|string|null $storeId
     * @return string[]
     */
    public function getCspImgHosts($storeId = null): array
    {
        $value = (string)$this->scopeConfig->getValue(
            self::XML_PATH_CSP_IMG_HOSTS,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );

        $hosts = preg_split('/[\s,;]+/', $value, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $hosts = array_values(array_unique($hosts));

        // Never emit unsafe sources into the CSP header, even if the stored
        // value bypassed admin validation (e.g. set via CLI or import).
        return $this->hostValidator->getValid($hosts);
    }
}
