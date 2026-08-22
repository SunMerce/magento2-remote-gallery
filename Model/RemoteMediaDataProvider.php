<?php
declare(strict_types=1);

namespace Sunmerce\RemoteGallery\Model;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Builds frontend media payloads from remote gallery rows.
 */
class RemoteMediaDataProvider
{
    /**
     * @var RemoteGalleryProcessor
     */
    private $processor;

    /**
     * @var UrlTransformer
     */
    private $urlTransformer;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @param RemoteGalleryProcessor $processor
     * @param UrlTransformer $urlTransformer
     * @param ProductRepositoryInterface $productRepository
     */
    public function __construct(
        RemoteGalleryProcessor $processor,
        UrlTransformer $urlTransformer,
        ProductRepositoryInterface $productRepository
    ) {
        $this->processor = $processor;
        $this->urlTransformer = $urlTransformer;
        $this->productRepository = $productRepository;
    }

    /**
     * Build Fotorama image items for configurable product JSON.
     *
     * @param Product $product
     * @return array
     */
    public function getFotoramaImages(Product $product): array
    {
        $images = $this->getImages($product);
        if (!$images) {
            return [];
        }

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
     * Build swatch AJAX media response data.
     *
     * @param Product $product
     * @return array
     */
    public function getSwatchMediaGallery(Product $product): array
    {
        $images = $this->getImages($product);
        if (!$images) {
            return [];
        }

        $gallery = [];
        foreach ($images as $index => $image) {
            $gallery[$index] = $this->getSwatchImageData($image, $index);
        }

        $result = $gallery[0];
        $result['gallery'] = $gallery;

        return $result;
    }

    /**
     * Get remote image rows, reloading product data only when the attribute was not selected.
     *
     * @param DataObject $product
     * @return array
     */
    private function getImages(DataObject $product): array
    {
        $images = $this->processor->getImages($product);
        $productId = (int)($product->getId() ?: $product->getData('entity_id'));
        if ($images || $product->hasData(RemoteGalleryProcessor::ATTRIBUTE_CODE) || !$productId) {
            return $images;
        }

        try {
            $storeId = $product->getStoreId() ?: null;
            $product = $this->productRepository->getById($productId, false, $storeId);
        } catch (NoSuchEntityException $e) {
            return [];
        }

        return $this->processor->getImages($product);
    }

    /**
     * @param array $image
     * @param int $index
     * @return array
     */
    private function getSwatchImageData(array $image, int $index): array
    {
        return [
            'large' => $this->urlTransformer->transform($image['url'], UrlTransformer::ROLE_FULL),
            'medium' => $this->urlTransformer->transform($image['url'], UrlTransformer::ROLE_IMAGE),
            'small' => $this->urlTransformer->transform($image['url'], UrlTransformer::ROLE_THUMB),
            'position' => (string)$image['position'],
            'isMain' => $index === 0,
        ];
    }
}