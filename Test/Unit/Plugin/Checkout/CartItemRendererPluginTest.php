<?php
declare(strict_types=1);

namespace Sunmerce\RemoteGallery\Test\Unit\Plugin\Checkout;

use Sunmerce\RemoteGallery\Model\Config;
use Sunmerce\RemoteGallery\Model\RemoteImageUrlProvider;
use Sunmerce\RemoteGallery\Model\UrlTransformer;
use Sunmerce\RemoteGallery\Plugin\Checkout\CartItemRendererPlugin;
use Magento\Catalog\Model\Product;
use Magento\Checkout\Block\Cart\Item\Renderer;
use Magento\Quote\Model\Quote\Item\AbstractItem;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CartItemRendererPluginTest extends TestCase
{
    /**
     * @var Config|MockObject
     */
    private $config;

    /**
     * @var RemoteImageUrlProvider|MockObject
     */
    private $remoteImageUrlProvider;

    /**
     * @var CartItemRendererPlugin
     */
    private $plugin;

    protected function setUp(): void
    {
        $this->config = $this->createMock(Config::class);
        $this->remoteImageUrlProvider = $this->createMock(RemoteImageUrlProvider::class);
        $this->plugin = new CartItemRendererPlugin($this->config, $this->remoteImageUrlProvider);
    }

    public function testAfterGetProductForThumbnailReturnsOriginalProductWhenModuleIsDisabled(): void
    {
        $subject = $this->createMock(Renderer::class);
        $product = $this->createMock(Product::class);

        $this->config->expects($this->once())
            ->method('isEnabled')
            ->willReturn(false);
        $subject->expects($this->never())
            ->method('getItem');
        $this->remoteImageUrlProvider->expects($this->never())
            ->method('getProductForItem');

        $this->assertSame($product, $this->plugin->afterGetProductForThumbnail($subject, $product));
    }

    public function testAfterGetProductForThumbnailReturnsRemoteItemProductWhenAvailable(): void
    {
        $subject = $this->createMock(Renderer::class);
        $item = $this->createMock(AbstractItem::class);
        $originalProduct = $this->createMock(Product::class);
        $remoteProduct = $this->createMock(Product::class);

        $this->config->expects($this->once())
            ->method('isEnabled')
            ->willReturn(true);
        $subject->expects($this->once())
            ->method('getItem')
            ->willReturn($item);
        $this->remoteImageUrlProvider->expects($this->once())
            ->method('getProductForItem')
            ->with($item, UrlTransformer::ROLE_LISTING)
            ->willReturn($remoteProduct);

        $this->assertSame(
            $remoteProduct,
            $this->plugin->afterGetProductForThumbnail($subject, $originalProduct)
        );
    }

    public function testAfterGetProductForThumbnailReturnsOriginalProductWhenNoRemoteImageExists(): void
    {
        $subject = $this->createMock(Renderer::class);
        $item = $this->createMock(AbstractItem::class);
        $product = $this->createMock(Product::class);

        $this->config->expects($this->once())
            ->method('isEnabled')
            ->willReturn(true);
        $subject->expects($this->once())
            ->method('getItem')
            ->willReturn($item);
        $this->remoteImageUrlProvider->expects($this->once())
            ->method('getProductForItem')
            ->with($item, UrlTransformer::ROLE_LISTING)
            ->willReturn(null);

        $this->assertSame($product, $this->plugin->afterGetProductForThumbnail($subject, $product));
    }
}