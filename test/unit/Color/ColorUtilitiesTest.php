<?php

declare(strict_types=1);

namespace Horde\Image\Test\Unit\Color;

use Horde\Image\Color\Color;
use Horde\Image\Color\ColorModel;
use Horde\Image\Color\NamedColors;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use InvalidArgumentException;

#[CoversClass(Color::class)]
final class ColorUtilitiesTest extends TestCase
{
    public function testNamedRed(): void
    {
        $color = Color::named('red');

        self::assertSame(1.0, $color->red());
        self::assertSame(0.0, $color->green());
        self::assertSame(0.0, $color->blue());
    }

    public function testNamedCaseInsensitive(): void
    {
        $color = Color::named('DodgerBlue');

        self::assertEqualsWithDelta(30 / 255, $color->red(), 0.001);
        self::assertEqualsWithDelta(144 / 255, $color->green(), 0.001);
        self::assertSame(1.0, $color->blue());
    }

    public function testNamedTrimsWhitespace(): void
    {
        $color = Color::named(' navy ');

        self::assertSame(0.0, $color->red());
        self::assertSame(0.0, $color->green());
        self::assertEqualsWithDelta(128 / 255, $color->blue(), 0.001);
    }

    public function testNamedUnknownThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown color name: "nonexistent"');

        Color::named('nonexistent');
    }

    public function testBrightnessWhite(): void
    {
        $color = Color::rgb(1.0, 1.0, 1.0);

        self::assertEqualsWithDelta(1.0, $color->brightness(), 0.001);
    }

    public function testBrightnessBlack(): void
    {
        $color = Color::rgb(0.0, 0.0, 0.0);

        self::assertSame(0.0, $color->brightness());
    }

    public function testBrightnessFormula(): void
    {
        $color = Color::rgb(0.5, 0.5, 0.5);
        $expected = 0.5 * 0.299 + 0.5 * 0.587 + 0.5 * 0.114;

        self::assertEqualsWithDelta($expected, $color->brightness(), 0.0001);
    }

    public function testBrightnessRedHeavierThanBlue(): void
    {
        $red = Color::rgb(1.0, 0.0, 0.0);
        $blue = Color::rgb(0.0, 0.0, 1.0);

        self::assertGreaterThan($blue->brightness(), $red->brightness());
    }

    public function testToGray(): void
    {
        $color = Color::rgb(0.8, 0.4, 0.2);
        $gray = $color->toGray();

        self::assertSame(ColorModel::Gray, $gray->colorModel());
        self::assertEqualsWithDelta($color->brightness(), $gray->luminance(), 0.0001);
    }

    public function testLighten(): void
    {
        $color = Color::rgb(0.5, 0.3, 0.1);
        $lighter = $color->lighten(0.2);

        self::assertEqualsWithDelta(0.7, $lighter->red(), 0.0001);
        self::assertEqualsWithDelta(0.5, $lighter->green(), 0.0001);
        self::assertEqualsWithDelta(0.3, $lighter->blue(), 0.0001);
    }

    public function testLightenClamps(): void
    {
        $color = Color::rgb(0.9, 0.95, 1.0);
        $lighter = $color->lighten(0.2);

        self::assertSame(1.0, $lighter->red());
        self::assertSame(1.0, $lighter->green());
        self::assertSame(1.0, $lighter->blue());
    }

    public function testLightenPreservesModel(): void
    {
        $color = Color::rgba(0.5, 0.5, 0.5, 0.8);
        $lighter = $color->lighten(0.1);

        self::assertSame(ColorModel::Rgba, $lighter->colorModel());
        self::assertSame(0.8, $lighter->alpha());
    }

    public function testDarken(): void
    {
        $color = Color::rgb(0.5, 0.3, 0.1);
        $darker = $color->darken(0.1);

        self::assertEqualsWithDelta(0.4, $darker->red(), 0.0001);
        self::assertEqualsWithDelta(0.2, $darker->green(), 0.0001);
        self::assertEqualsWithDelta(0.0, $darker->blue(), 0.0001);
    }

    public function testDarkenClamps(): void
    {
        $color = Color::rgb(0.05, 0.0, 0.1);
        $darker = $color->darken(0.2);

        self::assertSame(0.0, $darker->red());
        self::assertSame(0.0, $darker->green());
        self::assertSame(0.0, $darker->blue());
    }

    public function testIntensifyRedDominant(): void
    {
        $color = Color::rgb(0.8, 0.4, 0.2);
        $intense = $color->intensify(0.1);

        self::assertEqualsWithDelta(0.9, $intense->red(), 0.0001);
        self::assertEqualsWithDelta(0.45, $intense->green(), 0.0001);
        self::assertEqualsWithDelta(0.225, $intense->blue(), 0.0001);
    }

    public function testIntensifyGreenDominant(): void
    {
        $color = Color::rgb(0.2, 0.6, 0.3);
        $intense = $color->intensify(0.2);

        self::assertEqualsWithDelta(0.8, $intense->green(), 0.0001);
        $expectedR = (0.2 / 0.6) * 0.8;
        $expectedB = (0.3 / 0.6) * 0.8;
        self::assertEqualsWithDelta($expectedR, $intense->red(), 0.0001);
        self::assertEqualsWithDelta($expectedB, $intense->blue(), 0.0001);
    }

    public function testIntensifyBlueDominant(): void
    {
        $color = Color::rgb(0.1, 0.2, 0.7);
        $intense = $color->intensify(0.1);

        self::assertEqualsWithDelta(0.8, $intense->blue(), 0.0001);
        $expectedR = (0.1 / 0.7) * 0.8;
        $expectedG = (0.2 / 0.7) * 0.8;
        self::assertEqualsWithDelta($expectedR, $intense->red(), 0.0001);
        self::assertEqualsWithDelta($expectedG, $intense->green(), 0.0001);
    }

    public function testIntensifyBlackReturnsBlack(): void
    {
        $color = Color::rgb(0.0, 0.0, 0.0);
        $intense = $color->intensify(0.5);

        self::assertSame(0.0, $intense->red());
        self::assertSame(0.0, $intense->green());
        self::assertSame(0.0, $intense->blue());
    }

    public function testIntensifyClamps(): void
    {
        $color = Color::rgb(0.9, 0.45, 0.3);
        $intense = $color->intensify(0.5);

        self::assertSame(1.0, $intense->red());
        self::assertLessThanOrEqual(1.0, $intense->green());
        self::assertLessThanOrEqual(1.0, $intense->blue());
    }

    public function testIntensifyPreservesModel(): void
    {
        $color = Color::rgba(0.6, 0.2, 0.1, 0.7);
        $intense = $color->intensify(0.1);

        self::assertSame(ColorModel::Rgba, $intense->colorModel());
        self::assertSame(0.7, $intense->alpha());
    }

    public function testImmutabilityLighten(): void
    {
        $original = Color::rgb(0.5, 0.5, 0.5);
        $lighter = $original->lighten(0.1);

        self::assertNotSame($original, $lighter);
        self::assertSame(0.5, $original->red());
    }

    public function testImmutabilityDarken(): void
    {
        $original = Color::rgb(0.5, 0.5, 0.5);
        $darker = $original->darken(0.1);

        self::assertNotSame($original, $darker);
        self::assertSame(0.5, $original->red());
    }

    public function testImmutabilityIntensify(): void
    {
        $original = Color::rgb(0.5, 0.3, 0.1);
        $intense = $original->intensify(0.1);

        self::assertNotSame($original, $intense);
        self::assertSame(0.5, $original->red());
    }

    public function testIntensifyEqualChannels(): void
    {
        $color = Color::rgb(0.5, 0.5, 0.5);
        $intense = $color->intensify(0.1);

        self::assertEqualsWithDelta(0.6, $intense->red(), 0.0001);
        self::assertEqualsWithDelta(0.6, $intense->green(), 0.0001);
        self::assertEqualsWithDelta(0.6, $intense->blue(), 0.0001);
    }
}
