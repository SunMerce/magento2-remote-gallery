<?php
declare(strict_types=1);

namespace Sunmerce\RemoteGallery\Model\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * CDN optimization option insertion locations
 */
class OptimizationLocation implements OptionSourceInterface
{
    public const AFTER_DOMAIN = 'after_domain';
    public const APPEND_QUERY = 'append_query';
    public const PLACEHOLDER = 'placeholder';

    /**
     * @inheritdoc
     */
    public function toOptionArray(): array
    {
        return [
            [
                'value' => self::AFTER_DOMAIN,
                'label' => __('After Domain'),
            ],
            [
                'value' => self::APPEND_QUERY,
                'label' => __('Append Query Parameters'),
            ],
            [
                'value' => self::PLACEHOLDER,
                'label' => __('Replace {OPTIMIZE_OPTIONS} Placeholder'),
            ],
        ];
    }
}
