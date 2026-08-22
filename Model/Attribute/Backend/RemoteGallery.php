<?php
declare(strict_types=1);

namespace Sunmerce\RemoteGallery\Model\Attribute\Backend;

use Magento\Eav\Model\Entity\Attribute\Backend\AbstractBackend;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\Serializer\Json;

/**
 * Backend model for the remote_gallery product attribute.
 *
 * Stores an array of rows [{image_url, name, position}, ...] as JSON.
 */
class RemoteGallery extends AbstractBackend
{
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
     * Sanitize row data and encode to JSON before saving
     *
     * @param \Magento\Framework\DataObject $object
     * @return $this
     * @throws LocalizedException
     */
    public function beforeSave($object)
    {
        $attrCode = $this->getAttribute()->getAttributeCode();
        if (!$object->hasData($attrCode)) {
            return $this;
        }

        $value = $object->getData($attrCode);
        if (is_array($value)) {
            $rows = $this->sanitizeRows($value);
            $object->setData($attrCode, $rows ? $this->serializer->serialize($rows) : null);
        }

        return $this;
    }

    /**
     * Decode JSON to an array of rows after loading
     *
     * @param \Magento\Framework\DataObject $object
     * @return $this
     */
    public function afterLoad($object)
    {
        parent::afterLoad($object);
        $attrCode = $this->getAttribute()->getAttributeCode();
        $value = $object->getData($attrCode);

        if (is_string($value) && $value !== '') {
            try {
                $object->setData($attrCode, $this->serializer->unserialize($value));
            } catch (\InvalidArgumentException $e) {
                $object->setData($attrCode, []);
            }
        }

        return $this;
    }

    /**
     * Filter out empty rows, validate URLs and keep only known row keys
     *
     * @param array $value
     * @return array
     * @throws LocalizedException
     */
    private function sanitizeRows(array $value): array
    {
        $rows = [];
        foreach ($value as $row) {
            if (!is_array($row)) {
                continue;
            }
            $url = trim((string)($row['image_url'] ?? ''));
            if ($url === '') {
                continue;
            }
            if (!filter_var($url, FILTER_VALIDATE_URL) || !preg_match('#^https?://#i', $url)) {
                throw new LocalizedException(
                    __('Remote Gallery: "%1" is not a valid http(s) image URL.', $url)
                );
            }
            $rows[] = [
                'image_url' => $url,
                'name' => trim((string)($row['name'] ?? '')),
                'position' => (int)($row['position'] ?? count($rows)),
            ];
        }

        usort(
            $rows,
            static function (array $a, array $b): int {
                return $a['position'] <=> $b['position'];
            }
        );

        return $rows;
    }
}
