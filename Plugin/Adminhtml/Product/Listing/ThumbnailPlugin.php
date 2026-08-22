<?php
declare(strict_types=1);

namespace Sunmerce\RemoteGallery\Plugin\Adminhtml\Product\Listing;

use Sunmerce\RemoteGallery\Model\Config;
use Sunmerce\RemoteGallery\Model\RemoteImageUrlProvider;
use Sunmerce\RemoteGallery\Model\UrlTransformer;
use Magento\Catalog\Ui\Component\Listing\Columns\Thumbnail;
use Magento\Framework\DataObject;

/**
 * Substitutes remote gallery images into the admin product grid thumbnail column.
 */
class ThumbnailPlugin
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
     * @param Thumbnail $subject
     * @param array $result
     * @return array
     */
    public function afterPrepareDataSource(Thumbnail $subject, array $result): array
    {
        if (!$this->config->isEnabled() || empty($result['data']['items'])) {
            return $result;
        }

        $fieldName = $subject->getData('name');
        foreach ($result['data']['items'] as &$item) {
            if (!is_array($item)) {
                continue;
            }

            $productData = new DataObject($item);
            $thumbnailUrl = $this->remoteImageUrlProvider->getUrlForProductData(
                $productData,
                UrlTransformer::ROLE_LISTING
            );
            if ($thumbnailUrl === null) {
                continue;
            }

            $item[$fieldName . '_src'] = $thumbnailUrl;

            $previewUrl = $this->remoteImageUrlProvider->getUrlForProductData(
                $productData,
                UrlTransformer::ROLE_IMAGE
            );
            if ($previewUrl !== null) {
                $item[$fieldName . '_orig_src'] = $previewUrl;
            }
        }
        unset($item);

        return $result;
    }
}