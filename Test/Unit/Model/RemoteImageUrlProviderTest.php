<?php
declare(strict_types=1);

namespace Sunmerce\RemoteGallery\Test\Unit\Model;

use Sunmerce\RemoteGallery\Model\RemoteGalleryProcessor;
use Sunmerce\RemoteGallery\Model\RemoteImageUrlProvider;
use Sunmerce\RemoteGallery\Model\UrlTransformer;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Configuration\Item\ItemInterface as ConfigurationItemInterface;
use Magento\Catalog\Model\Product\Configuration\Item\ItemResolverInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Model\Quote\Item\Option;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class RemoteImageUrlProviderTest extends TestCase
{
    /**
     * @var RemoteGalleryProcessor|MockObject
     */
    private $processor;

    /**
     * @var UrlTransformer|MockObject
     */
    private $urlTransformer;

    /**
     * @var ProductRepositoryInterface|MockObject
     */
    private $productRepository;

    /**
     * @var ItemResolverInterface|MockObject
     */
    private $itemResolver;

    /**
     * @var RemoteImageUrlProvider
     */
    private $provider;

    protected function setUp(): void
    {
        $this->processor = $this->createMock(RemoteGalleryProcessor::class);
        $this->urlTransformer = $this->createMock(UrlTransformer::class);
        $this->productRepository = $this->createMock(ProductRepositoryInterface::class);
        $this->itemResolver = $this->createMock(ItemResolverInterface::class);
        $this->provider = new RemoteImageUrlProvider(
            $this->processor,
            $this->urlTransformer,
            $this->productRepository,
            $this->itemResolver
        );
    }

    public function testGetUrlTransformsFirstRemoteImage(): void
    {
        $product = $this->createMock(Product::class);
        $this->processor->expects($this->once())
            ->method('getImages')
            ->with($product)
            ->willReturn([
                ['url' => 'https://cdn.example.com/first.jpg'],
                ['url' => 'https://cdn.example.com/second.jpg'],
            ]);
        $this->urlTransformer->expects($this->once())
            ->method('transform')
            ->with('https://cdn.example.com/first.jpg', UrlTransformer::ROLE_LISTING)
            ->willReturn('https://cdn.example.com/w_300/first.jpg');

        $this->assertSame(
            'https://cdn.example.com/w_300/first.jpg',
            $this->provider->getUrl($product, UrlTransformer::ROLE_LISTING)
        );
    }

    public function testGetUrlReloadsProductWhenRemoteGalleryAttributeWasNotSelected(): void
    {
        $product = $this->createMock(Product::class);
        $reloadedProduct = $this->createMock(Product::class);

        $product->method('getId')->willReturn(10);
        $product->method('hasData')
            ->with(RemoteGalleryProcessor::ATTRIBUTE_CODE)
            ->willReturn(false);
        $product->method('getStoreId')->willReturn(5);

        $this->processor->expects($this->exactly(2))
            ->method('getImages')
            ->willReturnOnConsecutiveCalls(
                [],
                [['url' => 'https://cdn.example.com/reloaded.jpg']]
            );
        $this->productRepository->expects($this->once())
            ->method('getById')
            ->with(10, false, 5)
            ->willReturn($reloadedProduct);
        $this->urlTransformer->expects($this->once())
            ->method('transform')
            ->with('https://cdn.example.com/reloaded.jpg', UrlTransformer::ROLE_THUMB)
            ->willReturn('https://cdn.example.com/thumb/reloaded.jpg');

        $this->assertSame(
            'https://cdn.example.com/thumb/reloaded.jpg',
            $this->provider->getUrl($product, UrlTransformer::ROLE_THUMB)
        );
    }

    public function testGetUrlReturnsNullWhenReloadedProductDoesNotExist(): void
    {
        $product = $this->createMock(Product::class);
        $product->method('getId')->willReturn(20);
        $product->method('hasData')
            ->with(RemoteGalleryProcessor::ATTRIBUTE_CODE)
            ->willReturn(false);
        $product->method('getStoreId')->willReturn(null);

        $this->processor->expects($this->once())
            ->method('getImages')
            ->with($product)
            ->willReturn([]);
        $this->productRepository->expects($this->once())
            ->method('getById')
            ->with(20, false, null)
            ->willThrowException(new NoSuchEntityException(__('Product does not exist')));
        $this->urlTransformer->expects($this->never())
            ->method('transform');

        $this->assertNull($this->provider->getUrl($product, UrlTransformer::ROLE_IMAGE));
    }

    public function testGetUrlForItemUsesFinalProduct(): void
    {
        $item = $this->createMock(ConfigurationItemInterface::class);
        $product = $this->createMock(Product::class);

        $item->method('getOptionByCode')->willReturnMap([
            ['simple_product', null],
            ['product_type', null],
        ]);

        $this->itemResolver->expects($this->once())
            ->method('getFinalProduct')
            ->with($item)
            ->willReturn($product);
        $this->processor->expects($this->once())
            ->method('getImages')
            ->with($product)
            ->willReturn([['url' => 'https://cdn.example.com/item.jpg']]);
        $this->urlTransformer->expects($this->once())
            ->method('transform')
            ->with('https://cdn.example.com/item.jpg', UrlTransformer::ROLE_THUMB)
            ->willReturn('https://cdn.example.com/thumb/item.jpg');

        $this->assertSame(
            'https://cdn.example.com/thumb/item.jpg',
            $this->provider->getUrlForItem($item, UrlTransformer::ROLE_THUMB)
        );
    }

    public function testGetUrlForItemPrefersConfiguredChildProductRemoteImage(): void
    {
        $item = $this->createMock(ConfigurationItemInterface::class);
        $option = $this->createMock(Option::class);
        $childProduct = $this->createMock(Product::class);

        $item->method('getOptionByCode')->willReturnMap([
            ['simple_product', $option],
            ['product_type', null],
        ]);
        $option->method('getProduct')->willReturn($childProduct);
        $this->itemResolver->expects($this->never())->method('getFinalProduct');
        $this->processor->expects($this->once())
            ->method('getImages')
            ->with($childProduct)
            ->willReturn([['url' => 'https://cdn.example.com/child.jpg']]);
        $this->urlTransformer->expects($this->once())
            ->method('transform')
            ->with('https://cdn.example.com/child.jpg', UrlTransformer::ROLE_THUMB)
            ->willReturn('https://cdn.example.com/thumb/child.jpg');

        $this->assertSame(
            'https://cdn.example.com/thumb/child.jpg',
            $this->provider->getUrlForItem($item, UrlTransformer::ROLE_THUMB)
        );
    }

    public function testGetUrlForItemFallsBackToFinalProductWhenChildHasNoRemoteImage(): void
    {
        $item = $this->createMock(ConfigurationItemInterface::class);
        $option = $this->createMock(Option::class);
        $childProduct = $this->createMock(Product::class);
        $finalProduct = $this->createMock(Product::class);

        $item->method('getOptionByCode')->willReturnMap([
            ['simple_product', $option],
            ['product_type', null],
        ]);
        $option->method('getProduct')->willReturn($childProduct);
        $this->itemResolver->expects($this->once())
            ->method('getFinalProduct')
            ->with($item)
            ->willReturn($finalProduct);
        $this->processor->expects($this->exactly(2))
            ->method('getImages')
            ->willReturnMap([
                [$childProduct, []],
                [$finalProduct, [['url' => 'https://cdn.example.com/final.jpg']]],
            ]);
        $this->urlTransformer->expects($this->once())
            ->method('transform')
            ->with('https://cdn.example.com/final.jpg', UrlTransformer::ROLE_THUMB)
            ->willReturn('https://cdn.example.com/thumb/final.jpg');

        $this->assertSame(
            'https://cdn.example.com/thumb/final.jpg',
            $this->provider->getUrlForItem($item, UrlTransformer::ROLE_THUMB)
        );
    }

    public function testGetUrlForItemPrefersGroupedChildProductRemoteImage(): void
    {
        $item = $this->createMock(ConfigurationItemInterface::class);
        $option = $this->createMock(Option::class);
        $childProduct = $this->createMock(Product::class);
        $parentProduct = $this->createMock(Product::class);

        $item->method('getOptionByCode')->willReturnMap([
            ['simple_product', null],
            ['product_type', $option],
        ]);
        $item->method('getProduct')->willReturn($childProduct);
        $childProduct->method('getId')->willReturn(10);
        $parentProduct->method('getId')->willReturn(20);
        $option->method('getProduct')->willReturn($parentProduct);
        $this->itemResolver->expects($this->never())->method('getFinalProduct');
        $this->processor->expects($this->once())
            ->method('getImages')
            ->with($childProduct)
            ->willReturn([['url' => 'https://cdn.example.com/grouped-child.jpg']]);
        $this->urlTransformer->expects($this->once())
            ->method('transform')
            ->with('https://cdn.example.com/grouped-child.jpg', UrlTransformer::ROLE_THUMB)
            ->willReturn('https://cdn.example.com/thumb/grouped-child.jpg');

        $this->assertSame(
            'https://cdn.example.com/thumb/grouped-child.jpg',
            $this->provider->getUrlForItem($item, UrlTransformer::ROLE_THUMB)
        );
    }

    public function testGetProductForItemPrefersGroupedChildProductRemoteImage(): void
    {
        $item = $this->createMock(ConfigurationItemInterface::class);
        $option = $this->createMock(Option::class);
        $childProduct = $this->createMock(Product::class);
        $parentProduct = $this->createMock(Product::class);

        $item->method('getOptionByCode')->willReturnMap([
            ['simple_product', null],
            ['product_type', $option],
        ]);
        $item->method('getProduct')->willReturn($childProduct);
        $childProduct->method('getId')->willReturn(10);
        $parentProduct->method('getId')->willReturn(20);
        $option->method('getProduct')->willReturn($parentProduct);
        $this->itemResolver->expects($this->never())->method('getFinalProduct');
        $this->processor->expects($this->once())
            ->method('getImages')
            ->with($childProduct)
            ->willReturn([['url' => 'https://cdn.example.com/grouped-child.jpg']]);
        $this->urlTransformer->expects($this->once())
            ->method('transform')
            ->with('https://cdn.example.com/grouped-child.jpg', UrlTransformer::ROLE_LISTING)
            ->willReturn('https://cdn.example.com/listing/grouped-child.jpg');

        $this->assertSame(
            $childProduct,
            $this->provider->getProductForItem($item, UrlTransformer::ROLE_LISTING)
        );
    }

    public function testGetProductForItemFallsBackToFinalProductWhenChildHasNoRemoteImage(): void
    {
        $item = $this->createMock(ConfigurationItemInterface::class);
        $option = $this->createMock(Option::class);
        $childProduct = $this->createMock(Product::class);
        $finalProduct = $this->createMock(Product::class);

        $item->method('getOptionByCode')->willReturnMap([
            ['simple_product', $option],
            ['product_type', null],
        ]);
        $option->method('getProduct')->willReturn($childProduct);
        $this->itemResolver->expects($this->once())
            ->method('getFinalProduct')
            ->with($item)
            ->willReturn($finalProduct);
        $this->processor->expects($this->exactly(2))
            ->method('getImages')
            ->willReturnMap([
                [$childProduct, []],
                [$finalProduct, [['url' => 'https://cdn.example.com/final.jpg']]],
            ]);
        $this->urlTransformer->expects($this->once())
            ->method('transform')
            ->with('https://cdn.example.com/final.jpg', UrlTransformer::ROLE_LISTING)
            ->willReturn('https://cdn.example.com/listing/final.jpg');

        $this->assertSame(
            $finalProduct,
            $this->provider->getProductForItem($item, UrlTransformer::ROLE_LISTING)
        );
    }
}