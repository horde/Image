<?php

declare(strict_types=1);

namespace Horde\Image\Driver;

use Horde\Image\Color\Color;
use Horde\Image\Drawing\DrawingContext;
use Horde\Image\Effect\Effect;
use Horde\Image\Filter\Filter;
use Horde\Image\Geometry\Rectangle;
use Horde\Image\Geometry\Size;

interface ImageResource
{
    public function size(): Size;

    public function resize(Size $size): static;

    public function crop(Rectangle $region): static;

    public function rotate(float $angleDeg, Color $background): static;

    public function flip(bool $horizontal = false, bool $vertical = false): static;

    public function apply(Filter $filter): static;

    public function effect(Effect $effect): static;

    public function drawingContext(): DrawingContext;
}
