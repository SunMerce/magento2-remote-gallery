<?php
declare(strict_types=1);

namespace Sunmerce\RemoteGallery\Model\Csp;

use Magento\Csp\Api\PolicyCollectorInterface;
use Magento\Csp\Model\Policy\FetchPolicy;
use Sunmerce\RemoteGallery\Model\Config;

/**
 * Adds the configured CDN hosts to the img-src Content-Security-Policy directive
 */
class ImgSrcCollector implements PolicyCollectorInterface
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @param Config $config
     */
    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    /**
     * @inheritdoc
     */
    public function collect(array $defaultPolicies = []): array
    {
        $policies = $defaultPolicies;
        $hosts = $this->config->isEnabled() ? $this->config->getCspImgHosts() : [];

        if ($hosts) {
            $policies[] = new FetchPolicy('img-src', false, $hosts, [], false);
        }

        return $policies;
    }
}
