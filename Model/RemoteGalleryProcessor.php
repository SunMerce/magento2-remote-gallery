<?php
declare(strict_types=1);

namespace Sunmerce\RemoteGallery\Model;

use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Serialize\Serializer\Json;

/**
 * Extracts normalized remote gallery image rows from a product
 */
class RemoteGalleryProcessor
{
    public const ATTRIBUTE_CODE = 'remote_gallery';

    /**
     * @var Json
     */
    private $serializer;

    /**
     * @param Json $serializer
     */
    public function __construct(Json $serializer)
    {
        $this->serializer = $serializer;
    }

    /**
     * Get normalized remote gallery images for a product, sorted by position.
     *
     * Handles both a raw JSON string (collection load) and an already
     * unserialized array (single entity load via backend model afterLoad).
     *
     * @param AbstractModel|\Magento\Framework\DataObject $product
     * @return array{url: string, name: string, position: int}[]
     */
    public function getImages($product): array
    {
        $value = $product->getData(self::ATTRIBUTE_CODE);

        if (is_string($value) && $value !== '') {
            try {
                $value = $this->serializer->unserialize($value);
            } catch (\InvalidArgumentException $e) {
                return [];
            }
        }

        if (!is_array($value)) {
            return [];
        }

        $images = [];
        foreach ($value as $row) {
            if (!is_array($row) || empty($row['image_url'])) {
                continue;
            }
            $url = trim((string)$row['image_url']);
            if (!preg_match('#^https?://#i', $url)) {
                continue;
            }
            $images[] = [
                'url' => $url,
                'name' => trim((string)($row['name'] ?? '')),
                'position' => (int)($row['position'] ?? count($images)),
            ];
        }

        usort(
            $images,
            static function (array $a, array $b): int {
                return $a['position'] <=> $b['position'];
            }
        );

        return $images;
    }
}
