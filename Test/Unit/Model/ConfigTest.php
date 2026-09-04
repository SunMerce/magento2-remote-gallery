<?php
declare(strict_types=1);

namespace Sunmerce\RemoteGallery\Test\Unit\Model;

use Sunmerce\RemoteGallery\Model\Config;
use Sunmerce\RemoteGallery\Model\Csp\HostValidator;
use Sunmerce\RemoteGallery\Model\UrlTransformer;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ConfigTest extends TestCase
{
    /**
     * @var ScopeConfigInterface|MockObject
     */
    private $scopeConfig;

    /**
     * @var Config
     */
    private $config;

    protected function setUp(): void
    {
        $this->scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $this->config = new Config($this->scopeConfig, new HostValidator());
    }

    public function testIsEnabledReadsStoreScopedFlag(): void
    {
        $this->scopeConfig->expects($this->once())
            ->method('isSetFlag')
            ->with(Config::XML_PATH_ENABLED, ScopeInterface::SCOPE_STORE, 7)
            ->willReturn(true);

        $this->assertTrue($this->config->isEnabled(7));
    }

    /**
     * @param string $role
     * @param string $path
     */
    #[DataProvider('optimizationOptionsDataProvider')]
    public function testGetOptimizationOptionsReadsPathForRole(string $role, string $path): void
    {
        $this->scopeConfig->expects($this->once())
            ->method('getValue')
            ->with($path, ScopeInterface::SCOPE_STORE, 'default')
            ->willReturn('w_300');

        $this->assertSame('w_300', $this->config->getOptimizationOptions($role, 'default'));
    }

    public static function optimizationOptionsDataProvider(): array
    {
        return [
            [UrlTransformer::ROLE_THUMB, Config::XML_PATH_THUMB_OPTIONS],
            [UrlTransformer::ROLE_IMAGE, Config::XML_PATH_IMAGE_OPTIONS],
            [UrlTransformer::ROLE_FULL, Config::XML_PATH_FULL_OPTIONS],
            [UrlTransformer::ROLE_LISTING, Config::XML_PATH_LISTING_OPTIONS],
        ];
    }

    public function testGetOptimizationOptionsReturnsEmptyStringForUnknownRole(): void
    {
        $this->scopeConfig->expects($this->never())
            ->method('getValue');

        $this->assertSame('', $this->config->getOptimizationOptions('unknown'));
    }

    public function testGetCspImgHostsSplitsTrimsAndDeduplicates(): void
    {
        $this->scopeConfig->expects($this->once())
            ->method('getValue')
            ->with(Config::XML_PATH_CSP_IMG_HOSTS, ScopeInterface::SCOPE_STORE, null)
            ->willReturn("cdn.example.com\nimg.example.com, cdn.example.com\n\n");

        $this->assertSame(['cdn.example.com', 'img.example.com'], $this->config->getCspImgHosts());
    }

    public function testGetCspImgHostsReturnsEmptyArrayWhenNotConfigured(): void
    {
        $this->scopeConfig->expects($this->once())
            ->method('getValue')
            ->with(Config::XML_PATH_CSP_IMG_HOSTS, ScopeInterface::SCOPE_STORE, null)
            ->willReturn(null);

        $this->assertSame([], $this->config->getCspImgHosts());
    }

    public function testGetCspImgHostsFiltersUnsafeEntries(): void
    {
        $this->scopeConfig->expects($this->once())
            ->method('getValue')
            ->with(Config::XML_PATH_CSP_IMG_HOSTS, ScopeInterface::SCOPE_STORE, null)
            ->willReturn("cdn.example.com\n*\n*.example.com\nhttps:\ndata:\ncdn.example.com:443");

        $this->assertSame(
            ['cdn.example.com', 'cdn.example.com:443'],
            $this->config->getCspImgHosts()
        );
    }
}