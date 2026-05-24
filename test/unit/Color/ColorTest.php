<?php

declare(strict_types=1);

namespace Horde\Image\Test\Unit\Color;

use Horde\Image\Color\Color;
use Horde\Image\Color\ColorModel;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(Color::class)]
final class ColorTest extends TestCase
{
    public function testRgbFactory(): void
    {
        $color = Color::rgb(0.5, 0.25, 0.75);

        self::assertSame(ColorModel::Rgb, $color->colorModel());
        self::assertSame(0.5, $color->red());
        self::assertSame(0.25, $color->green());
        self::assertSame(0.75, $color->blue());
        self::assertSame(0.0, $color->alpha());
    }

    public function testRgbaFactory(): void
    {
        $color = Color::rgba(1.0, 0.0, 0.5, 0.8);

        self::assertSame(ColorModel::Rgba, $color->colorModel());
        self::assertSame(1.0, $color->red());
        self::assertSame(0.0, $color->green());
        self::assertSame(0.5, $color->blue());
        self::assertSame(0.8, $color->alpha());
    }

    public function testCmykFactory(): void
    {
        $color = Color::cmyk(0.1, 0.2, 0.3, 0.4);

        self::assertSame(ColorModel::Cmyk, $color->colorModel());
        self::assertSame(0.1, $color->cyan());
        self::assertSame(0.2, $color->magenta());
        self::assertSame(0.3, $color->yellow());
        self::assertSame(0.4, $color->key());
    }

    public function testGrayFactory(): void
    {
        $color = Color::gray(0.65);

        self::assertSame(ColorModel::Gray, $color->colorModel());
        self::assertSame(0.65, $color->luminance());
    }

    public function testHexFactorySixDigit(): void
    {
        $color = Color::hex('#ff8000');

        self::assertSame(ColorModel::Rgb, $color->colorModel());
        self::assertSame(1.0, $color->red());
        self::assertEqualsWithDelta(0.502, $color->green(), 0.01);
        self::assertSame(0.0, $color->blue());
    }

    public function testHexFactoryThreeDigit(): void
    {
        $color = Color::hex('#f00');

        self::assertSame(ColorModel::Rgb, $color->colorModel());
        self::assertSame(1.0, $color->red());
        self::assertSame(0.0, $color->green());
        self::assertSame(0.0, $color->blue());
    }

    public function testHexFactoryWithoutHash(): void
    {
        $color = Color::hex('00ff00');

        self::assertSame(0.0, $color->red());
        self::assertSame(1.0, $color->green());
        self::assertSame(0.0, $color->blue());
    }

    public function testHexFactoryEightDigitWithAlpha(): void
    {
        $color = Color::hex('#ff000080');

        self::assertSame(ColorModel::Rgba, $color->colorModel());
        self::assertSame(1.0, $color->red());
        self::assertSame(0.0, $color->green());
        self::assertSame(0.0, $color->blue());
        self::assertEqualsWithDelta(0.502, $color->alpha(), 0.01);
    }

    public function testToHex(): void
    {
        $color = Color::rgb(1.0, 0.0, 0.5);

        self::assertSame('#ff0080', $color->toHex());
    }

    public function testToHexBlack(): void
    {
        $color = Color::rgb(0.0, 0.0, 0.0);

        self::assertSame('#000000', $color->toHex());
    }

    public function testToHexWhite(): void
    {
        $color = Color::rgb(1.0, 1.0, 1.0);

        self::assertSame('#ffffff', $color->toHex());
    }

    public function testWithAlpha(): void
    {
        $color = Color::rgb(1.0, 0.0, 0.0);
        $withAlpha = $color->withAlpha(0.5);

        self::assertSame(ColorModel::Rgba, $withAlpha->colorModel());
        self::assertSame(1.0, $withAlpha->red());
        self::assertSame(0.5, $withAlpha->alpha());
        // Original unchanged
        self::assertSame(ColorModel::Rgb, $color->colorModel());
    }
}
