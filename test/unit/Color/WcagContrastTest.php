<?php

declare(strict_types=1);

namespace Horde\Image\Test\Unit\Color;

use Horde_Image;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @author Torben Dannhauer <torben@dannhauer.de>
 */
#[CoversClass(Horde_Image::class)]
final class WcagContrastTest extends TestCase
{
    public function testRelativeLuminanceBlack(): void
    {
        self::assertEqualsWithDelta(0.0, Horde_Image::relativeLuminance('#000'), 0.0001);
    }

    public function testRelativeLuminanceWhite(): void
    {
        self::assertEqualsWithDelta(1.0, Horde_Image::relativeLuminance('#fff'), 0.0001);
    }

    public function testRelativeLuminanceMidGray(): void
    {
        // #808080 → linearized sRGB ≈ 0.2159 per channel → WCAG L ≈ 0.2159
        self::assertEqualsWithDelta(0.2159, Horde_Image::relativeLuminance('#808080'), 0.0001);
    }

    public function testRelativeLuminanceShorthandHex(): void
    {
        self::assertEqualsWithDelta(
            Horde_Image::relativeLuminance('#ffffff'),
            Horde_Image::relativeLuminance('#fff'),
            0.0001
        );
    }

    /**
     * @dataProvider contrastColorProvider
     */
    public function testContrastColor(string $background, string $expected): void
    {
        self::assertSame($expected, Horde_Image::contrastColor($background));
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function contrastColorProvider(): array
    {
        return [
            'bright magenta prefers black' => ['#fb00ec', '#000'],
            'shorthand magenta prefers black' => ['#f0e', '#000'],
            'blue prefers white' => ['#0000ff', '#fff'],
            'dark green prefers white' => ['#008000', '#fff'],
            'white background prefers black' => ['#ffffff', '#000'],
            'black background prefers white' => ['#000000', '#fff'],
            'mid gray prefers black' => ['#808080', '#000'],
        ];
    }

    public function testContrastColorPreservesCandidateFormat(): void
    {
        self::assertSame(
            '#000000',
            Horde_Image::contrastColor('#fb00ec', '#ffffff', '#000000')
        );
    }

    public function testContrastColorCustomCandidates(): void
    {
        self::assertSame(
            '#111111',
            Horde_Image::contrastColor('#ffffff', '#eeeeee', '#111111')
        );
        self::assertSame(
            '#eeeeee',
            Horde_Image::contrastColor('#000000', '#eeeeee', '#111111')
        );
    }

    public function testContrastColorDiffersFromBrightnessThreshold(): void
    {
        $background = '#fb00ec';
        $legacy = Horde_Image::brightness($background) < 128 ? '#fff' : '#000';

        self::assertSame('#fff', $legacy);
        self::assertSame('#000', Horde_Image::contrastColor($background));
    }
}
