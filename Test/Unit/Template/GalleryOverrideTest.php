<?php
/**
 * Copyright © Develo Design. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Develo\CloudflareVideo\Test\Unit\Template;

use PHPUnit\Framework\TestCase;

/**
 * Smoke tests for the gallery.phtml override.
 *
 * These tests load the PHTML as a string and assert presence of integration
 * points. They are NOT behavioural tests of Alpine.js — they are
 * "doesn't compile away" guards.
 */
class GalleryOverrideTest extends TestCase
{
    private const OVERRIDE_PATH = __DIR__ . '/../../../view/frontend/templates/product/view/gallery.phtml';
    private const LAYOUT_XML_PATH = __DIR__ . '/../../../view/frontend/layout/catalog_product_view.xml';

    private string $content;

    protected function setUp(): void
    {
        $this->assertFileExists(
            self::OVERRIDE_PATH,
            'gallery.phtml override must exist inside the module at view/frontend/templates/product/view/gallery.phtml'
        );
        $this->content = file_get_contents(self::OVERRIDE_PATH);
    }

    public function testItPlacesTheGalleryOverrideAtTheModuleTemplatePath(): void
    {
        $this->assertFileExists(
            self::OVERRIDE_PATH,
            'gallery.phtml must exist at app/code/Develo/CloudflareVideo/view/frontend/templates/product/view/gallery.phtml'
        );
    }

    public function testItRegistersTheGalleryTemplateOverrideViaLayoutXml(): void
    {
        $this->assertFileExists(
            self::LAYOUT_XML_PATH,
            'catalog_product_view.xml must exist to override the product.media block template'
        );
        $layoutXml = file_get_contents(self::LAYOUT_XML_PATH);
        $this->assertStringContainsString(
            'product.media',
            $layoutXml,
            'Layout XML must reference the product.media gallery block by name'
        );
        $this->assertStringContainsString(
            'Develo_CloudflareVideo::product/view/gallery.phtml',
            $layoutXml,
            'Layout XML must point product.media at the override template'
        );
    }

    public function testItPreservesTheExistingYoutubeRegexFromTheUpstreamHyvaTemplate(): void
    {
        $this->assertStringContainsString(
            'youtube\.com|youtu\.be|youtube-nocookie.com',
            $this->content,
            'The YouTube regex from the upstream Hyvä template must be preserved'
        );
    }

    public function testItPreservesTheExistingVimeoRegexFromTheUpstreamHyvaTemplate(): void
    {
        $this->assertStringContainsString(
            'vimeo\.com',
            $this->content,
            'The Vimeo regex from the upstream Hyvä template must be preserved'
        );
    }

    public function testItAddsARegexMatchingCloudflarestreamComAndVideodeliveryNetUrls(): void
    {
        $this->assertStringContainsString(
            'cloudflarestream\.com',
            $this->content,
            'A regex matching cloudflarestream.com must be present in the override'
        );
        $this->assertStringContainsString(
            'videodelivery\.net',
            $this->content,
            'A regex matching videodelivery.net must be present in the override'
        );
    }

    public function testItReadsThePerSlideUrlFromImageVideoUrlCamelCaseNotImageVideoUrl(): void
    {
        // The gallery JSON exposes videoUrl (camelCase); the template accesses it via .videoUrl
        $this->assertStringContainsString(
            '.videoUrl',
            $this->content,
            'The template must access the camelCase .videoUrl property to read the per-slide URL'
        );
        $this->assertStringNotContainsString(
            '.video_url',
            $this->content,
            'The template must NOT use .video_url (snake_case)'
        );
    }

    public function testItIncludesAnElseIfBranchForVideoDataTypeCloudflareInActivateVideo(): void
    {
        $this->assertStringContainsString(
            'videoData.type === "cloudflare"',
            $this->content,
            'activateVideo() must include an else-if branch for videoData.type === "cloudflare"'
        );
    }

    public function testItIncludesACloudflarePlayerDivBoundToActiveVideoTypeCloudflare(): void
    {
        $this->assertStringContainsString(
            'id="cloudflare-player"',
            $this->content,
            'A div with id="cloudflare-player" must be present in the template'
        );
        $this->assertStringContainsString(
            'activeVideoType === \'cloudflare\'',
            $this->content,
            'The cloudflare player div must be bound to activeVideoType === \'cloudflare\''
        );
    }

    public function testItDeclaresCloudflareCustomerCodeAsAConstOutsideTheInitGalleryFunctionSoItIsClosedOverByMethodsInside(): void
    {
        // cloudflareCustomerCode must appear BEFORE function initGallery
        $constPos = strpos($this->content, 'const cloudflareCustomerCode');
        $functionPos = strpos($this->content, 'function initGallery');

        $this->assertNotFalse(
            $constPos,
            'cloudflareCustomerCode must be declared as a const'
        );
        $this->assertNotFalse(
            $functionPos,
            'function initGallery must exist in the template'
        );
        $this->assertLessThan(
            $functionPos,
            $constPos,
            'cloudflareCustomerCode const must be declared before function initGallery so it is closed over'
        );
    }

    public function testItEscapesTheCustomerCodeViaEscaperEscapeJs(): void
    {
        $this->assertStringContainsString(
            '$escaper->escapeJs($cloudflareVideo->getCustomerCode())',
            $this->content,
            'The customer code must be escaped via $escaper->escapeJs()'
        );
    }

    public function testItFallsBackToIframeVideodeliveryNetWhenCloudflareCustomerCodeIsEmptyMirrorsEmbedUrlBuilder(): void
    {
        $this->assertStringContainsString(
            'iframe.videodelivery.net',
            $this->content,
            'The template must fall back to iframe.videodelivery.net when cloudflareCustomerCode is empty'
        );
    }

    public function testItUsesImageImgAsTheIframePosterQueryParameter(): void
    {
        $this->assertStringContainsString(
            'image.img',
            $this->content,
            'The template must use image.img as the poster URL for the Cloudflare iframe'
        );
        $this->assertStringContainsString(
            '?poster=',
            $this->content,
            'The iframe URL must include a ?poster= query parameter'
        );
    }
}
