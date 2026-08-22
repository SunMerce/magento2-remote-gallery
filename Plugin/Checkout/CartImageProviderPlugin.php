<?php
declare(strict_types=1);

namespace Sunmerce\RemoteGallery\Plugin\Checkout;

use Sunmerce\RemoteGallery\Model\Config;
use Sunmerce\RemoteGallery\Model\RemoteImageUrlProvider;
use Sunmerce\RemoteGallery\Model\UrlTransformer;
use Magento\Catalog\Model\Product\Configuration\Item\ItemInterface as ConfigurationItemInterface;
use Magento\Checkout\Model\Cart\ImageProvider;
use Magento\Quote\Api\CartItemRepositoryInterface;

/**
 * Substitutes checkout cart image-provider URLs.
 */
class CartImageProviderPlugin
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var RemoteImageUrlProvider
     */
    private $remoteImageUrlProvider;

    /**
     * @var CartItemRepositoryInterface
     */
    private $cartItemRepository;

    /**
     * @param Config $config
     * @param RemoteImageUrlProvider $remoteImageUrlProvider
     * @param CartItemRepositoryInterface $cartItemRepository
     */
    public function __construct(
        Config $config,
        RemoteImageUrlProvider $remoteImageUrlProvider,
        CartItemRepositoryInterface $cartItemRepository
    ) {
        $this->config = $config;
        $this->remoteImageUrlProvider = $remoteImageUrlProvider;
        $this->cartItemRepository = $cartItemRepository;
    }

    /**
     * @param ImageProvider $subject
     * @param array $result
     * @param int|string $cartId
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetImages(ImageProvider $subject, array $result, $cartId): array
    {
        if (!$this->config->isEnabled()) {
            return $result;
        }

        foreach ($this->cartItemRepository->getList($cartId) as $cartItem) {
            $itemId = $cartItem->getItemId();
            if (!isset($result[$itemId]['src']) || !$cartItem instanceof ConfigurationItemInterface) {
                continue;
            }

            $url = $this->remoteImageUrlProvider->getUrlForItem($cartItem, UrlTransformer::ROLE_THUMB);
            if ($url !== null) {
                $result[$itemId]['src'] = $url;
            }
        }

        return $result;
    }
}
