<?php
/**
 * Copyright © Develo Design. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Develo\CloudflareVideo\Test\Unit\Model;

use Develo\CloudflareVideo\Model\UidExtractor;
use PHPUnit\Framework\TestCase;

class UidExtractorTest extends TestCase
{
    private UidExtractor $extractor;

    protected function setUp(): void
    {
        $this->extractor = new UidExtractor();
    }

    public function testItExtractsUidFromACustomerCodeUrlEndingInIframe(): void
    {
        $url = 'https://customer-abc123.cloudflarestream.com/31c9291ab41fac05471db4e73aa11717/iframe';

        $result = $this->extractor->extract($url);

        $this->assertSame('31c9291ab41fac05471db4e73aa11717', $result);
    }

    public function testItExtractsUidFromACustomerCodeUrlEndingInWatch(): void
    {
        $url = 'https://customer-abc123.cloudflarestream.com/31c9291ab41fac05471db4e73aa11717/watch';

        $result = $this->extractor->extract($url);

        $this->assertSame('31c9291ab41fac05471db4e73aa11717', $result);
    }

    public function testItExtractsUidFromACustomerCodeUrlEndingInManifestVideoM3u8(): void
    {
        $url = 'https://customer-abc123.cloudflarestream.com/31c9291ab41fac05471db4e73aa11717/manifest/video.m3u8';

        $result = $this->extractor->extract($url);

        $this->assertSame('31c9291ab41fac05471db4e73aa11717', $result);
    }

    public function testItExtractsUidFromACustomerCodeUrlWithATrailingQueryString(): void
    {
        $url = 'https://customer-abc123.cloudflarestream.com/31c9291ab41fac05471db4e73aa11717/iframe?clientBandwidthHint=10';

        $result = $this->extractor->extract($url);

        $this->assertSame('31c9291ab41fac05471db4e73aa11717', $result);
    }

    public function testItExtractsUidFromAnIframeVideodeliveryNetUrl(): void
    {
        $url = 'https://iframe.videodelivery.net/31c9291ab41fac05471db4e73aa11717/iframe';

        $result = $this->extractor->extract($url);

        $this->assertSame('31c9291ab41fac05471db4e73aa11717', $result);
    }

    public function testItExtractsUidFromAWatchCloudflarestreamComUrl(): void
    {
        $url = 'https://watch.cloudflarestream.com/31c9291ab41fac05471db4e73aa11717/watch';

        $result = $this->extractor->extract($url);

        $this->assertSame('31c9291ab41fac05471db4e73aa11717', $result);
    }

    public function testItReturnsABare32HexCharStringUnchangedAsTheUid(): void
    {
        $uid = '31c9291ab41fac05471db4e73aa11717';

        $result = $this->extractor->extract($uid);

        $this->assertSame('31c9291ab41fac05471db4e73aa11717', $result);
    }

    public function testItReturnsNullForACustomerCodeUrlPointingAtThumbnailsThumbnailJpg(): void
    {
        $url = 'https://customer-abc123.cloudflarestream.com/31c9291ab41fac05471db4e73aa11717/thumbnails/thumbnail.jpg';

        $result = $this->extractor->extract($url);

        $this->assertNull($result);
    }

    public function testItReturnsNullForAYoutubeUrl(): void
    {
        $url = 'https://www.youtube.com/watch?v=dQw4w9WgXcQ';

        $result = $this->extractor->extract($url);

        $this->assertNull($result);
    }

    public function testItReturnsNullForAVimeoUrl(): void
    {
        $url = 'https://vimeo.com/123456789';

        $result = $this->extractor->extract($url);

        $this->assertNull($result);
    }

    public function testItReturnsNullForAnEmptyOrNonStringInput(): void
    {
        $this->assertNull($this->extractor->extract(null));
        $this->assertNull($this->extractor->extract(''));
    }

    public function testItReturnsNullForAUidWithWrongLengthOrNonHexCharacters(): void
    {
        // too short
        $this->assertNull($this->extractor->extract('31c9291ab41fac05471db4e73aa1171'));
        // too long
        $this->assertNull($this->extractor->extract('31c9291ab41fac05471db4e73aa117177'));
        // non-hex chars (uppercase)
        $this->assertNull($this->extractor->extract('31c9291ab41fac05471db4e73aa1171G'));
        // non-hex chars (special)
        $this->assertNull($this->extractor->extract('31c9291ab41fac05471db4e73aa1171!'));
    }
}
