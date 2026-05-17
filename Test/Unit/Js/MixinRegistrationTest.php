<?php
/**
 * Copyright © Develo Design. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Develo\CloudflareVideo\Test\Unit\Js;

use PHPUnit\Framework\TestCase;

class MixinRegistrationTest extends TestCase
{
    /**
     * Absolute path to the module root (app/code/Develo/CloudflareVideo).
     * __DIR__ is Test/Unit/Js — three levels up gives the module root.
     */
    private static function moduleRoot(): string
    {
        return dirname(__DIR__, 3);
    }

    /**
     * @var string
     */
    private string $requirejsSource;

    /**
     * @var string
     */
    private string $mixinSource;

    protected function setUp(): void
    {
        $requirejsConfigFile = self::moduleRoot() . '/view/adminhtml/requirejs-config.js';
        $mixinFile           = self::moduleRoot() . '/view/adminhtml/web/js/get-video-information-mixin.js';

        $this->requirejsSource = is_file($requirejsConfigFile)
            ? (string) file_get_contents($requirejsConfigFile)
            : '';

        $this->mixinSource = is_file($mixinFile)
            ? (string) file_get_contents($mixinFile)
            : '';
    }

    // -------------------------------------------------------------------------
    // requirejs-config.js assertions
    // -------------------------------------------------------------------------

    // phpcs:ignore Generic.Files.LineLength.TooLong
    public function testItRegistersAMixinInRequirejsConfigUnderConfigMixinsForMagentoProductVideoJsGetVideoInformation(): void
    {
        // RequireJS mixin registration lives inside config: { mixins: { … } }
        $this->assertStringContainsString(
            'mixins',
            $this->requirejsSource,
            'requirejs-config.js must declare a mixins block inside config'
        );
        $this->assertStringContainsString(
            'Magento_ProductVideo/js/get-video-information',
            $this->requirejsSource,
            'requirejs-config.js must target Magento_ProductVideo/js/get-video-information in mixins'
        );
    }

    public function testItMapsTheMixinToThePathDeveloCloudflareVideoJsGetVideoInformationMixin(): void
    {
        $this->assertStringContainsString(
            'Develo_CloudflareVideo/js/get-video-information-mixin',
            $this->requirejsSource,
            'requirejs-config.js must map to Develo_CloudflareVideo/js/get-video-information-mixin'
        );
    }

    // -------------------------------------------------------------------------
    // Regex assertions — extract the JS regex literal from source and test it
    // via preg_match after converting to a PHP-compatible pattern.
    // -------------------------------------------------------------------------

    /**
     * Extract the CLOUDFLARE_URL_RE literal from the JS source and convert it
     * to a PHP PCRE pattern string so we can run fixture URLs against it.
     */
    private function extractCloudflareRegex(): string
    {
        // Match a JS regex literal assigned to CLOUDFLARE_URL_RE
        // Capture everything between the outer /…/ delimiters (non-greedy).
        $matched = preg_match(
            '/CLOUDFLARE_URL_RE\s*=\s*\/((?:[^\/\\\\]|\\\\.)*)\//',
            $this->mixinSource,
            $matches
        );
        $this->assertSame(
            1,
            $matched,
            'get-video-information-mixin.js must define a CLOUDFLARE_URL_RE regex literal'
        );
        // Wrap as a PHP PCRE pattern with the same delimiter
        return '/' . $matches[1] . '/';
    }

    public function testItDefinesACloudflareUrlRegexThatMatchesCustomerCodeCloudflarestreamComUrls(): void
    {
        $pattern = $this->extractCloudflareRegex();
        $url = 'https://customer-abc123.cloudflarestream.com/31c9291ab41fac05471db4e73aa11717/iframe';
        $this->assertMatchesRegularExpression($pattern, $url);
    }

    public function testItDefinesACloudflareUrlRegexThatMatchesIframeVideodeliveryNetUrls(): void
    {
        $pattern = $this->extractCloudflareRegex();
        $url = 'https://iframe.videodelivery.net/31c9291ab41fac05471db4e73aa11717';
        $this->assertMatchesRegularExpression($pattern, $url);
    }

    public function testItDefinesACloudflareUrlRegexThatMatchesABare32HexCharUid(): void
    {
        $pattern = $this->extractCloudflareRegex();
        $uid = '31c9291ab41fac05471db4e73aa11717';
        $this->assertMatchesRegularExpression($pattern, $uid);
    }

    public function testItDefinesACloudflareUrlRegexThatDoesNotMatchAYoutubeUrl(): void
    {
        $pattern = $this->extractCloudflareRegex();
        $url = 'https://www.youtube.com/watch?v=dQw4w9WgXcQ';
        $this->assertDoesNotMatchRegularExpression($pattern, $url);
    }

    // -------------------------------------------------------------------------
    // Mixin structure assertions
    // -------------------------------------------------------------------------

    public function testItWrapsTheValidateUrlMethodOfTheVideoDataWidget(): void
    {
        $this->assertStringContainsString(
            '_validateURL',
            $this->mixinSource,
            'get-video-information-mixin.js must wrap _validateURL'
        );
    }

    public function testItWrapsTheOnRequestHandlerMethodOfTheVideoDataWidget(): void
    {
        $this->assertStringContainsString(
            '_onRequestHandler',
            $this->mixinSource,
            'get-video-information-mixin.js must wrap _onRequestHandler'
        );
    }

    public function testItWrapsTheCreateMethodOfTheProductVideoLoaderWidgetSoItDoesNotThrowForDataTypeCloudflare(): void
    {
        $this->assertStringContainsString(
            'productVideoLoader',
            $this->mixinSource,
            'get-video-information-mixin.js must extend/wrap mage.productVideoLoader'
        );
        $this->assertStringContainsString(
            '_create',
            $this->mixinSource,
            'get-video-information-mixin.js must override _create on productVideoLoader'
        );
        $this->assertStringContainsString(
            'cloudflare',
            $this->mixinSource,
            'get-video-information-mixin.js must handle the cloudflare case in _create'
        );
    }

    public function testItLeavesYoutubeVimeoFlowIntactWhenTheUrlIsNotACloudflareUrl(): void
    {
        // The mixin must call the original method for non-cloudflare URLs.
        // This is satisfied by calling _super() (jQuery widget pattern) or
        // by delegating to the original wrapped function via a closure variable.
        $callsSuperOrOriginal = (
            str_contains($this->mixinSource, '_super(') ||
            str_contains($this->mixinSource, '_super.apply') ||
            str_contains($this->mixinSource, 'originalFn') ||
            str_contains($this->mixinSource, 'original.')
        );
        $this->assertTrue(
            $callsSuperOrOriginal,
            'get-video-information-mixin.js must delegate to the original method ' .
            'for non-cloudflare URLs (via _super() or original function reference)'
        );
    }
}
