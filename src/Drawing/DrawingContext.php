<?php

declare(strict_types=1);

namespace Horde\Image\Drawing;

use Horde\Image\Color\Color;
use Horde\Image\Geometry\AffineTransform;

/**
 * 2D drawing context interface.
 *
 * Method signatures are duck-type compatible with Horde\Pdf\ContentStreamBuilder
 * for the shared subset of operations (state, color, path, painting, transform).
 */
interface DrawingContext
{
    public function save(): static;

    public function restore(): static;

    public function setFillColor(Color $color): static;

    public function setStrokeColor(Color $color): static;

    public function setLineWidth(float $width): static;

    public function setLineCap(LineCap $cap): static;

    public function setDashPattern(LineDashPattern $pattern): static;

    public function moveTo(float $x, float $y): static;

    public function lineTo(float $x, float $y): static;

    public function curveTo(
        float $x1,
        float $y1,
        float $x2,
        float $y2,
        float $x3,
        float $y3,
    ): static;

    public function closePath(): static;

    public function rect(float $x, float $y, float $w, float $h): static;

    public function stroke(): static;

    public function fill(): static;

    public function fillAndStroke(): static;

    public function clip(): static;

    public function transform(AffineTransform $transform): static;
}
