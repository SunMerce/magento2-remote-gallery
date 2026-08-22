<?php
declare(strict_types=1);

namespace Sunmerce\RemoteGallery\Ui\DataProvider\Product\Form\Modifier;

use Sunmerce\RemoteGallery\Model\RemoteGalleryProcessor;
use Magento\Catalog\Model\Locator\LocatorInterface;
use Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\AbstractModifier;
use Magento\Ui\Component\Container;
use Magento\Ui\Component\DynamicRows;
use Magento\Ui\Component\Form\Element\DataType\Number;
use Magento\Ui\Component\Form\Element\DataType\Text;
use Magento\Ui\Component\Form\Element\Input;
use Magento\Ui\Component\Form\Field;
use Magento\Ui\Component\Form\Fieldset;

/**
 * Adds the "Remote Gallery" editable rows section to the admin product form
 */
class RemoteGallery extends AbstractModifier
{
    private const FIELDSET_NAME = 'remote_gallery_fieldset';

    /**
     * @var LocatorInterface
     */
    private $locator;

    /**
     * @var RemoteGalleryProcessor
     */
    private $processor;

    /**
     * @param LocatorInterface $locator
     * @param RemoteGalleryProcessor $processor
     */
    public function __construct(
        LocatorInterface $locator,
        RemoteGalleryProcessor $processor
    ) {
        $this->locator = $locator;
        $this->processor = $processor;
    }

    /**
     * @inheritdoc
     */
    public function modifyData(array $data)
    {
        $product = $this->locator->getProduct();
        $productId = $product->getId();
        if (!$productId) {
            return $data;
        }

        $rows = [];
        foreach ($this->processor->getImages($product) as $index => $image) {
            $rows[] = [
                'image_url' => $image['url'],
                'name' => $image['name'],
                'position' => $image['position'],
                'record_id' => $index,
            ];
        }

        $data[$productId][self::DATA_SOURCE_DEFAULT][RemoteGalleryProcessor::ATTRIBUTE_CODE] = $rows;

        return $data;
    }

    /**
     * @inheritdoc
     */
    public function modifyMeta(array $meta)
    {
        $meta[static::FIELDSET_NAME] = [
            'arguments' => [
                'data' => [
                    'config' => [
                        'label' => __('Remote Gallery'),
                        'componentType' => Fieldset::NAME,
                        'dataScope' => self::DATA_SCOPE_PRODUCT,
                        'collapsible' => true,
                        'opened' => false,
                        'sortOrder' => 100,
                    ],
                ],
            ],
            'children' => [
                RemoteGalleryProcessor::ATTRIBUTE_CODE => $this->getDynamicRowsStructure(),
            ],
        ];

        return $meta;
    }

    /**
     * Build dynamicRows meta for the remote gallery rows
     *
     * @return array
     */
    private function getDynamicRowsStructure(): array
    {
        return [
            'arguments' => [
                'data' => [
                    'config' => [
                        'componentType' => DynamicRows::NAME,
                        'label' => __('Remote Images'),
                        'renderDefaultRecord' => false,
                        'recordTemplate' => 'record',
                        'dataScope' => '',
                        'columnsHeader' => true,
                        'deleteProperty' => false,
                        'addButtonLabel' => __('Add Image'),
                        'dndConfig' => [
                            'enabled' => true,
                        ],
                        'required' => false,
                        'sortOrder' => 10,
                    ],
                ],
            ],
            'children' => [
                'record' => [
                    'arguments' => [
                        'data' => [
                            'config' => [
                                'componentType' => Container::NAME,
                                'component' => 'Magento_Ui/js/dynamic-rows/record',
                                'isTemplate' => true,
                                'is_collection' => true,
                                'dataScope' => '',
                            ],
                        ],
                    ],
                    'children' => [
                        'image_url' => [
                            'arguments' => [
                                'data' => [
                                    'config' => [
                                        'componentType' => Field::NAME,
                                        'formElement' => Input::NAME,
                                        'dataType' => Text::NAME,
                                        'label' => __('Image URL'),
                                        'dataScope' => 'image_url',
                                        'validation' => [
                                            'required-entry' => true,
                                            'validate-url' => true,
                                        ],
                                        'sortOrder' => 10,
                                    ],
                                ],
                            ],
                        ],
                        'name' => [
                            'arguments' => [
                                'data' => [
                                    'config' => [
                                        'componentType' => Field::NAME,
                                        'formElement' => Input::NAME,
                                        'dataType' => Text::NAME,
                                        'label' => __('Name'),
                                        'dataScope' => 'name',
                                        'sortOrder' => 20,
                                    ],
                                ],
                            ],
                        ],
                        'position' => [
                            'arguments' => [
                                'data' => [
                                    'config' => [
                                        'componentType' => Field::NAME,
                                        'formElement' => Input::NAME,
                                        'dataType' => Number::NAME,
                                        'dataScope' => 'position',
                                        'visible' => false,
                                        'sortOrder' => 30,
                                    ],
                                ],
                            ],
                        ],
                        'actionDelete' => [
                            'arguments' => [
                                'data' => [
                                    'config' => [
                                        'componentType' => 'actionDelete',
                                        'dataType' => Text::NAME,
                                        'label' => ' ',
                                        'sortOrder' => 40,
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }
}
