<?php
declare(strict_types=1);

namespace Sunmerce\RemoteGallery\Test\Unit\Model;

use Sunmerce\RemoteGallery\Model\Config;
use Sunmerce\RemoteGallery\Model\Source\OptimizationLocation;
use Sunmerce\RemoteGallery\Model\UrlTransformer;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class UrlTransformerTest extends TestCase
{
    /**
     * @var Config|MockObject
     */
    private $config;

    /**
     * @var UrlTransformer
     */
    private $urlTransformer;

    protected function setUp(): void
    {
        $this->config = $this->createMock(Config::class);
        $this->urlTransformer = new UrlTransformer($this->config);
    }

    public function testTransformReplacesPlaceholder(): void
    {
        $this->config->method('getOptimizationOptions')
            ->with(UrlTransformer::ROLE_IMAGE, 3)
            ->willReturn('w_800');
        $this->config->method('getOptimizationLocation')
            ->with(3)
            ->willReturn(OptimizationLocation::PLACEHOLDER);

        $this->assertSame(
            'https://cdn.example.com/w_800/catalog/product/image.jpg',
            $this->urlTransformer->transform(
                'https://cdn.example.com/{OPTIMIZE_OPTIONS}/catalog/product/image.jpg',
                UrlTransformer::ROLE_IMAGE,
                3
            )
        );
    }

    public function testTransformInsertsOptionsAfterDomain(): void
    {
        $this->config->method('getOptimizationOptions')
            ->willReturn('/w_300/');
        $this->config->method('getOptimizationLocation')
            ->willReturn(OptimizationLocation::AFTER_DOMAIN);

        $this->assertSame(
            'https://cdn.example.com:8443/w_300/catalog/product/image.jpg?version=1#preview',
            $this->urlTransformer->transform(
                'https://cdn.example.com:8443/catalog/product/image.jpg?version=1#preview',
                UrlTransformer::ROLE_THUMB
            )
        );
    }

    public function testTransformAppendsOptionsToQueryBeforeFragment(): void
    {
        $this->config->method('getOptimizationOptions')
            ->willReturn('?width=300&height=400');
        $this->config->method('getOptimizationLocation')
            ->willReturn(OptimizationLocation::APPEND_QUERY);

        $this->assertSame(
            'https://cdn.example.com/image.jpg?existing=1&width=300&height=400#zoom',
            $this->urlTransformer->transform(
                'https://cdn.example.com/image.jpg?existing=1#zoom',
                UrlTransformer::ROLE_THUMB
            )
        );
    }

    public function testTransformRemovesPlaceholderWhenOptionsAreEmpty(): void
    {
        $this->config->method('getOptimizationOptions')
            ->willReturn('');
        $this->config->method('getOptimizationLocation')
            ->willReturn(OptimizationLocation::AFTER_DOMAIN);

        $this->assertSame(
            'https://cdn.example.com//image.jpg',
            $this->urlTransformer->transform(
                'https://cdn.example.com/{OPTIMIZE_OPTIONS}/image.jpg',
                UrlTransformer::ROLE_FULL
            )
        );
    }
}