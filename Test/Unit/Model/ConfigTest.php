<?php
/**
 * Copyright © Develo Design. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Develo\CloudflareVideo\Test\Unit\Model;

use Develo\CloudflareVideo\Model\Config;
use Magento\Framework\App\Config\ScopeConfigInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ConfigTest extends TestCase
{
    private const CONFIG_PATH = 'develo_cloudflare_video/general/customer_code';

    /**
     * @var ScopeConfigInterface&MockObject
     */
    private ScopeConfigInterface $scopeConfig;

    /**
     * @var Config
     */
    private Config $config;

    protected function setUp(): void
    {
        $this->scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $this->config = new Config($this->scopeConfig);
    }

    public function testItReturnsCustomerCodeFromScopeConfigWhenSet(): void
    {
        $this->scopeConfig
            ->method('getValue')
            ->with(self::CONFIG_PATH)
            ->willReturn('abc123');

        $result = $this->config->getCustomerCode();

        $this->assertSame('abc123', $result);
    }

    public function testItReturnsEmptyStringWhenCustomerCodeIsNotConfigured(): void
    {
        $this->scopeConfig
            ->method('getValue')
            ->with(self::CONFIG_PATH)
            ->willReturn(null);

        $result = $this->config->getCustomerCode();

        $this->assertSame('', $result);
    }

    public function testItTrimsWhitespaceFromTheConfiguredCustomerCode(): void
    {
        $this->scopeConfig
            ->method('getValue')
            ->with(self::CONFIG_PATH)
            ->willReturn('  abc123  ');

        $result = $this->config->getCustomerCode();

        $this->assertSame('abc123', $result);
    }

    public function testItReadsFromConfigPathDeveloCloudflareVideoGeneralCustomerCode(): void
    {
        $this->scopeConfig
            ->expects($this->once())
            ->method('getValue')
            ->with('develo_cloudflare_video/general/customer_code')
            ->willReturn('test-code');

        $this->config->getCustomerCode();
    }
}
