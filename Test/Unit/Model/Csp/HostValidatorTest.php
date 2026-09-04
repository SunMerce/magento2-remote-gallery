<?php
declare(strict_types=1);

namespace Sunmerce\RemoteGallery\Test\Unit\Model\Csp;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Sunmerce\RemoteGallery\Model\Csp\HostValidator;

class HostValidatorTest extends TestCase
{
    /**
     * @var HostValidator
     */
    private $validator;

    protected function setUp(): void
    {
        $this->validator = new HostValidator();
    }

    /**
     * @param string $host
     */
    #[DataProvider('validHostDataProvider')]
    public function testValidHostsAreAccepted(string $host): void
    {
        $this->assertTrue($this->validator->isValid($host));
    }

    public static function validHostDataProvider(): array
    {
        return [
            'plain host' => ['cdn.example.com'],
            'subdomain' => ['images.cdn.example.co.uk'],
            'host with port' => ['cdn.example.com:8443'],
            'ipv4' => ['192.168.0.10'],
            'ipv4 with port' => ['192.168.0.10:8080'],
        ];
    }

    /**
     * @param string $host
     */
    #[DataProvider('invalidHostDataProvider')]
    public function testUnsafeHostsAreRejected(string $host): void
    {
        $this->assertFalse($this->validator->isValid($host));
    }

    public static function invalidHostDataProvider(): array
    {
        return [
            'empty' => [''],
            'wildcard only' => ['*'],
            'wildcard subdomain' => ['*.example.com'],
            'wildcard suffix' => ['cdn.*'],
            'https scheme only' => ['https:'],
            'http scheme only' => ['http:'],
            'data scheme only' => ['data:'],
            'blob scheme only' => ['blob:'],
            'comma' => ['cdn.example.com,img.example.com'],
            'semicolon' => ['cdn.example.com;'],
            'space' => ['cdn. example.com'],
            'tab' => ["cdn.\texample.com"],
            'newline' => ["cdn.\nexample.com"],
        ];
    }

    public function testGetValidKeepsOnlySafeHosts(): void
    {
        $this->assertSame(
            ['cdn.example.com', 'cdn.example.com:443'],
            $this->validator->getValid(['cdn.example.com', '*', 'https:', 'cdn.example.com:443', 'a b'])
        );
    }

    public function testGetInvalidReturnsUnsafeHosts(): void
    {
        $this->assertSame(
            ['*', 'https:', 'a b'],
            $this->validator->getInvalid(['cdn.example.com', '*', 'https:', 'a b'])
        );
    }
}
