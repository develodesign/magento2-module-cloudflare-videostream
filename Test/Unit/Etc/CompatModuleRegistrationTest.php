<?php
/**
 * Copyright © Develo Design. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Develo\CloudflareVideo\Test\Unit\Etc;

use PHPUnit\Framework\TestCase;

/**
 * Validates that etc/frontend/di.xml registers Develo_CloudflareVideo
 * with Hyvä's CompatModuleRegistry so gallery.phtml override is discovered.
 */
class CompatModuleRegistrationTest extends TestCase
{
    private const DI_XML_PATH = __DIR__ . '/../../../etc/frontend/di.xml';

    private \SimpleXMLElement $xml;

    protected function setUp(): void
    {
        $this->assertFileExists(
            self::DI_XML_PATH,
            'etc/frontend/di.xml must exist'
        );

        $xml = simplexml_load_file(self::DI_XML_PATH);
        $this->assertNotFalse($xml, 'etc/frontend/di.xml must be valid XML');

        $this->xml = $xml;
    }

    public function testItRegistersDeveloCloudflareVideoAsACompatModuleForMagentoCatalogInEtcFrontendDiXml(): void
    {
        $this->assertFileExists(
            self::DI_XML_PATH,
            'etc/frontend/di.xml must exist'
        );

        // Assert CompatModuleRegistry type is configured
        $registryType = $this->xml->xpath(
            '//type[@name="Hyva\CompatModuleFallback\Model\CompatModuleRegistry"]'
        );
        $this->assertNotEmpty(
            $registryType,
            'etc/frontend/di.xml must configure Hyva\CompatModuleFallback\Model\CompatModuleRegistry'
        );

        // Assert original_module is Magento_Catalog
        $originalModule = $this->xml->xpath(
            '//item[@name="original_module"]'
        );
        $this->assertNotEmpty($originalModule, 'A original_module item must be declared');

        $originalModuleValue = (string) $originalModule[0];
        $this->assertSame(
            'Magento_Catalog',
            $originalModuleValue,
            'original_module must be Magento_Catalog'
        );

        // Assert compat_module is Develo_CloudflareVideo
        $compatModule = $this->xml->xpath(
            '//item[@name="compat_module"]'
        );
        $this->assertNotEmpty($compatModule, 'A compat_module item must be declared');

        $compatModuleValue = (string) $compatModule[0];
        $this->assertSame(
            'Develo_CloudflareVideo',
            $compatModuleValue,
            'compat_module must be Develo_CloudflareVideo'
        );
    }
}
