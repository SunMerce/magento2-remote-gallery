<?php
declare(strict_types=1);

namespace Sunmerce\RemoteGallery\Test\Unit\Model;

use Sunmerce\RemoteGallery\Model\RemoteGalleryProcessor;
use Magento\Framework\DataObject;
use Magento\Framework\Serialize\Serializer\Json;
use PHPUnit\Framework\TestCase;

class RemoteGalleryProcessorTest extends TestCase
{
    /**
     * @var RemoteGalleryProcessor
     */
    private $processor;

    protected function setUp(): void
    {
        $this->processor = new RemoteGalleryProcessor(new Json());
    }

    public function testGetImagesNormalizesValidArrayRows(): void
    {
        $product = new DataObject([
            RemoteGalleryProcessor::ATTRIBUTE_CODE => [
                ['image_url' => 'ftp://example.com/image.jpg', 'name' => 'Ignored', 'position' => 1],
                ['image_url' => ' https://cdn.example.com/b.jpg ', 'name' => ' Second ', 'position' => 20],
                ['image_url' => 'http://cdn.example.com/a.jpg', 'name' => 'First', 'position' => 10],
                ['name' => 'Missing URL'],
                'invalid row',
            ],
        ]);

        $this->assertSame(
            [
                [
                    'url' => 'http://cdn.example.com/a.jpg',
                    'name' => 'First',
                    'position' => 10,
                ],
                [
                    'url' => 'https://cdn.example.com/b.jpg',
                    'name' => 'Second',
                    'position' => 20,
                ],
            ],
            $this->processor->getImages($product)
        );
    }

    public function testGetImagesUnserializesJsonValue(): void
    {
        $product = new DataObject([
            RemoteGalleryProcessor::ATTRIBUTE_CODE => '[{"image_url":"https://cdn.example.com/image.jpg"}]',
        ]);

        $this->assertSame(
            [
                [
                    'url' => 'https://cdn.example.com/image.jpg',
                    'name' => '',
                    'position' => 0,
                ],
            ],
            $this->processor->getImages($product)
        );
    }

    public function testGetImagesReturnsEmptyArrayForInvalidJson(): void
    {
        $product = new DataObject([
            RemoteGalleryProcessor::ATTRIBUTE_CODE => '{invalid json',
        ]);

        $this->assertSame([], $this->processor->getImages($product));
    }
}