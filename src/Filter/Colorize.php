<?php

declare(strict_types=1);

namespace Horde\Image\Filter;

use Horde\Image\Color\Color;

final class Colorize implements Filter
{
    public function __construct(
        public readonly Color $color,
        public readonly float $opacity = 1.0,
    ) {}
}
