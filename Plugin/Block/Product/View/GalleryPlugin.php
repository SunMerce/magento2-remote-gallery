<?php
declare(strict_types=1);

namespace Sunmerce\RemoteGallery\Plugin\Block\Product\View;

use Sunmerce\RemoteGallery\Model\Config;
use Sunmerce\RemoteGallery\Model\RemoteGalleryProcessor;
use Sunmerce\RemoteGallery\Model\UrlTransformer;
use Magento\Catalog\Block\Product\View\Gallery;
use Magento\Catalog\Model\Product;
use Magento\Framework\Data\Collection;
use Magento\Framework\Data\CollectionFactory;
use Magento\Framework\DataObject;
use Magento\ProductVideo\Block\Product\View\Gallery as ProductVideoGallery;

/**
 * Substitutes remote CDN images into the product page gallery JSON
 */
class GalleryPlugin
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var RemoteGalleryProcessor
     */
    private $processor;

    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * @var UrlTransformer
     */
    private $urlTransformer;

    /**
     * @param Config $config
     * @param RemoteGalleryProcessor $processor
     * @param CollectionFactory $collectionFactory
     * @param UrlTransformer $urlTransformer
     */
    public function __construct(
        Config $config,
        RemoteGalleryProcessor $processor,
        CollectionFactory $collectionFactory,
        UrlTransformer $urlTransformer
    ) {
        $this->config = $config;
        $this->processor = $processor;
        $this->collectionFactory = $collectionFactory;
        $this->urlTransformer = $urlTransformer;
    }

    /**
     * Replace gallery images JSON with remote gallery data when available
     *
     * @param Gallery $subject
     * @param string $result
     * @return string
     */
    public function afterGetGalleryImagesJson(Gallery $subject, $result)
    {
        if (!$this->config->isEnabled()) {
            return $result;
        }

        $product = $subject->getProduct();
        $images = $this->processor->getImages($product);
        if (!$images) {
            return $result;
        }

        $items = $this->getRemoteGalleryItems($product, $images);

        $nativeItems = json_decode($result, true);
        if (is_array($nativeItems)) {
            foreach ($nativeItems as $nativeItem) {
                if (!is_array($nativeItem)) {
                    continue;
                }

                $isVideo = ($nativeItem['type'] ?? '') === 'video'
                    || !empty($nativeItem['videoUrl']);
                if ($isVideo) {
                    $items[] = $nativeItem;
                }
            }
        }

        return json_encode($items);
    }

    /**
     * Build Fotorama image items from normalized remote gallery rows.
     *
     * @param Product $product
     * @param array $images
     * @return array
     */
    private function getRemoteGalleryItems(Product $product, array $images): array
    {
        $items = [];
        foreach ($images as $index => $image) {
            $items[] = [
                'thumb' => $this->urlTransformer->transform($image['url'], UrlTransformer::ROLE_THUMB),
                'img' => $this->urlTransformer->transform($image['url'], UrlTransformer::ROLE_IMAGE),
                'full' => $this->urlTransformer->transform($image['url'], UrlTransformer::ROLE_FULL),
                'caption' => $image['name'] !== '' ? $image['name'] : $product->getName(),
                'position' => (string)$image['position'],
                'isMain' => $index === 0,
                'type' => 'image',
                'videoUrl' => null,
            ];
        }

        return $items;
    }

    /**
     * Keep ProductVideo metadata aligned with the remote gallery item order.
     *
     * @param ProductVideoGallery $subject
     * @param string $result
     * @return string
     */
    public function afterGetMediaGalleryDataJson(ProductVideoGallery $subject, $result)
    {
        if (!$this->config->isEnabled()) {
            return $result;
        }

        $images = $this->processor->getImages($subject->getProduct());
        if (!$images) {
            return $result;
        }

        $nativeItems = json_decode($result, true);
        if (!is_array($nativeItems)) {
            return $result;
        }

        $items = [];
        foreach ($images as $index => $image) {
            $items[] = [
                'mediaType' => 'image',
                'videoUrl' => null,
                'isBase' => $index === 0,
            ];
        }

        foreach ($nativeItems as $nativeItem) {
            if (!is_array($nativeItem)) {
                continue;
            }

            $isVideo = ($nativeItem['mediaType'] ?? '') === 'external-video'
                || !empty($nativeItem['videoUrl']);
            if ($isVideo) {
                $nativeItem['isBase'] = false;
                $items[] = $nativeItem;
            }
        }

        return json_encode($items);
    }

    /**
     * Replace the image used by gallery.phtml before Fotorama initializes.
     *
     * @param Gallery $subject
     * @param Collection $result
     * @return Collection
     */
    public function afterGetGalleryImages(Gallery $subject, $result)
    {
        if (!$this->config->isEnabled() || !$result instanceof Collection) {
            return $result;
        }

        $images = $this->processor->getImages($subject->getProduct());
        if (!$images) {
            return $result;
        }

        $product = $subject->getProduct();
        $remoteMainImage = new DataObject($this->getRemoteGalleryImageData($images[0]));

        $remoteResult = $this->collectionFactory->create();
        $remoteResult->addItem($remoteMainImage);
        foreach ($result as $galleryItem) {
            $remoteResult->addItem($galleryItem);
        }

        return $remoteResult;
    }

    /**
     * Build gallery collection data for a remote gallery row.
     *
     * @param array $image
     * @return array
     */
    private function getRemoteGalleryImageData(array $image): array
    {
        return [
            'file' => $image['url'],
            'medium_image_url' => $this->urlTransformer->transform($image['url'], UrlTransformer::ROLE_IMAGE),
            'small_image_url' => $this->urlTransformer->transform($image['url'], UrlTransformer::ROLE_THUMB),
            'large_image_url' => $this->urlTransformer->transform($image['url'], UrlTransformer::ROLE_FULL),
            'label' => $image['name'],
            'position' => 0,
        ];
    }
}
