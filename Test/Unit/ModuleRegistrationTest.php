<?php
/**
 * Copyright © Develo Design. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Develo\CloudflareVideo\Test\Unit;

use Magento\Framework\Component\ComponentRegistrar;
use PHPUnit\Framework\TestCase;

class ModuleRegistrationTest extends TestCase
{
    private const MODULE_NAME = 'Develo_CloudflareVideo';

    /**
     * @var string
     */
    private string $modulePath;

    protected function setUp(): void
    {
        $this->modulePath = realpath(__DIR__ . '/../..');
    }

    public function testItRegistersTheModuleViaComponentRegistrarWithNameDeveloCloudflareVideo(): void
    {
        require_once $this->modulePath . '/registration.php';

        $registrar = new ComponentRegistrar();
        $paths = $registrar->getPaths(ComponentRegistrar::MODULE);

        $this->assertArrayHasKey(
            self::MODULE_NAME,
            $paths,
            'Module Develo_CloudflareVideo must be registered via ComponentRegistrar'
        );
    }

    public function testItDeclaresModuleSetupVersionInEtcModuleXml(): void
    {
        $moduleXmlPath = $this->modulePath . '/etc/module.xml';
        $this->assertFileExists($moduleXmlPath, 'etc/module.xml must exist');

        $xml = simplexml_load_file($moduleXmlPath);
        $this->assertNotFalse($xml, 'etc/module.xml must be valid XML');

        $modules = $xml->xpath('//module[@name="' . self::MODULE_NAME . '"]');
        $this->assertNotEmpty($modules, 'module element with name Develo_CloudflareVideo must exist in etc/module.xml');

        $module = $modules[0];
        $this->assertNotEmpty(
            (string) $module['setup_version'],
            'module element must declare a setup_version attribute'
        );
    }

    public function testItSequencesAfterHyvaThemeSoHyvaTemplateOverridesWin(): void
    {
        $moduleXmlPath = $this->modulePath . '/etc/module.xml';
        $xml = simplexml_load_file($moduleXmlPath);

        $hyvaThemeSequence = $xml->xpath('//sequence/module[@name="Hyva_Theme"]');
        $this->assertNotEmpty(
            $hyvaThemeSequence,
            'etc/module.xml must sequence after Hyva_Theme'
        );
    }

    public function testItSequencesAfterHyvaCompatModuleFallbackForGalleryOverrideDiscovery(): void
    {
        $moduleXmlPath = $this->modulePath . '/etc/module.xml';
        $xml = simplexml_load_file($moduleXmlPath);

        $compatFallbackSequence = $xml->xpath('//sequence/module[@name="Hyva_CompatModuleFallback"]');
        $this->assertNotEmpty(
            $compatFallbackSequence,
            'etc/module.xml must sequence after Hyva_CompatModuleFallback'
        );
    }

    public function testItSequencesAfterMagentoCatalogAndMagentoProductVideo(): void
    {
        $moduleXmlPath = $this->modulePath . '/etc/module.xml';
        $xml = simplexml_load_file($moduleXmlPath);

        $catalogSequence = $xml->xpath('//sequence/module[@name="Magento_Catalog"]');
        $this->assertNotEmpty(
            $catalogSequence,
            'etc/module.xml must sequence after Magento_Catalog'
        );

        $productVideoSequence = $xml->xpath('//sequence/module[@name="Magento_ProductVideo"]');
        $this->assertNotEmpty(
            $productVideoSequence,
            'etc/module.xml must sequence after Magento_ProductVideo'
        );
    }

    public function testItShipsAComposerJsonWithAMagento2ModuleType(): void
    {
        $composerJsonPath = $this->modulePath . '/composer.json';
        $this->assertFileExists($composerJsonPath, 'composer.json must exist');

        $composer = json_decode(file_get_contents($composerJsonPath), true);
        $this->assertNotNull($composer, 'composer.json must be valid JSON');
        $this->assertSame(
            'magento2-module',
            $composer['type'],
            'composer.json type must be magento2-module'
        );
    }
}
