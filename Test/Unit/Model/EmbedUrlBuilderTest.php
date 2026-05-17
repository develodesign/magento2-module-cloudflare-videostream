<?php
/**
 * Copyright © Develo Design. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Develo\CloudflareVideo\Test\Unit\Model;

use Develo\CloudflareVideo\Model\EmbedUrlBuilder;
use Develo\CloudflareVideo\Model\UidExtractor;
use PHPUnit\Framework\TestCase;

class EmbedUrlBuilderTest extends TestCase
{
    private const UID = 'deadbeef0123456789abcdef01234567';

    private EmbedUrlBuilder $builder;

    protected function setUp(): void
    {
        $this->builder = new EmbedUrlBuilder(new UidExtractor());
    }

    public function testItPreservesTheCustomerSubdomainFromTheInputUrl(): void
    {
        $result = $this->builder->build('https://customer-abc123.cloudflarestream.com/' . self::UID . '/iframe');

        $this->assertSame(
            'https://customer-abc123.cloudflarestream.com/' . self::UID . '/iframe',
            $result
        );
    }

    public function testItNormalisesTheWatchPathOnACustomerUrlToTheIframePath(): void
    {
        $result = $this->builder->build('https://customer-abc123.cloudflarestream.com/' . self::UID . '/watch');

        $this->assertSame(
            'https://customer-abc123.cloudflarestream.com/' . self::UID . '/iframe',
            $result
        );
    }

    public function testItNormalisesTheManifestPathOnACustomerUrlToTheIframePath(): void
    {
        $result = $this->builder->build(
            'https://customer-abc123.cloudflarestream.com/' . self::UID . '/manifest/video.m3u8'
        );

        $this->assertSame(
            'https://customer-abc123.cloudflarestream.com/' . self::UID . '/iframe',
            $result
        );
    }

    public function testItFallsBackToIframeVideodeliveryNetForAWatchCloudflarestreamComUrl(): void
    {
        $result = $this->builder->build('https://watch.cloudflarestream.com/' . self::UID);

        $this->assertSame('https://iframe.videodelivery.net/' . self::UID, $result);
    }

    public function testItPreservesAnIframeVideodeliveryNetUrl(): void
    {
        $result = $this->builder->build('https://iframe.videodelivery.net/' . self::UID);

        $this->assertSame('https://iframe.videodelivery.net/' . self::UID, $result);
    }

    public function testItCanonicalisesAVideodeliveryNetUrlToIframeVideodeliveryNet(): void
    {
        $result = $this->builder->build('https://videodelivery.net/' . self::UID);

        $this->assertSame('https://iframe.videodelivery.net/' . self::UID, $result);
    }

    public function testItFallsBackToIframeVideodeliveryNetForABareUid(): void
    {
        $result = $this->builder->build(self::UID);

        $this->assertSame('https://iframe.videodelivery.net/' . self::UID, $result);
    }

    public function testItAppendsAUrlEncodedPosterQueryParameterWhenPosterIsGiven(): void
    {
        $result = $this->builder->build(
            'https://customer-abc123.cloudflarestream.com/' . self::UID . '/iframe',
            'https://example.com/poster.jpg?size=large&color=blue'
        );

        $this->assertSame(
            'https://customer-abc123.cloudflarestream.com/' . self::UID . '/iframe'
            . '?poster=' . rawurlencode('https://example.com/poster.jpg?size=large&color=blue'),
            $result
        );
    }

    public function testItOmitsThePosterQueryParameterWhenPosterIsNull(): void
    {
        $result = $this->builder->build('https://customer-abc.cloudflarestream.com/' . self::UID . '/iframe', null);

        $this->assertNotNull($result);
        $this->assertStringNotContainsString('poster', $result);
    }

    public function testItOmitsThePosterQueryParameterWhenPosterIsEmpty(): void
    {
        $result = $this->builder->build('https://customer-abc.cloudflarestream.com/' . self::UID . '/iframe', '');

        $this->assertNotNull($result);
        $this->assertStringNotContainsString('poster', $result);
    }

    public function testItReturnsNullForUnrecognisedInput(): void
    {
        $this->assertNull($this->builder->build('https://www.youtube.com/watch?v=dQw4w9WgXcQ'));
    }

    public function testItReturnsNullForNullInput(): void
    {
        $this->assertNull($this->builder->build(null));
    }

    public function testItReturnsNullForEmptyInput(): void
    {
        $this->assertNull($this->builder->build(''));
    }
}
