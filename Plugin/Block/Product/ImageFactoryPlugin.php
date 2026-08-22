<?php
declare(strict_types=1);

namespace Sunmerce\RemoteGallery\Plugin\Block\Product;

use Sunmerce\RemoteGallery\Model\Config;
use Sunmerce\RemoteGallery\Model\RemoteImageUrlProvider;
use Sunmerce\RemoteGallery\Model\UrlTransformer;
use Magento\Catalog\Block\Product\Image as ImageBlock;
use Magento\Catalog\Block\Product\ImageFactory;
use Magento\Catalog\Model\Product;

/**
 * Substitutes the remote main image URL into listing image blocks
 * (category grid/list, search results, widgets, cross-sells)
 */
class ImageFactoryPlugin
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
     * Replace the image URL with the first remote gallery image when available
     *
     * @param ImageFactory $subject
     * @param ImageBlock $result
     * @param Product $product
     * @return ImageBlock
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterCreate(ImageFactory $subject, ImageBlock $result, Product $product): ImageBlock
    {
        if (!$this->config->isEnabled()) {
            return $result;
        }

        $url = $this->remoteImageUrlProvider->getUrl($product, UrlTransformer::ROLE_LISTING);
        if ($url !== null) {
            $result->setData('image_url', $url);
        }

        return $result;
    }
}
