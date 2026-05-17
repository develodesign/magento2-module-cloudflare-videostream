<?php
/**
 * Copyright © Develo Design. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Develo\CloudflareVideo\Test\Unit\ViewModel;

use Develo\CloudflareVideo\Model\Config;
use Develo\CloudflareVideo\Model\EmbedUrlBuilder;
use Develo\CloudflareVideo\Model\UidExtractor;
use Develo\CloudflareVideo\ViewModel\CloudflareVideo;
use Magento\Framework\DataObject;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CloudflareVideoTest extends TestCase
{
    private const CLOUDFLARE_URL = 'https://customer-abc.cloudflarestream.com/abcdef1234567890abcdef1234567890/iframe';
    private const CLOUDFLARE_UID = 'abcdef1234567890abcdef1234567890';
    private const YOUTUBE_URL = 'https://www.youtube.com/watch?v=dQw4w9WgXcQ';
    private const EMBED_URL = 'https://customer-abc.cloudflarestream.com/abcdef1234567890abcdef1234567890/iframe';

    /**
     * @var Config&MockObject
     */
    private Config $config;

    /**
     * @var UidExtractor&MockObject
     */
    private UidExtractor $uidExtractor;

    /**
     * @var EmbedUrlBuilder&MockObject
     */
    private EmbedUrlBuilder $embedUrlBuilder;

    /**
     * @var CloudflareVideo
     */
    private CloudflareVideo $viewModel;

    protected function setUp(): void
    {
        $this->config = $this->createMock(Config::class);
        $this->uidExtractor = $this->createMock(UidExtractor::class);
        $this->embedUrlBuilder = $this->createMock(EmbedUrlBuilder::class);

        $this->viewModel = new CloudflareVideo(
            $this->config,
            $this->uidExtractor,
            $this->embedUrlBuilder
        );
    }

    /**
     * Creates a DataObject gallery item simulating an external video.
     */
    private function makeExternalVideoItem(string $videoUrl): DataObject
    {
        return new DataObject([
            'media_type' => 'external-video',
            'video_url'  => $videoUrl,
        ]);
    }

    /**
     * Creates a DataObject gallery item simulating a plain image.
     */
    private function makeImageItem(): DataObject
    {
        return new DataObject([
            'media_type' => 'image',
        ]);
    }

    public function testItIdentifiesACloudflareExternalVideoByMediaTypeAndUrl(): void
    {
        $item = $this->makeExternalVideoItem(self::CLOUDFLARE_URL);

        $this->uidExtractor
            ->method('extract')
            ->with(self::CLOUDFLARE_URL)
            ->willReturn(self::CLOUDFLARE_UID);

        $result = $this->viewModel->isCloudflareVideo($item);

        $this->assertTrue($result);
    }

    public function testItDoesNotIdentifyAYoutubeExternalVideoAsCloudflare(): void
    {
        $item = $this->makeExternalVideoItem(self::YOUTUBE_URL);

        $this->uidExtractor
            ->method('extract')
            ->with(self::YOUTUBE_URL)
            ->willReturn(null);

        $result = $this->viewModel->isCloudflareVideo($item);

        $this->assertFalse($result);
    }

    public function testItDoesNotIdentifyARegularImageGalleryItemAsCloudflare(): void
    {
        $item = $this->makeImageItem();

        $this->uidExtractor
            ->expects($this->never())
            ->method('extract');

        $result = $this->viewModel->isCloudflareVideo($item);

        $this->assertFalse($result);
    }

    public function testItReturnsFalseForIsCloudflareVideoWhenGivenNull(): void
    {
        $this->uidExtractor
            ->expects($this->never())
            ->method('extract');

        $result = $this->viewModel->isCloudflareVideo(null);

        $this->assertFalse($result);
    }

    public function testItReturnsTheLiteralStringCloudflareAsProviderForACloudflareVideo(): void
    {
        $item = $this->makeExternalVideoItem(self::CLOUDFLARE_URL);

        $this->uidExtractor
            ->method('extract')
            ->with(self::CLOUDFLARE_URL)
            ->willReturn(self::CLOUDFLARE_UID);

        $result = $this->viewModel->getProvider($item);

        $this->assertSame('cloudflare', $result);
    }

    public function testItReturnsNullProviderForANonCloudflareItem(): void
    {
        $item = $this->makeExternalVideoItem(self::YOUTUBE_URL);

        $this->uidExtractor
            ->method('extract')
            ->with(self::YOUTUBE_URL)
            ->willReturn(null);

        $result = $this->viewModel->getProvider($item);

        $this->assertNull($result);
    }

    public function testItReturnsNullProviderWhenGivenNull(): void
    {
        $result = $this->viewModel->getProvider(null);

        $this->assertNull($result);
    }

    public function testItReturnsAnIframeEmbedUrlWhenGivenACloudflareGalleryItem(): void
    {
        $posterUrl = 'https://example.com/poster.jpg';
        $item = new DataObject([
            'media_type'       => 'external-video',
            'video_url'        => self::CLOUDFLARE_URL,
            'medium_image_url' => $posterUrl,
        ]);

        $this->uidExtractor
            ->method('extract')
            ->with(self::CLOUDFLARE_URL)
            ->willReturn(self::CLOUDFLARE_UID);

        $this->embedUrlBuilder
            ->method('build')
            ->with(self::CLOUDFLARE_UID, $posterUrl)
            ->willReturn(self::EMBED_URL);

        $result = $this->viewModel->getEmbedUrl($item);

        $this->assertSame(self::EMBED_URL, $result);
    }

    public function testItReturnsNullEmbedUrlForANonCloudflareItem(): void
    {
        $item = $this->makeExternalVideoItem(self::YOUTUBE_URL);

        $this->uidExtractor
            ->method('extract')
            ->with(self::YOUTUBE_URL)
            ->willReturn(null);

        $this->embedUrlBuilder
            ->expects($this->never())
            ->method('build');

        $result = $this->viewModel->getEmbedUrl($item);

        $this->assertNull($result);
    }

    public function testItReturnsNullEmbedUrlWhenGivenNull(): void
    {
        $this->embedUrlBuilder
            ->expects($this->never())
            ->method('build');

        $result = $this->viewModel->getEmbedUrl(null);

        $this->assertNull($result);
    }

    public function testItProxiesGetCustomerCodeThroughModelConfig(): void
    {
        $this->config
            ->expects($this->once())
            ->method('getCustomerCode')
            ->willReturn('my-customer-code');

        $result = $this->viewModel->getCustomerCode();

        $this->assertSame('my-customer-code', $result);
    }
}
