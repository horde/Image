<?php

declare(strict_types=1);

namespace Horde\Image\Drawing;

use Horde\Image\Color\Color;
use Horde\Image\Geometry\Point;

final class Brush
{
    private function __construct() {}

    /**
     * Draw a shaped marker at a point using path operations on any DrawingContext.
     */
    public static function draw(
        DrawingContext $ctx,
        Point $position,
        Color $color,
        BrushShape $shape = BrushShape::Square,
        float $size = 4.0,
    ): void {
        $x = $position->x;
        $y = $position->y;
        $half = $size / 2.0;

        $ctx->save();
        $ctx->setFillColor($color);
        $ctx->setStrokeColor($color);
        $ctx->setLineWidth(1.0);

        match ($shape) {
            BrushShape::Square => self::drawSquare($ctx, $x, $y, $half),
            BrushShape::Circle => self::drawCircle($ctx, $x, $y, $half),
            BrushShape::Diamond => self::drawDiamond($ctx, $x, $y, $half),
            BrushShape::Triangle => self::drawTriangle($ctx, $x, $y, $half),
        };

        $ctx->restore();
    }

    private static function drawSquare(DrawingContext $ctx, float $x, float $y, float $half): void
    {
        $ctx->rect($x - $half, $y - $half, $half * 2, $half * 2);
        $ctx->fill();
    }

    private static function drawCircle(DrawingContext $ctx, float $x, float $y, float $radius): void
    {
        $steps = 16;
        for ($i = 0; $i <= $steps; $i++) {
            $angle = 2.0 * M_PI * $i / $steps;
            $px = $x + $radius * cos($angle);
            $py = $y + $radius * sin($angle);
            if ($i === 0) {
                $ctx->moveTo($px, $py);
            } else {
                $ctx->lineTo($px, $py);
            }
        }
        $ctx->closePath();
        $ctx->fill();
    }

    private static function drawDiamond(DrawingContext $ctx, float $x, float $y, float $half): void
    {
        $ctx->moveTo($x, $y - $half);
        $ctx->lineTo($x + $half, $y);
        $ctx->lineTo($x, $y + $half);
        $ctx->lineTo($x - $half, $y);
        $ctx->closePath();
        $ctx->fill();
    }

    private static function drawTriangle(DrawingContext $ctx, float $x, float $y, float $half): void
    {
        $ctx->moveTo($x, $y - $half);
        $ctx->lineTo($x + $half, $y + $half);
        $ctx->lineTo($x - $half, $y + $half);
        $ctx->closePath();
        $ctx->fill();
    }
}
