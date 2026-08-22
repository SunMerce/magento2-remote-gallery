<?php
declare(strict_types=1);

namespace Sunmerce\RemoteGallery\Plugin\Checkout;

use Sunmerce\RemoteGallery\Model\Config;
use Sunmerce\RemoteGallery\Model\RemoteImageUrlProvider;
use Sunmerce\RemoteGallery\Model\UrlTransformer;
use Magento\Catalog\Model\Product;
use Magento\Checkout\Block\Cart\Item\Renderer;

class CartItemRendererPlugin
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
     * Prefer the selected child product when it has a remote image for the cart thumbnail.
     *
     * @param Renderer $subject
     * @param Product $result
     * @return Product
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetProductForThumbnail(Renderer $subject, Product $result): Product
    {
        if (!$this->config->isEnabled()) {
            return $result;
        }

        return $this->remoteImageUrlProvider->getProductForItem($subject->getItem(), UrlTransformer::ROLE_LISTING)
            ?? $result;
    }
}