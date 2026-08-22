<?php
declare(strict_types=1);

namespace Sunmerce\RemoteGallery\Plugin\Checkout;

use Sunmerce\RemoteGallery\Model\Config;
use Sunmerce\RemoteGallery\Model\RemoteImageUrlProvider;
use Sunmerce\RemoteGallery\Model\UrlTransformer;
use Magento\Checkout\CustomerData\DefaultItem;
use Magento\Quote\Model\Quote\Item;

/**
 * Substitutes minicart customer-data product image URLs.
 */
class DefaultItemPlugin
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var RemoteImageUrlProvider
     */
    private $remoteImageUrlProvider;

    /**
     * @param Config $config
     * @param RemoteImageUrlProvider $remoteImageUrlProvider
     */
    public function __construct(
        Config $config,
        RemoteImageUrlProvider $remoteImageUrlProvider
    ) {
        $this->config = $config;
        $this->remoteImageUrlProvider = $remoteImageUrlProvider;
    }

    /**
     * @param DefaultItem $subject
     * @param array $result
     * @param Item $item
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetItemData(DefaultItem $subject, array $result, Item $item): array
    {
        if (!$this->config->isEnabled() || !isset($result['product_image']['src'])) {
            return $result;
        }

        $url = $this->remoteImageUrlProvider->getUrlForItem($item, UrlTransformer::ROLE_THUMB);
        if ($url !== null) {
            $result['product_image']['src'] = $url;
        }

        return $result;
    }
}
