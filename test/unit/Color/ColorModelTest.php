<?php

declare(strict_types=1);

namespace Horde\Image\Test\Unit\Color;

use Horde\Image\Color\ColorModel;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ColorModel::class)]
final class ColorModelTest extends TestCase
{
    public function testCases(): void
    {
        self::assertSame('rgb', ColorModel::Rgb->value);
        self::assertSame('rgba', ColorModel::Rgba->value);
        self::assertSame('cmyk', ColorModel::Cmyk->value);
        self::assertSame('gray', ColorModel::Gray->value);
    }

    public function testFromString(): void
    {
        self::assertSame(ColorModel::Rgb, ColorModel::from('rgb'));
        self::assertSame(ColorModel::Cmyk, ColorModel::from('cmyk'));
    }
}
