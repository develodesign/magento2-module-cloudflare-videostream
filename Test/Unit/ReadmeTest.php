<?php
/**
 * Copyright © Develo Design. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Develo\CloudflareVideo\Test\Unit;

use PHPUnit\Framework\TestCase;

class ReadmeTest extends TestCase
{
    private string $readmePath;
    private string $readmeContent;

    protected function setUp(): void
    {
        $this->readmePath = realpath(__DIR__ . '/../..') . '/README.md';
        $this->readmeContent = is_file($this->readmePath)
            ? (string) file_get_contents($this->readmePath)
            : '';
    }

    public function testItShipsAReadmeMdAtTheModuleRoot(): void
    {
        $this->assertFileExists($this->readmePath, 'README.md must exist at the module root');
    }

    public function testItDocumentsInstallationAndModuleEnableCommandsIncludingCacheFlush(): void
    {
        $this->assertStringContainsString(
            'module:enable Develo_CloudflareVideo',
            $this->readmeContent,
            'README must document the module:enable command'
        );
        $this->assertStringContainsString(
            'cache:flush',
            $this->readmeContent,
            'README must document the cache:flush command'
        );
    }

    public function testItStatesThatNoSeparateConfigurationIsRequired(): void
    {
        $this->assertMatchesRegularExpression(
            '/no\s+(?:separate\s+)?configuration/i',
            $this->readmeContent,
            'README must state that no separate configuration is required'
        );
    }

    public function testItListsAllFourSupportedCloudflareUrlHostPatternsAndTheBareUidForm(): void
    {
        $this->assertStringContainsString(
            'customer-',
            $this->readmeContent,
            'README must list the customer-<code>.cloudflarestream.com host pattern'
        );
        $this->assertStringContainsString(
            'watch.cloudflarestream.com',
            $this->readmeContent,
            'README must list the watch.cloudflarestream.com host pattern'
        );
        $this->assertStringContainsString(
            'iframe.videodelivery.net',
            $this->readmeContent,
            'README must list the iframe.videodelivery.net host pattern'
        );
        $this->assertStringContainsString(
            'videodelivery.net',
            $this->readmeContent,
            'README must list the videodelivery.net host pattern'
        );
        $this->assertStringContainsString(
            '32',
            $this->readmeContent,
            'README must document the bare 32-hex-character UID form'
        );
    }

    public function testItExplicitlyNotesThatOnlyPublicCloudflareStreamVideosAreSupportedSignedUrlsUnsupported(): void
    {
        $this->assertStringContainsString(
            'public',
            $this->readmeContent,
            'README must state public video support'
        );
        $this->assertStringContainsString(
            'signed',
            $this->readmeContent,
            'README must explicitly mention signed URLs are not supported'
        );
    }

    public function testItWarnsThatThePhpUidExtractorRegexAndTheJsRegexesAdminMixinAndGalleryOverrideMustStayFunctionallyEquivalent(): void
    {
        $this->assertStringContainsString(
            'UidExtractor',
            $this->readmeContent,
            'README must reference the PHP UidExtractor class'
        );
        $this->assertStringContainsString(
            'get-video-information-mixin',
            $this->readmeContent,
            'README must reference the JS admin mixin file'
        );
        $this->assertStringContainsString(
            'gallery.phtml',
            $this->readmeContent,
            'README must reference the gallery.phtml storefront override'
        );
        $this->assertStringContainsString(
            'functionally equivalent',
            $this->readmeContent,
            'README must warn that the PHP and JS regexes must stay functionally equivalent'
        );
    }

    public function testItNamesHyvaCompatModuleFallbackAsARequiredUpstreamModule(): void
    {
        $this->assertStringContainsString(
            'Hyva_CompatModuleFallback',
            $this->readmeContent,
            'README must name Hyva_CompatModuleFallback as a required upstream module'
        );
    }
}
