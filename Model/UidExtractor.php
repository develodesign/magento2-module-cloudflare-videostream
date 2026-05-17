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

    private const CUSTOMER_HOST_PATTERN = '/^customer-[^.]+\.cloudflarestream\.com$/';

    private const RECOGNISED_HOSTS = [
        self::CUSTOMER_HOST_PATTERN,
        '/^watch\.cloudflarestream\.com$/',
        '/^iframe\.videodelivery\.net$/',
        '/^videodelivery\.net$/',
    ];

    private const PLAYABLE_PATH_PATTERN = '/^\/([0-9a-f]{32})(\/(?:iframe|watch|manifest\/.*))?(?:\?.*)?$/';

    /**
     * Parse a Cloudflare Stream input into a structured form.
     *
     * Accepts a full Cloudflare Stream URL or a bare 32-character lowercase
     * hexadecimal UID. Returns ['uid' => string, 'customerHost' => ?string] or
     * null for unrecognised input. customerHost is the original
     * customer-<code>.cloudflarestream.com host when present in the input,
     * otherwise null.
     *
     * @param string|null $input Full URL or bare 32-hex-char UID.
     * @return array{uid: string, customerHost: ?string}|null
     */
    public function parse(?string $input): ?array
    {
        if ($input === null || $input === '') {
            return null;
        }

        if (preg_match(self::UID_PATTERN, $input)) {
            return ['uid' => $input, 'customerHost' => null];
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

        if (!preg_match(self::PLAYABLE_PATH_PATTERN, $pathWithQuery, $matches)) {
            return null;
        }

        return [
            'uid' => $matches[1],
            'customerHost' => preg_match(self::CUSTOMER_HOST_PATTERN, $host) ? $host : null,
        ];
    }

    /**
     * Extract a Cloudflare Stream UID from a URL or bare UID string.
     *
     * @param string|null $input Full URL or bare 32-hex-char UID.
     * @return string|null The 32-character UID, or null if not recognised.
     */
    public function extract(?string $input): ?string
    {
        $parsed = $this->parse($input);
        return $parsed === null ? null : $parsed['uid'];
    }

    /**
     * Check whether the given hostname is a recognised Cloudflare video host.
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
