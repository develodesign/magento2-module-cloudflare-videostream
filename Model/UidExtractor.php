<?php
/**
 * Copyright © Develo Design. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Develo\CloudflareVideo\Model;

class UidExtractor
{
    private const UID_PATTERN = '/^[0-9a-f]{32}$/';

    private const RECOGNISED_HOSTS = [
        '/^customer-[^.]+\.cloudflarestream\.com$/',
        '/^watch\.cloudflarestream\.com$/',
        '/^iframe\.videodelivery\.net$/',
        '/^videodelivery\.net$/',
    ];

    private const PLAYABLE_PATH_PATTERN = '/^\/([0-9a-f]{32})(\/(?:iframe|watch|manifest\/.*))?(?:\?.*)?$/';

    /**
     * Extract a Cloudflare Stream UID from a URL or bare UID string.
     *
     * Accepts a full Cloudflare Stream URL or a bare 32-character lowercase
     * hexadecimal UID. Returns the UID string or null for unrecognised input.
     *
     * @param string|null $input Full URL or bare 32-hex-char UID.
     * @return string|null The 32-character UID, or null if not recognised.
     */
    public function extract(?string $input): ?string
    {
        if ($input === null || $input === '') {
            return null;
        }

        if (preg_match(self::UID_PATTERN, $input)) {
            return $input;
        }

        // phpcs:ignore Magento2.Functions.DiscouragedFunction
        $parsed = parse_url($input);
        if ($parsed === false || empty($parsed['host'])) {
            return null;
        }

        $host = $parsed['host'];
        if (!$this->isRecognisedHost($host)) {
            return null;
        }

        $path = $parsed['path'] ?? '';
        $query = isset($parsed['query']) ? '?' . $parsed['query'] : '';
        $pathWithQuery = rtrim($path, '/') . $query;

        if (preg_match(self::PLAYABLE_PATH_PATTERN, $pathWithQuery, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Check whether the given hostname is a recognised Cloudflare video host.
     *
     * @param string $host Hostname extracted from the URL.
     * @return bool True when the host matches a known Cloudflare video domain.
     */
    private function isRecognisedHost(string $host): bool
    {
        foreach (self::RECOGNISED_HOSTS as $pattern) {
            if (preg_match($pattern, $host)) {
                return true;
            }
        }
        return false;
    }
}
