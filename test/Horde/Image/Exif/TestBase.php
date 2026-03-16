<?php

/**
 * Copyright 2011-2026 Horde LLC (http://www.horde.org/)
 *
 * @author     Michael J Rubinsky <mrubinsk@horde.org>
 * @category   Horde
 * @package    Image
 * @subpackage UnitTests
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 */

namespace Horde\Image\Exif;

use PHPUnit\Framework\TestCase;
use Horde_Image_Exif;

/**
 * @coversNothing
 */
class TestBase extends TestCase
{
    /**
     * @var Horde_Image_Exif_Base
     */
    protected static $_exif = null;

    /**
     * Cache of retrieved EXIF data
     */
    protected static $_data;

    /**
     * Load test configuration from environment or file, with auto-detection fallback
     *
     * @param string $env Environment variable name
     * @param string $path Path to conf.php file
     * @param array $default Default values
     * @return mixed Configuration array or null
     */
    protected static function getConfig(string $env, ?string $path = null, array $default = []): mixed
    {
        // Check environment variable
        $config = getenv($env);
        if ($config) {
            $json = json_decode($config, true);
            if ($json) {
                return array_replace_recursive($default, $json);
            }
        }

        // Try loading from conf.php file
        if (!$path) {
            $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
            $caller = $backtrace[1] ?? $backtrace[0];
            $path = dirname($caller['file']);
        }

        $configFile = $path . '/conf.php';
        if (file_exists($configFile)) {
            $conf = null;
            require $configFile;
            return $conf;
        }

        // Auto-detect available image extensions if no config found
        return self::autoDetectConfig($default);
    }

    /**
     * Auto-detect available image processing extensions and tools
     *
     * @param array $default Default configuration values
     * @return array Configuration with auto-detected capabilities
     */
    protected static function autoDetectConfig(array $default = []): array
    {
        $config = $default;

        // Detect GD extension
        if (extension_loaded('gd')) {
            $config['image']['gd'] = true;
        }

        // Detect Imagick extension
        if (extension_loaded('imagick')) {
            $config['image']['imagick'] = true;
        }

        // Detect EXIF extension
        if (extension_loaded('exif')) {
            $config['image']['exif'] = true;
        }

        // Try to find exiftool binary
        $exiftoolPaths = [
            '/usr/bin/exiftool',
            '/usr/local/bin/exiftool',
            '/opt/homebrew/bin/exiftool',
        ];

        foreach ($exiftoolPaths as $exiftoolPath) {
            if (file_exists($exiftoolPath) && is_executable($exiftoolPath)) {
                $config['image']['exiftool'] = $exiftoolPath;
                break;
            }
        }

        // Also check PATH
        if (empty($config['image']['exiftool'])) {
            $which = trim(shell_exec('which exiftool 2>/dev/null') ?: '');
            if ($which && file_exists($which) && is_executable($which)) {
                $config['image']['exiftool'] = $which;
            }
        }

        return $config;
    }

    public function setUp(): void
    {
        if (self::$_exif === null) {
            $this->markTestSkipped('No exif driver');
        }
    }

    public function testTitleFields()
    {
        $fields = Horde_Image_Exif::getTitleFields();
        $this->assertTrue(array_key_exists('ObjectName', $fields));
        $this->assertTrue(array_key_exists('Title', $fields));
    }

    public function testDescriptionFields()
    {
        $descFields = Horde_Image_Exif::getDescriptionFields();
        $this->assertTrue(array_key_exists('ImageDescription', $descFields));
        $this->assertTrue(array_key_exists('Description', $descFields));
        $this->assertTrue(array_key_exists('Caption-Abstract', $descFields));
    }

    /**
     * Tests ability to extract EXIF data without errors. Does not test data
     * for validity.
     */
    public function testExtract()
    {
        $fixture = __DIR__ . '/../Fixtures/img_exif.jpg';
        setlocale(LC_ALL, 'de_DE');
        self::$_data = self::$_exif->getData($fixture);
        $this->assertIsArray(self::$_data);
    }

    /**
     * @depends testExtract
     */
    public function testKeywordIsString()
    {
        $this->_testKeywordIsString();
    }

    /**
     * @depends testExtract
     */
    public function testKeywords()
    {
        $this->_testKeywords();
    }

    /**
     * @depends testExtract
     */
    public function testGPS()
    {
        $lat = self::$_data['GPSLatitude'];
        $lon = self::$_data['GPSLongitude'];
        $this->assertEquals(44.3535, $lat);
        $this->assertEquals(68.223, $lon);
    }

    protected function _testKeywords()
    {
        $this->markTestSkipped('Keyword field not supported by driver');
    }

    protected function _testKeywordIsString()
    {
        $this->markTestSkipped('Keyword field not supported by driver');
    }

}
