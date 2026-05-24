<?php

declare(strict_types=1);

namespace Horde\Image\Test\Unit\Filter;

use Horde\Image\Color\Color;
use Horde\Image\Filter\Blur;
use Horde\Image\Filter\Brightness;
use Horde\Image\Filter\Colorize;
use Horde\Image\Filter\Contrast;
use Horde\Image\Filter\Filter;
use Horde\Image\Filter\Gamma;
use Horde\Image\Filter\Grayscale;
use Horde\Image\Filter\Modulate;
use Horde\Image\Filter\Negate;
use Horde\Image\Filter\Pixelate;
use Horde\Image\Filter\Sepia;
use Horde\Image\Filter\Sharpen;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Grayscale::class)]
#[CoversClass(Sepia::class)]
#[CoversClass(Blur::class)]
#[CoversClass(Sharpen::class)]
#[CoversClass(Brightness::class)]
#[CoversClass(Contrast::class)]
#[CoversClass(Gamma::class)]
#[CoversClass(Colorize::class)]
#[CoversClass(Negate::class)]
#[CoversClass(Pixelate::class)]
#[CoversClass(Modulate::class)]
final class FilterConfigTest extends TestCase
{
    public function testAllImplementFilterInterface(): void
    {
        $filters = [
            new Grayscale(),
            new Sepia(),
            new Blur(),
            new Sharpen(),
            new Brightness(),
            new Contrast(),
            new Gamma(),
            new Colorize(color: Color::rgb(1.0, 0.0, 0.0)),
            new Negate(),
            new Pixelate(),
            new Modulate(),
        ];

        foreach ($filters as $filter) {
            self::assertInstanceOf(Filter::class, $filter);
        }
    }

    public function testSepiaDefaults(): void
    {
        $filter = new Sepia();
        self::assertSame(80.0, $filter->threshold);
    }

    public function testSepiaCustom(): void
    {
        $filter = new Sepia(threshold: 120.0);
        self::assertSame(120.0, $filter->threshold);
    }

    public function testBlurDefaults(): void
    {
        $filter = new Blur();
        self::assertSame(0.0, $filter->radius);
        self::assertSame(1.0, $filter->sigma);
    }

    public function testBlurCustom(): void
    {
        $filter = new Blur(radius: 5.0, sigma: 3.0);
        self::assertSame(5.0, $filter->radius);
        self::assertSame(3.0, $filter->sigma);
    }

    public function testSharpenDefaults(): void
    {
        $filter = new Sharpen();
        self::assertSame(0.0, $filter->radius);
        self::assertSame(1.0, $filter->sigma);
        self::assertSame(1.0, $filter->amount);
        self::assertSame(0.05, $filter->threshold);
    }

    public function testBrightnessDefaults(): void
    {
        $filter = new Brightness();
        self::assertSame(0.0, $filter->level);
    }

    public function testContrastDefaults(): void
    {
        $filter = new Contrast();
        self::assertSame(0.0, $filter->level);
    }

    public function testGammaDefaults(): void
    {
        $filter = new Gamma();
        self::assertSame(1.0, $filter->gamma);
    }

    public function testColorize(): void
    {
        $color = Color::rgb(0.0, 1.0, 0.0);
        $filter = new Colorize(color: $color, opacity: 0.7);

        self::assertSame($color, $filter->color);
        self::assertSame(0.7, $filter->opacity);
    }

    public function testColorizeDefaultOpacity(): void
    {
        $filter = new Colorize(color: Color::rgb(1.0, 0.0, 0.0));
        self::assertSame(1.0, $filter->opacity);
    }

    public function testPixelateDefaults(): void
    {
        $filter = new Pixelate();
        self::assertSame(10, $filter->size);
    }

    public function testPixelateCustom(): void
    {
        $filter = new Pixelate(size: 20);
        self::assertSame(20, $filter->size);
    }

    public function testModulateDefaults(): void
    {
        $filter = new Modulate();
        self::assertSame(100.0, $filter->brightness);
        self::assertSame(100.0, $filter->saturation);
        self::assertSame(100.0, $filter->hue);
    }

    public function testModulateCustom(): void
    {
        $filter = new Modulate(brightness: 120.0, saturation: 50.0, hue: 150.0);
        self::assertSame(120.0, $filter->brightness);
        self::assertSame(50.0, $filter->saturation);
        self::assertSame(150.0, $filter->hue);
    }
}
