<?php

declare(strict_types=1);

namespace Horde\Image\Test\Unit\Drawing;

use Horde\Image\Drawing\BlendMode;
use Horde\Image\Drawing\GraphicsState;
use Horde\Image\Drawing\LineCap;
use Horde\Image\Drawing\LineDashPattern;
use Horde\Image\Drawing\ShapeStyle;
use Horde\Image\Drawing\TextStyle;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(LineCap::class)]
#[CoversClass(BlendMode::class)]
#[CoversClass(ShapeStyle::class)]
#[CoversClass(LineDashPattern::class)]
#[CoversClass(GraphicsState::class)]
#[CoversClass(TextStyle::class)]
final class DrawingTypesTest extends TestCase
{
    public function testLineCapValues(): void
    {
        self::assertSame(0, LineCap::Butt->value);
        self::assertSame(1, LineCap::Round->value);
        self::assertSame(2, LineCap::Square->value);
    }

    public function testBlendModeValues(): void
    {
        self::assertSame('Normal', BlendMode::Normal->value);
        self::assertSame('Multiply', BlendMode::Multiply->value);
        self::assertSame('Screen', BlendMode::Screen->value);
        self::assertSame('Overlay', BlendMode::Overlay->value);
        self::assertSame('Darken', BlendMode::Darken->value);
        self::assertSame('Lighten', BlendMode::Lighten->value);
        self::assertSame('ColorDodge', BlendMode::ColorDodge->value);
        self::assertSame('ColorBurn', BlendMode::ColorBurn->value);
        self::assertSame('HardLight', BlendMode::HardLight->value);
        self::assertSame('SoftLight', BlendMode::SoftLight->value);
        self::assertSame('Difference', BlendMode::Difference->value);
        self::assertSame('Exclusion', BlendMode::Exclusion->value);
    }

    public function testShapeStyleValues(): void
    {
        self::assertSame('S', ShapeStyle::Stroke->value);
        self::assertSame('F', ShapeStyle::Fill->value);
        self::assertSame('SF', ShapeStyle::StrokeAndFill->value);
    }

    public function testLineDashPatternSolid(): void
    {
        $pattern = LineDashPattern::solid();

        self::assertSame([], $pattern->dashArray);
        self::assertSame(0.0, $pattern->dashPhase);
    }

    public function testLineDashPatternCustom(): void
    {
        $pattern = new LineDashPattern([5.0, 3.0, 1.0], 2.0);

        self::assertSame([5.0, 3.0, 1.0], $pattern->dashArray);
        self::assertSame(2.0, $pattern->dashPhase);
    }

    public function testGraphicsStateAlphaFactory(): void
    {
        $state = GraphicsState::alpha(0.5);

        self::assertSame(0.5, $state->fillAlpha);
        self::assertSame(0.5, $state->strokeAlpha);
        self::assertNull($state->blendMode);
    }

    public function testGraphicsStateAlphaWithSeparateStroke(): void
    {
        $state = GraphicsState::alpha(0.8, 0.3);

        self::assertSame(0.8, $state->fillAlpha);
        self::assertSame(0.3, $state->strokeAlpha);
    }

    public function testGraphicsStateBlendModeFactory(): void
    {
        $state = GraphicsState::blendMode(BlendMode::Multiply);

        self::assertNull($state->fillAlpha);
        self::assertNull($state->strokeAlpha);
        self::assertSame(BlendMode::Multiply, $state->blendMode);
    }

    public function testGraphicsStateFullConstructor(): void
    {
        $state = new GraphicsState(
            fillAlpha: 0.9,
            strokeAlpha: 0.7,
            blendMode: BlendMode::Screen,
        );

        self::assertSame(0.9, $state->fillAlpha);
        self::assertSame(0.7, $state->strokeAlpha);
        self::assertSame(BlendMode::Screen, $state->blendMode);
    }

    public function testTextStyleDefaults(): void
    {
        $style = new TextStyle();

        self::assertSame(12.0, $style->size);
        self::assertNull($style->fontFamily);
        self::assertNull($style->fontFile);
        self::assertSame(0.0, $style->angle);
    }

    public function testTextStyleCustom(): void
    {
        $style = new TextStyle(
            size: 24.0,
            fontFamily: 'Helvetica',
            angle: 45.0,
        );

        self::assertSame(24.0, $style->size);
        self::assertSame('Helvetica', $style->fontFamily);
        self::assertNull($style->fontFile);
        self::assertSame(45.0, $style->angle);
    }
}
