<?php
declare(strict_types=1);

namespace Sunmerce\RemoteGallery\Test\Unit\Plugin\Block\Product;

use Sunmerce\RemoteGallery\Model\Config;
use Sunmerce\RemoteGallery\Model\RemoteImageUrlProvider;
use Sunmerce\RemoteGallery\Model\UrlTransformer;
use Sunmerce\RemoteGallery\Plugin\Block\Product\ImageFactoryPlugin;
use Magento\Catalog\Block\Product\Image as ImageBlock;
use Magento\Catalog\Block\Product\ImageFactory;
use Magento\Catalog\Model\Product;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ImageFactoryPluginTest extends TestCase
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
     * @var ImageFactoryPlugin
     */
    private $plugin;

    protected function setUp(): void
    {
        $this->config = $this->createMock(Config::class);
        $this->remoteImageUrlProvider = $this->createMock(RemoteImageUrlProvider::class);
        $this->plugin = new ImageFactoryPlugin($this->config, $this->remoteImageUrlProvider);
    }

    public function testAfterCreateReturnsOriginalBlockWhenModuleIsDisabled(): void
    {
        $subject = $this->createMock(ImageFactory::class);
        $result = $this->createMock(ImageBlock::class);
        $product = $this->createMock(Product::class);

        $this->config->expects($this->once())
            ->method('isEnabled')
            ->willReturn(false);
        $this->remoteImageUrlProvider->expects($this->never())
            ->method('getUrl');
        $result->expects($this->never())
            ->method('setData');

        $this->assertSame($result, $this->plugin->afterCreate($subject, $result, $product));
    }

    public function testAfterCreateSetsListingImageUrlWhenRemoteImageExists(): void
    {
        $subject = $this->createMock(ImageFactory::class);
        $result = $this->createMock(ImageBlock::class);
        $product = $this->createMock(Product::class);

        $this->config->expects($this->once())
            ->method('isEnabled')
            ->willReturn(true);
        $this->remoteImageUrlProvider->expects($this->once())
            ->method('getUrl')
            ->with($product, UrlTransformer::ROLE_LISTING)
            ->willReturn('https://cdn.example.com/listing.jpg');
        $result->expects($this->once())
            ->method('setData')
            ->with('image_url', 'https://cdn.example.com/listing.jpg')
            ->willReturnSelf();

        $this->assertSame($result, $this->plugin->afterCreate($subject, $result, $product));
    }
}