<?php

declare(strict_types=1);

namespace Horde\Image\Test\Unit\Color;

use Horde\Image\Color\Color;
use Horde\Image\Color\NamedColors;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(NamedColors::class)]
#[CoversClass(Color::class)]
final class NamedColorsTest extends TestCase
{
    public function testLookupReturnsColor(): void
    {
        $color = NamedColors::lookup('red');

        self::assertNotNull($color);
        self::assertSame(1.0, $color->red());
        self::assertSame(0.0, $color->green());
        self::assertSame(0.0, $color->blue());
    }

    public function testLookupCaseInsensitive(): void
    {
        $color = NamedColors::lookup('AliceBlue');

        self::assertNotNull($color);
        self::assertEqualsWithDelta(240 / 255, $color->red(), 0.001);
        self::assertEqualsWithDelta(248 / 255, $color->green(), 0.001);
        self::assertSame(1.0, $color->blue());
    }

    public function testLookupUnknownReturnsNull(): void
    {
        self::assertNull(NamedColors::lookup('fakecolor'));
    }

    public function testHasTrue(): void
    {
        self::assertTrue(NamedColors::has('blue'));
        self::assertTrue(NamedColors::has('CornflowerBlue'));
    }

    public function testHasFalse(): void
    {
        self::assertFalse(NamedColors::has('octarine'));
    }

    public function testAllReturnsFullTable(): void
    {
        $all = NamedColors::all();

        self::assertGreaterThan(140, count($all));
        self::assertArrayHasKey('red', $all);
        self::assertArrayHasKey('white', $all);
        self::assertArrayHasKey('rebeccapurple', $all);
    }

    public function testAllValuesAreRgbArrays(): void
    {
        foreach (NamedColors::all() as $name => $rgb) {
            self::assertCount(3, $rgb, "Color '$name' should have 3 components");
            self::assertGreaterThanOrEqual(0, $rgb[0]);
            self::assertLessThanOrEqual(255, $rgb[0]);
            self::assertGreaterThanOrEqual(0, $rgb[1]);
            self::assertLessThanOrEqual(255, $rgb[1]);
            self::assertGreaterThanOrEqual(0, $rgb[2]);
            self::assertLessThanOrEqual(255, $rgb[2]);
        }
    }

    #[DataProvider('commonColorsProvider')]
    public function testCommonColors(string $name, int $r, int $g, int $b): void
    {
        $color = NamedColors::lookup($name);

        self::assertNotNull($color);
        self::assertEqualsWithDelta($r / 255, $color->red(), 0.001);
        self::assertEqualsWithDelta($g / 255, $color->green(), 0.001);
        self::assertEqualsWithDelta($b / 255, $color->blue(), 0.001);
    }

    /**
     * @return array<string, array{string, int, int, int}>
     */
    public static function commonColorsProvider(): array
    {
        return [
            'black' => ['black', 0, 0, 0],
            'white' => ['white', 255, 255, 255],
            'red' => ['red', 255, 0, 0],
            'green' => ['green', 0, 128, 0],
            'blue' => ['blue', 0, 0, 255],
            'yellow' => ['yellow', 255, 255, 0],
            'cyan' => ['cyan', 0, 255, 255],
            'magenta' => ['magenta', 255, 0, 255],
            'gray' => ['gray', 128, 128, 128],
            'navy' => ['navy', 0, 0, 128],
            'teal' => ['teal', 0, 128, 128],
            'silver' => ['silver', 192, 192, 192],
        ];
    }
}
