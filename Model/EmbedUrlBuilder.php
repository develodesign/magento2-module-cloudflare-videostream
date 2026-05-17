<?php
/**
 * Copyright © Develo Design. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Develo\CloudflareVideo\Model;

use InvalidArgumentException;

class EmbedUrlBuilder
{
    private const CUSTOMER_BASE_URL = 'https://customer-%s.cloudflarestream.com/%s/iframe';
    private const FALLBACK_BASE_URL = 'https://iframe.videodelivery.net/%s';

    /**
     * @param Config $config
     */
    public function __construct(
        private readonly Config $config
    ) {
    }

    /**
     * Builds the Cloudflare Stream iframe embed URL for the given UID.
     *
     * @param string $uid Validated Cloudflare Stream UID.
     * @param string|null $posterUrl Optional poster image URL.
     * @return string
     * @throws InvalidArgumentException When $uid is empty.
     */
    public function build(string $uid, ?string $posterUrl = null): string
    {
        if ($uid === '') {
            throw new InvalidArgumentException('UID must not be empty.');
        }

        $customerCode = $this->config->getCustomerCode();

        if ($customerCode !== '') {
            $url = sprintf(self::CUSTOMER_BASE_URL, $customerCode, $uid);
        } else {
            $url = sprintf(self::FALLBACK_BASE_URL, $uid);
        }

        if ($posterUrl !== null && $posterUrl !== '') {
            $url .= '?poster=' . rawurlencode($posterUrl);
        }

        return $url;
    }
}
