<?php
/**
 * Copyright © Develo Design. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Develo\CloudflareVideo\ViewModel;

use Develo\CloudflareVideo\Model\EmbedUrlBuilder;
use Develo\CloudflareVideo\Model\UidExtractor;
use Magento\Framework\DataObject;
use Magento\Framework\View\Element\Block\ArgumentInterface;

class CloudflareVideo implements ArgumentInterface
{
    /**
     * @param UidExtractor $uidExtractor
     * @param EmbedUrlBuilder $embedUrlBuilder
     */
    public function __construct(
        private readonly UidExtractor $uidExtractor,
        private readonly EmbedUrlBuilder $embedUrlBuilder
    ) {
    }

    /**
     * Returns true when the gallery item is an external video recognised as Cloudflare Stream.
     *
     * @param DataObject|null $item Gallery image DataObject.
     * @return bool
     */
    public function isCloudflareVideo(?DataObject $item): bool
    {
        if ($item === null) {
            return false;
        }

        if ($item->getData('media_type') !== 'external-video') {
            return false;
        }

        return $this->uidExtractor->extract($item->getData('video_url')) !== null;
    }

    /**
     * Returns 'cloudflare' when the item is a Cloudflare Stream video, null otherwise.
     *
     * @param DataObject|null $item Gallery image DataObject.
     * @return string|null
     */
    public function getProvider(?DataObject $item): ?string
    {
        return $this->isCloudflareVideo($item) ? 'cloudflare' : null;
    }

    /**
     * Returns the iframe embed URL for a Cloudflare Stream gallery item, null otherwise.
     *
     * @param DataObject|null $item Gallery image DataObject.
     * @return string|null
     */
    public function getEmbedUrl(?DataObject $item): ?string
    {
        if (!$this->isCloudflareVideo($item)) {
            return null;
        }

        $videoUrl = (string) $item->getData('video_url');
        $posterUrl = $item->getData('medium_image_url');

        return $this->embedUrlBuilder->build($videoUrl, $posterUrl ?: null);
    }
}
