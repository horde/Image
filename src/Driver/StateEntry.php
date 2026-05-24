<?php

declare(strict_types=1);

namespace Horde\Image\Driver;

use Horde\Image\Color\Color;
use Horde\Image\Drawing\LineCap;
use Horde\Image\Drawing\LineDashPattern;
use Horde\Image\Geometry\AffineTransform;

/**
 * @internal
 */
final class StateEntry
{
    public function __construct(
        public readonly Color $fillColor,
        public readonly Color $strokeColor,
        public readonly float $lineWidth,
        public readonly LineCap $lineCap,
        public readonly LineDashPattern $dashPattern,
        public readonly AffineTransform $transform,
        public readonly ?string $clipPath,
    ) {}
}
