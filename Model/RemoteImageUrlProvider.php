<?php
declare(strict_types=1);

namespace Sunmerce\RemoteGallery\Model;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Configuration\Item\ItemInterface as ConfigurationItemInterface;
use Magento\Catalog\Model\Product\Configuration\Item\ItemResolverInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Model\Quote\Item\Option;

/**
 * Resolves the primary remote gallery image URL for a product.
 */
class RemoteImageUrlProvider
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
     * @var ItemResolverInterface
     */
    private $itemResolver;

    /**
     * @param RemoteGalleryProcessor $processor
     * @param UrlTransformer $urlTransformer
     * @param ProductRepositoryInterface $productRepository
     * @param ItemResolverInterface $itemResolver
     */
    public function __construct(
        RemoteGalleryProcessor $processor,
        UrlTransformer $urlTransformer,
        ProductRepositoryInterface $productRepository,
        ItemResolverInterface $itemResolver
    ) {
        $this->processor = $processor;
        $this->urlTransformer = $urlTransformer;
        $this->productRepository = $productRepository;
        $this->itemResolver = $itemResolver;
    }

    /**
     * Get the first remote image URL for a product and transform it for the requested role.
     *
     * @param Product $product
     * @param string $role
     * @return string|null
     */
    public function getUrl(Product $product, string $role): ?string
    {
        $images = $this->getImages($product);
        if (!$images) {
            return null;
        }

        return $this->urlTransformer->transform($images[0]['url'], $role);
    }

    /**
     * Get the first remote image URL for a quote/configuration item.
     *
     * @param ConfigurationItemInterface $item
     * @param string $role
     * @return string|null
     */
    public function getUrlForItem(ConfigurationItemInterface $item, string $role): ?string
    {
        foreach ($this->getPreferredProducts($item) as $product) {
            $url = $this->getUrl($product, $role);
            if ($url !== null) {
                return $url;
            }
        }

        return $this->getUrl($this->itemResolver->getFinalProduct($item), $role);
    }

    /**
     * Resolve the quote item product whose remote image should be shown.
     *
     * @param ConfigurationItemInterface $item
     * @param string $role
     * @return Product|null
     */
    public function getProductForItem(ConfigurationItemInterface $item, string $role): ?Product
    {
        foreach ($this->getPreferredProducts($item) as $product) {
            if ($this->getUrl($product, $role) !== null) {
                return $product;
            }
        }

        $product = $this->itemResolver->getFinalProduct($item);

        return $this->getUrl($product, $role) !== null ? $product : null;
    }

    /**
     * @param ConfigurationItemInterface $item
     * @return Product|null
     */
    private function getConfiguredChildProduct(ConfigurationItemInterface $item): ?Product
    {
        $option = $item->getOptionByCode('simple_product');
        if ($option instanceof Option && $option->getProduct() instanceof Product) {
            return $option->getProduct();
        }

        return null;
    }

    /**
     * @param ConfigurationItemInterface $item
     * @return Product|null
     */
    private function getGroupedChildProduct(ConfigurationItemInterface $item): ?Product
    {
        $option = $item->getOptionByCode('product_type');
        $product = $item->getProduct();

        return $option instanceof Option
            && $option->getProduct() instanceof Product
            && $product instanceof Product
            && (int)$option->getProduct()->getId() !== (int)$product->getId()
            ? $product
            : null;
    }

    /**
     * @param ConfigurationItemInterface $item
     * @return Product[]
     */
    private function getPreferredProducts(ConfigurationItemInterface $item): array
    {
        $products = [];
        $configuredChildProduct = $this->getConfiguredChildProduct($item);
        if ($configuredChildProduct !== null) {
            $products[] = $configuredChildProduct;
        }

        $groupedChildProduct = $this->getGroupedChildProduct($item);
        if ($groupedChildProduct !== null) {
            $products[] = $groupedChildProduct;
        }

        return $products;
    }

    /**
     * Get the first remote image URL from product-like row data.
     *
     * @param DataObject $productData
     * @param string $role
     * @return string|null
     */
    public function getUrlForProductData(DataObject $productData, string $role): ?string
    {
        $images = $this->getImages($productData);
        if (!$images) {
            return null;
        }

        return $this->urlTransformer->transform($images[0]['url'], $role);
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
}
