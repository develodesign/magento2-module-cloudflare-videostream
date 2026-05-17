<?php
/**
 * Copyright © Develo Design. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Develo\CloudflareVideo\Model;

class EmbedUrlBuilder
{
    private const FALLBACK_BASE_URL = 'https://iframe.videodelivery.net/%s';
    private const CUSTOMER_BASE_URL_TEMPLATE = 'https://%s/%s/iframe';

    /**
     * @param UidExtractor $uidExtractor
     */
    public function __construct(
        private readonly UidExtractor $uidExtractor
    ) {
    }

    /**
     * Build the Cloudflare Stream iframe embed URL from the original input.
     *
     * Preserves the customer-<code>.cloudflarestream.com host when present in
     * the input, falls back to iframe.videodelivery.net otherwise.
     *
     * @param string|null $input Full Cloudflare URL or bare 32-hex-char UID.
     * @param string|null $posterUrl Optional poster image URL.
     * @return string|null The iframe URL, or null if the input is not a recognised Cloudflare source.
     */
    public function build(?string $input, ?string $posterUrl = null): ?string
    {
        $parsed = $this->uidExtractor->parse($input);
        if ($parsed === null) {
            return null;
        }

        $url = $parsed['customerHost'] !== null
            ? sprintf(self::CUSTOMER_BASE_URL_TEMPLATE, $parsed['customerHost'], $parsed['uid'])
            : sprintf(self::FALLBACK_BASE_URL, $parsed['uid']);

        if ($posterUrl !== null && $posterUrl !== '') {
            $url .= '?poster=' . rawurlencode($posterUrl);
        }

        return $url;
    }
}
