<?php
declare(strict_types=1);

namespace Sunmerce\RemoteGallery\Plugin\Block\Product\View\Type;

use Sunmerce\RemoteGallery\Model\Config;
use Sunmerce\RemoteGallery\Model\RemoteMediaDataProvider;
use Magento\ConfigurableProduct\Block\Product\View\Type\Configurable;
use Magento\Swatches\Block\Product\Renderer\Listing\Configurable as ListingConfigurable;

/**
 * Adds remote child product galleries to configurable product JSON.
 */
class ConfigurablePlugin
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
     * @param Configurable $subject
     * @param string $result
     * @return string
     */
    public function afterGetJsonConfig(Configurable $subject, $result): string
    {
        if (!$this->config->isEnabled() || $subject instanceof ListingConfigurable) {
            return $result;
        }

        $config = json_decode($result, true);
        if (!is_array($config)) {
            return $result;
        }

        foreach ($subject->getAllowProducts() as $product) {
            $images = $this->remoteMediaDataProvider->getFotoramaImages($product);
            if ($images) {
                $config['images'][(int)$product->getId()] = $images;
            }
        }

        $encodedConfig = json_encode($config);

        return is_string($encodedConfig) ? $encodedConfig : $result;
    }
}