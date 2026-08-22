<?php
declare(strict_types=1);

namespace Sunmerce\RemoteGallery\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

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

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(ScopeConfigInterface $scopeConfig)
    {
        $this->scopeConfig = $scopeConfig;
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
}
