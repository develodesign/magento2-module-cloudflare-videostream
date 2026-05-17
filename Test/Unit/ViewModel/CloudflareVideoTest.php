<?php
/**
 * Copyright © Develo Design. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Develo\CloudflareVideo\Test\Unit\ViewModel;

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
     * @var UidExtractor&MockObject
     */
    private UidExtractor $uidExtractor;

    /**
     * @var EmbedUrlBuilder&MockObject
     */
    private EmbedUrlBuilder $embedUrlBuilder;

    private CloudflareVideo $viewModel;

    protected function setUp(): void
    {
        $this->uidExtractor = $this->createMock(UidExtractor::class);
        $this->embedUrlBuilder = $this->createMock(EmbedUrlBuilder::class);

        $this->viewModel = new CloudflareVideo(
            $this->uidExtractor,
            $this->embedUrlBuilder
        );
    }

    private function makeExternalVideoItem(string $videoUrl): DataObject
    {
        return new DataObject([
            'media_type' => 'external-video',
            'video_url'  => $videoUrl,
        ]);
    }

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

        $this->assertTrue($this->viewModel->isCloudflareVideo($item));
    }

    public function testItDoesNotIdentifyAYoutubeExternalVideoAsCloudflare(): void
    {
        $item = $this->makeExternalVideoItem(self::YOUTUBE_URL);

        $this->uidExtractor
            ->method('extract')
            ->with(self::YOUTUBE_URL)
            ->willReturn(null);

        $this->assertFalse($this->viewModel->isCloudflareVideo($item));
    }

    public function testItDoesNotIdentifyARegularImageGalleryItemAsCloudflare(): void
    {
        $item = $this->makeImageItem();

        $this->uidExtractor
            ->expects($this->never())
            ->method('extract');

        $this->assertFalse($this->viewModel->isCloudflareVideo($item));
    }

    public function testItReturnsFalseForIsCloudflareVideoWhenGivenNull(): void
    {
        $this->uidExtractor
            ->expects($this->never())
            ->method('extract');

        $this->assertFalse($this->viewModel->isCloudflareVideo(null));
    }

    public function testItReturnsTheLiteralStringCloudflareAsProviderForACloudflareVideo(): void
    {
        $item = $this->makeExternalVideoItem(self::CLOUDFLARE_URL);

        $this->uidExtractor
            ->method('extract')
            ->with(self::CLOUDFLARE_URL)
            ->willReturn(self::CLOUDFLARE_UID);

        $this->assertSame('cloudflare', $this->viewModel->getProvider($item));
    }

    public function testItReturnsNullProviderForANonCloudflareItem(): void
    {
        $item = $this->makeExternalVideoItem(self::YOUTUBE_URL);

        $this->uidExtractor
            ->method('extract')
            ->with(self::YOUTUBE_URL)
            ->willReturn(null);

        $this->assertNull($this->viewModel->getProvider($item));
    }

    public function testItReturnsNullProviderWhenGivenNull(): void
    {
        $this->assertNull($this->viewModel->getProvider(null));
    }

    public function testItPassesTheOriginalUrlAndPosterToTheEmbedUrlBuilder(): void
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
            ->expects($this->once())
            ->method('build')
            ->with(self::CLOUDFLARE_URL, $posterUrl)
            ->willReturn(self::EMBED_URL);

        $this->assertSame(self::EMBED_URL, $this->viewModel->getEmbedUrl($item));
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

        $this->assertNull($this->viewModel->getEmbedUrl($item));
    }

    public function testItReturnsNullEmbedUrlWhenGivenNull(): void
    {
        $this->embedUrlBuilder
            ->expects($this->never())
            ->method('build');

        $this->assertNull($this->viewModel->getEmbedUrl(null));
    }
}
