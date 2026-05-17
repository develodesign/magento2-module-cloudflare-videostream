<?php
/**
 * Copyright © Develo Design. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Develo\CloudflareVideo\Test\Unit\Model;

use Develo\CloudflareVideo\Model\Config;
use Develo\CloudflareVideo\Model\EmbedUrlBuilder;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class EmbedUrlBuilderTest extends TestCase
{
    /**
     * @var Config&MockObject
     */
    private Config $config;

    /**
     * @var EmbedUrlBuilder
     */
    private EmbedUrlBuilder $builder;

    protected function setUp(): void
    {
        $this->config = $this->createMock(Config::class);
        $this->builder = new EmbedUrlBuilder($this->config);
    }

    public function testItBuildsEmbedUrlUsingConfiguredCustomerCode(): void
    {
        $this->config
            ->method('getCustomerCode')
            ->willReturn('abc123');

        $result = $this->builder->build('deadbeef0123456789abcdef01234567');

        $this->assertSame(
            'https://customer-abc123.cloudflarestream.com/deadbeef0123456789abcdef01234567/iframe',
            $result
        );
    }

    public function testItFallsBackToIframeVideodeliveryNetWhenCustomerCodeIsEmpty(): void
    {
        $this->config
            ->method('getCustomerCode')
            ->willReturn('');

        $result = $this->builder->build('deadbeef0123456789abcdef01234567');

        $this->assertSame(
            'https://iframe.videodelivery.net/deadbeef0123456789abcdef01234567',
            $result
        );
    }

    public function testItAppendsUrlEncodedPosterQueryParameterWhenPosterUrlIsGiven(): void
    {
        $this->config
            ->method('getCustomerCode')
            ->willReturn('abc123');

        $result = $this->builder->build(
            'deadbeef0123456789abcdef01234567',
            'https://example.com/poster.jpg?size=large&color=blue'
        );

        $this->assertSame(
            'https://customer-abc123.cloudflarestream.com/deadbeef0123456789abcdef01234567/iframe'
            . '?poster=' . rawurlencode('https://example.com/poster.jpg?size=large&color=blue'),
            $result
        );
    }

    public function testItOmitsPosterQueryParameterWhenPosterUrlIsNull(): void
    {
        $this->config
            ->method('getCustomerCode')
            ->willReturn('abc123');

        $result = $this->builder->build('deadbeef0123456789abcdef01234567', null);

        $this->assertStringNotContainsString('poster', $result);
    }

    public function testItOmitsPosterQueryParameterWhenPosterUrlIsEmpty(): void
    {
        $this->config
            ->method('getCustomerCode')
            ->willReturn('abc123');

        $result = $this->builder->build('deadbeef0123456789abcdef01234567', '');

        $this->assertStringNotContainsString('poster', $result);
    }

    public function testItThrowsInvalidArgumentExceptionForAnEmptyUid(): void
    {
        $this->config
            ->method('getCustomerCode')
            ->willReturn('abc123');

        $this->expectException(\InvalidArgumentException::class);

        $this->builder->build('');
    }
}
