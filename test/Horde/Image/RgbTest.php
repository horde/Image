<?php

/**
 * Copyright 2026 Horde LLC (http://www.horde.org/)
 *
 * @author     Ralf Lang <ralf.lang@ralf-lang.de>
 * @category   Horde
 * @package    Image
 * @subpackage UnitTests
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 */

namespace Horde\Image;

use PHPUnit\Framework\TestCase;
use Horde_Image_Rgb;

/**
 * Test Horde_Image_Rgb color name mappings
 *
 * @coversDefaultClass Horde_Image_Rgb
 */
class RgbTest extends TestCase
{
    /**
     * Test that $colors array exists and contains expected HTML color names
     */
    public function testColorsArrayExists(): void
    {
        $this->assertIsArray(Horde_Image_Rgb::$colors);
        $this->assertNotEmpty(Horde_Image_Rgb::$colors);
    }

    /**
     * Test basic HTML color names are defined
     */
    public function testBasicColors(): void
    {
        $basicColors = ['red', 'green', 'blue', 'white', 'black'];

        foreach ($basicColors as $color) {
            $this->assertArrayHasKey(
                $color,
                Horde_Image_Rgb::$colors,
                "Color '$color' should be defined"
            );
        }
    }

    /**
     * Test that RGB values are in correct format [R, G, B]
     */
    public function testRgbValueFormat(): void
    {
        foreach (Horde_Image_Rgb::$colors as $name => $rgb) {
            $this->assertIsArray($rgb, "Color '$name' should have array value");
            $this->assertCount(3, $rgb, "Color '$name' should have exactly 3 values");

            foreach ($rgb as $index => $value) {
                $this->assertIsInt($value, "Color '$name' component $index should be integer");
                $this->assertGreaterThanOrEqual(0, $value, "Color '$name' component $index should be >= 0");
                $this->assertLessThanOrEqual(255, $value, "Color '$name' component $index should be <= 255");
            }
        }
    }

    /**
     * Test specific known RGB values
     */
    public function testKnownRgbValues(): void
    {
        $this->assertEquals([255, 0, 0], Horde_Image_Rgb::$colors['red']);
        $this->assertEquals([0, 255, 0], Horde_Image_Rgb::$colors['lime']);
        $this->assertEquals([0, 0, 255], Horde_Image_Rgb::$colors['blue']);
        $this->assertEquals([255, 255, 255], Horde_Image_Rgb::$colors['white']);
        $this->assertEquals([0, 0, 0], Horde_Image_Rgb::$colors['black']);
        $this->assertEquals([128, 128, 128], Horde_Image_Rgb::$colors['gray']);
    }

    /**
     * Test that all standard HTML color names are present
     */
    public function testStandardHtmlColors(): void
    {
        $standardColors = [
            'red', 'green', 'blue', 'yellow', 'cyan', 'magenta',
            'white', 'black', 'gray', 'orange', 'purple', 'pink',
        ];

        foreach ($standardColors as $color) {
            $this->assertArrayHasKey($color, Horde_Image_Rgb::$colors);
        }
    }

    /**
     * Test that color array has reasonable size (HTML standard defines 140+ colors)
     */
    public function testColorArraySize(): void
    {
        $this->assertGreaterThan(100, count(Horde_Image_Rgb::$colors));
    }
}
