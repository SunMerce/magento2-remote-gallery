<?php
declare(strict_types=1);

namespace Sunmerce\RemoteGallery\Test\Unit\Model;

use Sunmerce\RemoteGallery\Model\RemoteGalleryProcessor;
use Sunmerce\RemoteGallery\Model\RemoteMediaDataProvider;
use Sunmerce\RemoteGallery\Model\UrlTransformer;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class RemoteMediaDataProviderTest extends TestCase
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
     * @var RemoteMediaDataProvider
     */
    private $provider;

    protected function setUp(): void
    {
        $this->processor = $this->createMock(RemoteGalleryProcessor::class);
        $this->urlTransformer = $this->createMock(UrlTransformer::class);
        $this->productRepository = $this->createMock(ProductRepositoryInterface::class);
        $this->provider = new RemoteMediaDataProvider(
            $this->processor,
            $this->urlTransformer,
            $this->productRepository
        );
    }

    public function testGetFotoramaImagesBuildsConfigurableGalleryItems(): void
    {
        $product = $this->createMock(Product::class);
        $product->method('getName')->willReturn('Variant');
        $this->processor->method('getImages')->willReturn([
            ['url' => 'https://cdn.example.com/a.jpg', 'name' => '', 'position' => 20],
            ['url' => 'https://cdn.example.com/b.jpg', 'name' => 'Back', 'position' => 30],
        ]);
        $this->urlTransformer->method('transform')->willReturnCallback(
            static function (string $url, string $role): string {
                return $url . '?' . $role;
            }
        );

        $this->assertSame(
            [
                [
                    'thumb' => 'https://cdn.example.com/a.jpg?thumb',
                    'img' => 'https://cdn.example.com/a.jpg?image',
                    'full' => 'https://cdn.example.com/a.jpg?full',
                    'caption' => 'Variant',
                    'position' => '20',
                    'isMain' => true,
                    'type' => 'image',
                    'videoUrl' => null,
                ],
                [
                    'thumb' => 'https://cdn.example.com/b.jpg?thumb',
                    'img' => 'https://cdn.example.com/b.jpg?image',
                    'full' => 'https://cdn.example.com/b.jpg?full',
                    'caption' => 'Back',
                    'position' => '30',
                    'isMain' => false,
                    'type' => 'image',
                    'videoUrl' => null,
                ],
            ],
            $this->provider->getFotoramaImages($product)
        );
    }

    public function testGetSwatchMediaGalleryBuildsAjaxResponseShape(): void
    {
        $product = $this->createMock(Product::class);
        $this->processor->method('getImages')->willReturn([
            ['url' => 'https://cdn.example.com/a.jpg', 'name' => '', 'position' => 10],
        ]);
        $this->urlTransformer->method('transform')->willReturnCallback(
            static function (string $url, string $role): string {
                return $url . '?' . $role;
            }
        );

        $this->assertSame(
            [
                'large' => 'https://cdn.example.com/a.jpg?full',
                'medium' => 'https://cdn.example.com/a.jpg?image',
                'small' => 'https://cdn.example.com/a.jpg?thumb',
                'position' => '10',
                'isMain' => true,
                'gallery' => [
                    [
                        'large' => 'https://cdn.example.com/a.jpg?full',
                        'medium' => 'https://cdn.example.com/a.jpg?image',
                        'small' => 'https://cdn.example.com/a.jpg?thumb',
                        'position' => '10',
                        'isMain' => true,
                    ],
                ],
            ],
            $this->provider->getSwatchMediaGallery($product)
        );
    }
}