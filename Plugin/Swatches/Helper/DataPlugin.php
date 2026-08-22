<?php
declare(strict_types=1);

namespace Sunmerce\RemoteGallery\Plugin\Swatches\Helper;

use Sunmerce\RemoteGallery\Model\Config;
use Sunmerce\RemoteGallery\Model\RemoteMediaDataProvider;
use Magento\Catalog\Model\Product;
use Magento\Swatches\Helper\Data;

/**
 * Substitutes remote media in swatch AJAX responses.
 */
class DataPlugin
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var RemoteMediaDataProvider
     */
    private $remoteMediaDataProvider;

    /**
     * @param Config $config
     * @param RemoteMediaDataProvider $remoteMediaDataProvider
     */
    public function __construct(
        Config $config,
        RemoteMediaDataProvider $remoteMediaDataProvider
    ) {
        $this->config = $config;
        $this->remoteMediaDataProvider = $remoteMediaDataProvider;
    }

    /**
     * @param Data $subject
     * @param array $result
     * @param Product $product
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetProductMediaGallery(Data $subject, array $result, Product $product): array
    {
        if (!$this->config->isEnabled()) {
            return $result;
        }

        $remoteMedia = $this->remoteMediaDataProvider->getSwatchMediaGallery($product);

        return $remoteMedia ?: $result;
    }
}