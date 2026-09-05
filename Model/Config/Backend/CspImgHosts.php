<?php
declare(strict_types=1);

namespace Sunmerce\RemoteGallery\Model\Config\Backend;

use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Value;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Sunmerce\RemoteGallery\Model\Csp\HostValidator;

/**
 * Validates the "Allowed CSP Image Hosts" field before it is persisted
 */
class CspImgHosts extends Value
{
    /**
     * @var HostValidator
     */
    private $hostValidator;

    /**
     * @param Context $context
     * @param Registry $registry
     * @param ScopeConfigInterface $config
     * @param TypeListInterface $cacheTypeList
     * @param HostValidator $hostValidator
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        ScopeConfigInterface $config,
        TypeListInterface $cacheTypeList,
        HostValidator $hostValidator,
        ?AbstractResource $resource = null,
        ?AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct($context, $registry, $config, $cacheTypeList, $resource, $resourceCollection, $data);
        $this->hostValidator = $hostValidator;
    }

    /**
     * Reject unsafe host entries before saving
     *
     * @return $this
     * @throws LocalizedException
     */
    public function beforeSave()
    {
        $value = (string)$this->getValue();
        $lines = preg_split('/\r\n|\r|\n/', $value, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $hosts = array_map('trim', $lines);

        $invalid = $this->hostValidator->getInvalid($hosts);

        if ($invalid) {
            throw new LocalizedException(
                __(
                    'Invalid CSP image host(s): %1. Each line must be a single host (e.g. cdn.example.com).'
                    . ' Wildcards (*), scheme-only sources (e.g. "https:", "data:"), commas, semicolons'
                    . ' and whitespace are not allowed.',
                    implode(', ', $invalid)
                )
            );
        }

        return parent::beforeSave();
    }
}
